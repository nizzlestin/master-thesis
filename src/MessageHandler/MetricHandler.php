<?php

namespace App\MessageHandler;

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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;
use function explode;
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

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messageBus, SccMetricCalculator $calculator, GitRepositoryManager $gitRepositoryManager, ProjectRepository $projectRepository, ParameterBagInterface $parameterBag)
    {
        $this->gitRepositoryManager = $gitRepositoryManager;
        $this->projectRepository = $projectRepository;
        $this->parameterBag = $parameterBag;
        $this->messageBus = $messageBus;
        $this->calculator = $calculator;
        $this->entityManager = $entityManager;
    }

    public function __invoke(MetricMessage $message): void
    {
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
            $this->calculator->execute($project, $i.".json", $hash, $dateTime, 1800);
        }

        $project->setLatestKnownCommit($hash);
        $project->setEvaluatedCommits($i);
        $project->setStatus('done');
        $this->projectRepository->add($project, true);
        $this->messageBus->dispatch(new CleanupFilesystemMessage($project->getUuid()));
    }

    private function getIncrementor($totalHashes)
    {
        $increment = 1;
        $limit = $this->parameterBag->get('app.sample_limit')??250;
        if ($totalHashes > $limit) {
            $tmpIncrement = round($totalHashes / $limit, 0);
            $increment = intval($tmpIncrement);
        }
        return $increment;
    }

    private function storeCodeChurn(Project $project) {
//        $cmd = explode(' ', 'git log --all -M -C --name-only --format="format:" "$@" | sort | grep -v "^$" | uniq -c | sort -n');
        $process = new Process(['git-churn'], $this->gitRepositoryManager->project($project->getUuid()));
        $process->run();
        $cleanedOutput = trim($process->getOutput());
        $xs = explode("\n", $cleanedOutput);
        foreach ($xs as $x) {
            $out = explode(' ', trim($x));
            if(strlen($out[1]) > 255){
                continue;
            }
            $churn = new FileChurn($out[0], $out[1], $project);
            $this->entityManager->persist($churn);
        }
        $this->entityManager->flush();
    }
}
