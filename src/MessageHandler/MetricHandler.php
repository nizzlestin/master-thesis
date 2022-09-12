<?php

namespace App\MessageHandler;

use App\Message\MetricMessage;
use App\Repository\RepoRepository;
use App\Service\AbstractMetricCalculator;
use App\Service\GitRepositoryManager;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function array_merge;
use function fclose;
use function fopen;
use function fwrite;
use function intval;
use function json_encode;
use function round;

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
        if(false === $repo->isCloned()) {
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
            $dateTime = new DateTimeImmutable($date);
            $this->gitRepositoryManager->checkoutCommit($repo, $hash);
            foreach ($this->calculators as $calculator) {
                /** @var AbstractMetricCalculator $calculator */
                $result = $calculator->execute($repo, 1800);
                $tmpMetrics[$calculator->getName()][] = ['date' => $dateTime->format('d/m/Y'), 'hash' => $hash, 'metrics' => $result];
            }
        }
        $this->repoRepository->add($repo, true);

        $metrics = [];
        foreach ($tmpMetrics['scc'] as $commit) {
            foreach ($commit['metrics'] as $metric) {
                $language = $metric['Name'];
                unset($metric['Name']);
                $metrics[] = array_merge(['date' => $commit['date'], 'hash' => $commit['hash'], 'language' => $language], $metric);
            }
        }
        $this->repoRepository->add($repo, true);

        $file = fopen($this->parameterBag->get('app.repo_dir') . '/' . $repo->getUuid() . '/metrics.json', 'w');
        fwrite($file, json_encode($metrics));
        fclose($file);
        $this->repoRepository->add($repo, true);
    }

    private function getIncrementor($totalHashes) {
        $increment = 1;
        $limit = 1000;
        if ($totalHashes > $limit) {
            $tmpIncrement = round($totalHashes / $limit, 0);
            $increment = intval($tmpIncrement);
        }
        return $increment;
    }
}
