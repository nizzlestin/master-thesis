<?php

namespace App\Service;

use App\Entity\Repo;
use App\Model\FileExtensions;
use Exception;
use Symfony\Component\Process\Process;
use function json_decode;

class SccMetricCalculator extends AbstractMetricCalculator
{
    public function execute(Repo $repo, int $timeout = null): array
    {
        $id = $repo->getUuid();
        $process = new Process(['scc', '--no-gen', '--no-cocomo', '--format', 'json', '--include-ext', FileExtensions::asString()], $this->parameterBag->get('app.repo_dir') . "/$id/repo/");
        if ($timeout !== null) {
            $process->setTimeout($timeout);
        }
        $process->run();
        $repo->setGolangMetricsCalculated(true);
        $this->repoRepository->add($repo, true);
        return json_decode($process->getOutput(), true);
    }

    public function reformatAndStore(array $result)
    {

    }

    public function getName(): string {
        return 'scc';
    }
}
