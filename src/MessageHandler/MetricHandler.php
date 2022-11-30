<?php

namespace App\MessageHandler;

use App\Entity\EvaluationStats;
use App\Entity\FileChurn;
use App\Entity\Project;
use App\Message\CleanupFilesystemMessage;
use App\Message\MetricMessage;
use App\Repository\ProjectRepository;
use App\Service\AbstractMetricCalculator;
use App\Service\GitRepositoryManager;
use App\Service\SccMetricCalculator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;
use function explode;
use function memory_get_usage;
use function microtime;
use function print_r;
use function sprintf;
use function strlen;

#[AsMessageHandler]
class MetricHandler
{
    private ProjectRepository $projectRepository;
    private GitRepositoryManager $gitRepositoryManager;
    private ParameterBagInterface $parameterBag;
    private MessageBusInterface $messageBus;
    private SccMetricCalculator $calculator;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $timerLogger;

    public function __construct(LoggerInterface $timerLogger, EntityManagerInterface $entityManager, MessageBusInterface $messageBus, SccMetricCalculator $calculator, GitRepositoryManager $gitRepositoryManager, ProjectRepository $projectRepository, ParameterBagInterface $parameterBag)
    {
        $this->gitRepositoryManager = $gitRepositoryManager;
        $this->projectRepository = $projectRepository;
        $this->parameterBag = $parameterBag;
        $this->messageBus = $messageBus;
        $this->calculator = $calculator;
        $this->entityManager = $entityManager;
        $this->timerLogger = $timerLogger;
    }

    public function __invoke(MetricMessage $message): void
    {
        $start = microtime(true);
        $project = $this->projectRepository->findOneBy(['uuid' => $message->getUuid()]);
        $this->storeCodeChurn($project);
        $tmpHashes = array_reverse($this->gitRepositoryManager->getCommitHashesOfCurrent($project));
        $this->projectRepository->add($project, true);

        $hashes = [];
        $sizeOfHashes = count($tmpHashes);
        $increment = $this->getIncrementor($sizeOfHashes);

        $i = 1;
        for ($c = 0; $c < $sizeOfHashes; $c += $increment) {
            $hashes[] = $tmpHashes[$c];
        }
        $hash = null;
        foreach ($hashes as $index => $h) {
            $hash = $h[0];
            $date = $h[1];
            $committerMail = $h[2];
            $dateTime = new DateTime($date);
            $this->gitRepositoryManager->checkoutCommit($project, $hash);
            /** @var AbstractMetricCalculator $calculator */
            $this->calculator->execute($project, $i . ".json", $hash, $dateTime, 1800);
        }

        $project->setLatestKnownCommit($hash);
        $project->setEvaluatedCommits($i);
        $project->setStatus('done');
        $this->projectRepository->add($project, true);
        $end = microtime(true);
        $evaluation = new EvaluationStats();
        $evaluation->setElapsedTime($end - $start);
        $numberOfFiles = $this->entityManager
            ->getConnection()
            ->prepare('SELECT count(id) FROM statistic WHERE project_id = :id')
            ->executeQuery(['id' => $project->getId()])
            ->fetchOne();
        $sloc = $this->entityManager
            ->getConnection()
            ->prepare('SELECT sum(code) FROM statistic WHERE project_id = :id')
            ->executeQuery(['id' => $project->getId()])
            ->fetchOne();
//        $evaluation->setNumberOfFiles($numberOfFiles);
//        $evaluation->setSloc($sloc);
//        $evaluation->setProject($project);
//        $evaluation->setOverallCommits($project->getTotalCommits());
//        $evaluation->setTask('metric_calculation');
//        $evaluation->setNumberOfCommits($this->parameterBag->get('app.sample_limit'));
//        $evaluation->setUrl($project->getUrl());
//        $evaluation->setMemory(memory_get_usage());
//        $this->entityManager->persist($evaluation);
        $this->entityManager->flush();
        $this->timerLogger->debug(sprintf('url: %s; id:%d; time in seconds: %f; memory_get_usage(): %d', $project->getUrl(), $project->getId(), $end - $start, memory_get_usage()));

        $this->messageBus->dispatch(new CleanupFilesystemMessage($project->getUuid()));
    }

    private function getIncrementor($totalHashes)
    {
        $increment = 1;
        $limit = $this->parameterBag->get('app.sample_limit') ?? 250;
        if ($totalHashes > $limit) {
            $tmpIncrement = round($totalHashes / $limit, 0);
            $increment = intval($tmpIncrement);
        }
        return $increment;
    }

    private function storeCodeChurn(Project $project)
    {
//        $cmd = explode(' ', 'git log --all -M -C --name-only --format="format:" "$@" | sort | grep -v "^$" | uniq -c | sort -n');
        $process = new Process(['git-churn'], $this->gitRepositoryManager->project($project->getUuid()));
        $process->setTimeout(300);
        $process->run();
        $cleanedOutput = trim($process->getOutput());
        $xs = explode("\n", $cleanedOutput);
        foreach ($xs as $x) {
            $out = explode(' ', trim($x));
            $this->timerLogger->debug(print_r($x));
            if (strlen($out[1]) > 255 || $out[1] == null) {
                continue;
            }
            $churn = new FileChurn(intval($out[0]), $out[1], $project);
            $this->entityManager->persist($churn);
        }
        $this->entityManager->flush();
    }
}
