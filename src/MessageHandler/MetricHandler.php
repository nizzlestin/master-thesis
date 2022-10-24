<?php

namespace App\MessageHandler;

use App\Message\MetricMessage;
use App\Repository\RepoRepository;
use App\Service\AbstractMetricCalculator;
use App\Service\GitRepositoryManager;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function array_filter;
use function array_merge;
use function array_reduce;
use function array_sum;
use function count;
use function fclose;
use function fopen;
use function fwrite;
use function intval;
use function is_array;
use function is_float;
use function json_encode;
use function max;
use function memory_get_usage;
use function round;
use function sort;

#[AsMessageHandler]
class MetricHandler
{
    private RepoRepository $repoRepository;
    private GitRepositoryManager $gitRepositoryManager;
    private ParameterBagInterface $parameterBag;
    private iterable $calculators;

    public function __construct(iterable $calculators, GitRepositoryManager $gitRepositoryManager, RepoRepository $repoRepository, ParameterBagInterface $parameterBag)
    {
        $this->gitRepositoryManager = $gitRepositoryManager;
        $this->repoRepository = $repoRepository;
        $this->parameterBag = $parameterBag;
        $this->calculators = $calculators;
    }

    public function __invoke(MetricMessage $message): void
    {
        $repo = $this->repoRepository->findOneBy(['uuid' => $message->getUuid()]);
        if (false === $repo->isCloned()) {
            return;
        }

        $hashes = $this->gitRepositoryManager->getCommitHashesOfCurrent($repo);
        $this->repoRepository->add($repo, true);

        $tmpMetrics = [];
        foreach ($this->calculators as $calculator) {
            /** @var AbstractMetricCalculator $calculator */
            $tmpMetrics[$calculator->getName()] = [];
        }

        $sizeOfHashes = count($hashes);
        $increment = $this->getIncrementor($sizeOfHashes);

        for ($c = 0; $c < $sizeOfHashes; $c += $increment) {
            $h = $hashes[$c];
            $hash = $h[0];
            $date = $h[1];
            $committerMail = $h[2];
            $dateTime = new DateTimeImmutable($date);
            $this->gitRepositoryManager->checkoutCommit($repo, $hash);
            foreach ($this->calculators as $calculator) {
                /** @var AbstractMetricCalculator $calculator */
                $result = $calculator->execute($repo, 1800);
                $tmpMetrics[$calculator->getName()][] = ['date' => $dateTime->format('d/m/Y'), 'hash' => $hash, 'committer' => $committerMail, 'metrics' => $result];
            }
        }
        $repo->setGolangMetricsCalculated(true);
        $repo->setRustMetricsCalculated(true);
        $this->repoRepository->add($repo, true);

        $metrics = [];
        foreach ($tmpMetrics['scc'] as $commit) {
            foreach ($commit['metrics'] as $metric) {
                if(empty($metric)) {
                    continue;
                }
                $language = $metric['Name'];
                unset($metric['Name']);
                $files = $metric['Files'];
                $max = 0;
                $maxFile = null;
                foreach ($files as $file) {
                    $complexity = $file['Complexity'];
                    if ($complexity > $max) {
                        $maxFile = $file;
                        $max = $complexity;
                        $metric['max'] = $max;
                        $metric['maxFile'] = $maxFile['Filename'];
                    }
                }
                $complexities = array_map(function ($f) {
                    return $f['Complexity'] ?? 0;
                }, $files);
                $average = $this->array_average($complexities);
                $median = $this->array_median($complexities);
                $metric['median'] = $median;
                $metric['average'] = $average;
                unset($metric['Files']);
                $metrics[] = array_merge(['date' => $commit['date'], 'hash' => $commit['hash'], 'committer' => $commit['committer'], 'language' => $language], $metric);
            }
        }
        usort($metrics, function ($item1, $item2) {
            $a = DateTime::createFromFormat('d/m/Y', $item1['date']);
            $b = DateTime::createFromFormat('d/m/Y', $item2['date']);
            return $a <=> $b;
        });
        $this->repoRepository->add($repo, true);

        $file = fopen($this->parameterBag->get('app.repo_dir') . '/' . $repo->getUuid() . '/metrics.json', 'w');
        fwrite($file, json_encode($metrics));
        fclose($file);
        $this->repoRepository->add($repo, true);
    }

    private function getIncrementor($totalHashes)
    {
        $increment = 1;
        $limit = 250;
        if ($totalHashes > $limit) {
            $tmpIncrement = round($totalHashes / $limit, 0);
            $increment = intval($tmpIncrement);
        }
        return $increment;
    }

    private function array_median(array $array): int|float
    {
        if (!$array) {
            return 0;
        }
        sort($array);
        $middleIndex = count($array) / 2;
        if (is_float($middleIndex)) {
            return $array[(int)$middleIndex];
        }
        return ($array[$middleIndex] + $array[$middleIndex - 1]) / 2;
    }

    private function array_average(array $array): int|float
    {
        if (!$array) {
            return 0;
        }
        return array_sum($array) / count($array);
    }
}
