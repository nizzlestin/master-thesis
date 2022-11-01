<?php

namespace App\Service;

use App\Entity\Repo;
use Symfony\Component\Process\Process;
use function explode;
use function json_decode;

class RustCodeAnalyzerMetricCalculator extends AbstractMetricCalculator
{

    public function execute(Repo $repo, string $output, int $timeout = null): array
    {
        $id = $repo->getUuid();
        $cwd = $this->parameterBag->get('app.repo_dir') . "/$id/repo/";
        $outputDir = $this->parameterBag->get('app.repo_dir') . "/$id/rust/";
        $cmd = explode(' ', "rust-code-analysis-cli --paths $cwd --metrics -O json -o  --pr");
        $process = new Process([], $cwd);
        if ($timeout !== null) {
            $process->setTimeout($timeout);
        }
        $process->run();

        return json_decode($process->getOutput(), true)??[];
    }

    public function reformatAndStore(array $result)
    {
        // TODO: Implement reformatAndStore() method.
    }

    public function getName(): string {
        return 'rust';
    }
}
