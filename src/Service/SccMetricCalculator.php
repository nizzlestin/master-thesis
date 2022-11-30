<?php

namespace App\Service;

use App\Entity\Project;
use App\Model\FileExtensions;
use App\Repository\ProjectRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use function json_decode;
use function sprintf;
use function substr;

class SccMetricCalculator extends AbstractMetricCalculator
{
    private EntityManagerInterface $entityManager;

    public function __construct(ParameterBagInterface $parameterBag, ProjectRepository $projectRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct($parameterBag, $projectRepository);
        $this->entityManager = $entityManager;
    }

    public function execute(Project $project, string $output = "out.json", string $commit = '', DateTime $date = null, int $timeout = null): array
    {
        $id = $project->getUuid();

        $process = new Process(
            [
                'scc',
                '--no-gen',
                '--no-cocomo',
                '--format=csv',
                '--by-file',
                '--sql-commit',
                $commit,
                '--sql-project',
                $project->getId(),
                '--include-ext',
                FileExtensions::asString(),
                '--sql-date',
                $date? $date->format('Y-m-d H:i:s'): "",
                '--output',
                substr($commit, 0, 15),
            ],
            $this->parameterBag->get('app.project_dir') . "/$id/repo/"
        );
        if ($timeout !== null) {
            $process->setTimeout($timeout);
        }

        $process->run();
        $this->entityManager->getConnection()->executeStatement(sprintf("LOAD DATA local infile '%s' INTO TABLE statistic COLUMNS TERMINATED BY ','", $this->parameterBag->get('app.project_dir') . "/$id/repo/".substr($commit, 0, 15)));
        return [];
    }

    public function getName(): string
    {
        return 'scc';
    }
}
