<?php

namespace App\MessageHandler;

use App\Message\JsonFileMergeMessage;
use App\Message\MetricMessage;
use App\Repository\RepoRepository;
use App\Service\AbstractMetricCalculator;
use App\Service\GitRepositoryManager;
use App\Service\SccMetricCalculator;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use function array_filter;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_sum;
use function count;
use function end;
use function fclose;
use function fopen;
use function fwrite;
use function intval;
use function is_array;
use function is_float;
use function json_encode;
use function key;
use function max;
use function memory_get_usage;
use function round;
use function sort;
use function sprintf;

#[AsMessageHandler]
class MetricHandler
{
    private RepoRepository $repoRepository;
    private GitRepositoryManager $gitRepositoryManager;
    private ParameterBagInterface $parameterBag;
    private MessageBusInterface $messageBus;
    private SccMetricCalculator $calculator;

    public function __construct(MessageBusInterface $messageBus, SccMetricCalculator $calculator, GitRepositoryManager $gitRepositoryManager, RepoRepository $repoRepository, ParameterBagInterface $parameterBag)
    {
        $this->gitRepositoryManager = $gitRepositoryManager;
        $this->repoRepository = $repoRepository;
        $this->parameterBag = $parameterBag;
        $this->messageBus = $messageBus;
        $this->calculator = $calculator;
    }

    public function __invoke(MetricMessage $message): void
    {
        $repo = $this->repoRepository->findOneBy(['uuid' => $message->getUuid()]);
        if (false === $repo->isCloned()) {
            return;
        }

        $tmpHashes = array_reverse($this->gitRepositoryManager->getCommitHashesOfCurrent($repo));
        $this->repoRepository->add($repo, true);
        $hashes = [];
        $sizeOfHashes = count($tmpHashes);
        $increment = $this->getIncrementor($sizeOfHashes);

        $i = 1;
        for ($c = 0; $c < $sizeOfHashes; $c += $increment) {
            $hashes[] = $tmpHashes[$c];
        }
        $lastElementKey = array_key_last($hashes);
        foreach ($hashes as $index => $h) {
            $hash = $h[0];
            $date = $h[1];
            $committerMail = $h[2];
            $dateTime = new DateTimeImmutable($date);
            $this->gitRepositoryManager->checkoutCommit($repo, $hash);
            /** @var AbstractMetricCalculator $calculator */
            $result = $this->calculator->execute($repo, $i.".json",1800);
            $innerLastElementKey = array_key_last($result);
            foreach ($result as $innerIndex => $metric) {
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
                    } else {
                        $metric['max'] = 0;
                        $metric['maxFile'] = null;
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
                $tmpMetric = array_merge(['date' => $dateTime->format('d/m/Y'), 'hash' => $hash, 'committer' => $committerMail, 'language' => $language], $metric);
                $file = fopen($this->parameterBag->get('app.repo_dir') . '/' . $repo->getUuid() . '/'.$i.'.jsontmp', 'w');
                if($index == $lastElementKey && $innerIndex == $innerLastElementKey) {
                    fwrite($file, json_encode($tmpMetric));
                }
                else {
                    fwrite($file, json_encode($tmpMetric).",");
                }
                fclose($file);
                $i = $i+1;
            }
        }
        $repo->setEvaluatedCommits($i);
        $repo->setGolangMetricsCalculated(true);
        $this->repoRepository->add($repo, true);
        $this->messageBus->dispatch(new JsonFileMergeMessage($repo->getUuid()));
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
