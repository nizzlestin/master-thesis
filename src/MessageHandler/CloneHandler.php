<?php

namespace App\MessageHandler;

use App\Entity\EvaluationStats;
use App\Message\CloneMessage;
use App\Message\MetricMessage;
use App\Repository\ProjectRepository;
use App\Service\GitRepositoryManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use function memory_get_usage;
use function microtime;
use function mkdir;
use function sprintf;

#[AsMessageHandler]
class CloneHandler
{
    private GitRepositoryManager $gitRepositoryManager;
    private ProjectRepository $projectRepository;
    private ParameterBagInterface $parameterBag;
    private MessageBusInterface $bus;
    private LoggerInterface $timerLogger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        MessageBusInterface    $bus,
        GitRepositoryManager   $gitRepositoryManager,
        ProjectRepository      $projectRepository,
        EntityManagerInterface $entityManager,
        ParameterBagInterface  $parameterBag,
        LoggerInterface        $timerLogger
    )
    {
        $this->gitRepositoryManager = $gitRepositoryManager;
        $this->projectRepository = $projectRepository;
        $this->parameterBag = $parameterBag;
        $this->bus = $bus;
        $this->timerLogger = $timerLogger;
        $this->entityManager = $entityManager;
    }


    public function __invoke(CloneMessage $cloneMessage)
    {
        $start = microtime(true);
        $project = $this->projectRepository->findOneBy(['uuid' => $cloneMessage->getUuid()]);
        $this->projectRepository->add($project, true);

        mkdir($this->parameterBag->get('app.project_dir') . '/' . $project->getUuid());
        $cloneProcess = $this->gitRepositoryManager->cloneGitRepository($project, 1800);
        if ($cloneProcess->isSuccessful()) {
            $project->setClonedAt(new DateTimeImmutable());
            $this->projectRepository->add($project, true);
            $this->bus->dispatch(new MetricMessage($project->getUuid()));
        }
        $end = microtime(true);

        $evaluation = new EvaluationStats();
        $evaluation->setElapsedTime($end - $start);
        $numberOfFiles = 0;
        $sloc = 0;
        $evaluation->setNumberOfFiles($numberOfFiles);
        $evaluation->setSloc($sloc);
        $evaluation->setProject($project);
        $evaluation->setOverallCommits($project->getTotalCommits());
        $evaluation->setTask('clone');
        $evaluation->setNumberOfCommits($this->parameterBag->get('app.sample_limit'));
        $evaluation->setUrl($project->getUrl());
        $evaluation->setMemory(memory_get_usage());
        $this->entityManager->persist($evaluation);
        $this->entityManager->flush();
        $this->timerLogger->debug(sprintf('url: %s; id:%d; time in seconds: %f; memory_get_usage(): %d', $project->getUrl(), $project->getId(), $end - $start, memory_get_usage()));
    }
}
