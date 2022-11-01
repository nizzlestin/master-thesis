<?php

namespace App\MessageHandler;

use App\Message\CloneMessage;
use App\Message\MetricMessage;
use App\Repository\RepoRepository;
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
    private RepoRepository $repoRepository;
    private ParameterBagInterface $parameterBag;
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus, GitRepositoryManager $gitRepositoryManager, RepoRepository $repoRepository, ParameterBagInterface $parameterBag)
    {
        $this->gitRepositoryManager = $gitRepositoryManager;
        $this->repoRepository = $repoRepository;
        $this->parameterBag = $parameterBag;
        $this->bus = $bus;
    }


    public function __invoke(CloneMessage $cloneMessage)
    {
        $repo = $this->repoRepository->findOneBy(['uuid' => $cloneMessage->getUuid()]);
        $repo->setCloned(false);
        $this->repoRepository->add($repo, true);

        mkdir($this->parameterBag->get('app.repo_dir') . '/' . $repo->getUuid());
        $cloneProcess = $this->gitRepositoryManager->cloneGitRepository($repo, 1800);
        if ($cloneProcess->isSuccessful()) {
            $repo->setCloned(true);
            $repo->setClonedAt(new DateTimeImmutable());
            $this->repoRepository->add($repo, true);
            $this->bus->dispatch(new MetricMessage($repo->getUuid()));
        }
    }
}
