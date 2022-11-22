<?php

namespace App\MessageHandler;

use App\Message\CloneMessage;
use App\Message\MetricMessage;
use App\Repository\ProjectRepository;
use App\Service\GitRepositoryManager;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use function mkdir;

#[AsMessageHandler]
class CloneHandler
{
    private GitRepositoryManager $gitRepositoryManager;
    private ProjectRepository $projectRepository;
    private ParameterBagInterface $parameterBag;
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus, GitRepositoryManager $gitRepositoryManager, ProjectRepository $projectRepository, ParameterBagInterface $parameterBag)
    {
        $this->gitRepositoryManager = $gitRepositoryManager;
        $this->projectRepository = $projectRepository;
        $this->parameterBag = $parameterBag;
        $this->bus = $bus;
    }


    public function __invoke(CloneMessage $cloneMessage)
    {
        $project = $this->projectRepository->findOneBy(['uuid' => $cloneMessage->getUuid()]);
        $this->projectRepository->add($project, true);

        mkdir($this->parameterBag->get('app.project_dir') . '/' . $project->getUuid());
        $cloneProcess = $this->gitRepositoryManager->cloneGitRepository($project, 1800);
        if ($cloneProcess->isSuccessful()) {
            $project->setClonedAt(new DateTimeImmutable());
            $this->projectRepository->add($project, true);
            $this->bus->dispatch(new MetricMessage($project->getUuid()));
        }
    }
}
