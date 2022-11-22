<?php

namespace App\Service;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use DateTime;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractMetricCalculator
{
    protected ProjectRepository $projectRepository;
    protected ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag, ProjectRepository $projectRepository)
    {
        $this->parameterBag = $parameterBag;
        $this->projectRepository = $projectRepository;
    }



    abstract public function execute(Project $project, string $output, string $commit = '', DateTime $dateTime = null, int $timeout = null): array;
    abstract public function getName(): string;
}
