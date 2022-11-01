<?php

namespace App\Service;

use App\Entity\Repo;
use App\Repository\RepoRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractMetricCalculator
{
    protected RepoRepository $repoRepository;
    protected ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag, RepoRepository $repoRepository)
    {
        $this->parameterBag = $parameterBag;
        $this->repoRepository = $repoRepository;
    }



    abstract public function execute(Repo $repo, string $output, int $timeout = null): array;
    abstract public function reformatAndStore(array $result);
    abstract public function getName(): string;
}
