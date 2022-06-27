<?php

namespace App\MessageHandler;

use App\Message\GitClone;
use App\Repository\RepoRepository;
use App\Service\GitRepositoryManager;
use App\Service\LOCMetricCalculator;
use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function array_merge;
use function fclose;
use function fopen;
use function fwrite;
use function json_encode;
use function mkdir;
use function sizeof;
use function substr;
use const SIGKILL;

#[AsMessageHandler]
class GitCloneHandler
{
    private GitRepositoryManager $gitRepositoryManager;
    private LOCMetricCalculator $metricCalculator;
    private RepoRepository $repoRepository;
    private ParameterBagInterface $parameterBag;

    public function __construct(GitRepositoryManager $gitRepositoryManager, LOCMetricCalculator $metricCalculator, RepoRepository $repoRepository, ParameterBagInterface $parameterBag)
    {
        $this->gitRepositoryManager = $gitRepositoryManager;
        $this->metricCalculator = $metricCalculator;
        $this->repoRepository = $repoRepository;
        $this->parameterBag = $parameterBag;
    }

    private function getPublicDirectory(): string
    {
        return $this->parameterBag->get('kernel.project_dir') . '/public/repo';
    }

    /**
     * @throws \Exception
     */
    public function __invoke(GitClone $gitClone)
    {
        try {
            $repo = $this->repoRepository->findOneBy(['uuid' => $gitClone->getUuid()]);
            $repo->setStatus('init');
            $this->repoRepository->add($repo, true);

            if ($repo->getStatus() === 'init') {
                mkdir($this->getPublicDirectory() . '/' . $repo->getUuid());
                $cloneProcess = $this->gitRepositoryManager->cloneGitRepository($repo, 1800);
                $repo->setStatus('repository_cloned');
                $this->repoRepository->add($repo, true);
//            $matches = [];
//            preg_match("/(?:git@|https:\/\/)github.com[:\/](.*)\/(.*).git/", $repo->getUrl(), $matches);
            }

            if ($repo->getStatus() === 'repository_cloned') {
                $hashes = $this->gitRepositoryManager->getCommitHashesOfCurrent($repo);
                $repo->setStatus('hashes_computed');
                $this->repoRepository->add($repo, true);
            }

            $tmpMetrics = [];


            for ($c = 0; $c < sizeof($hashes); $c += 20000) {
                $h = $hashes[$c];
//            foreach ($hashes as $h) {
                $hash = $h[0];
                $date = $h[1];
                $dateTime = new DateTimeImmutable($date);
                $this->gitRepositoryManager->checkoutCommit($repo, $hash);
                $metricsByCommit = $this->metricCalculator->executeScc($repo, substr($hash, 0, 10) . '.csv', 1800);
                $tmpMetrics[] = ['date' => $dateTime->format('d/m/Y'), 'hash' => $hash, 'metrics' => $metricsByCommit];

            }
            $repo->setStatus('run_scc_on_commits');
            $this->repoRepository->add($repo, true);

            $metrics = [];
            foreach ($tmpMetrics as $commit) {
                foreach ($commit['metrics'] as $metric) {
                    $language = $metric['Name'];
                    unset($metric['Name']);
                    $metrics[] = array_merge(['date' => $commit['date'], 'hash' => $commit['hash'], 'language' => $language], $metric);
                }
            }
            $repo->setStatus('restructured');
            $this->repoRepository->add($repo, true);


            $file = fopen($this->getPublicDirectory() . '/' . $repo->getUuid() . '/metrics.json', 'w');
            fwrite($file, json_encode($metrics));
            fclose($file);
            $repo->setStatus('done');
            $this->repoRepository->add($repo, true);
        } catch (Exception $exception) {
            $repo->setStatus('error');
            $this->repoRepository->add($repo, true);
            $cloneProcess->signal(SIGKILL);
            $cloneProcess->signal(SIGKILL);
        }

    }
}
