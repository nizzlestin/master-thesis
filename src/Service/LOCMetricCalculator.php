<?php


namespace App\Service;


use App\Entity\Repo;
use App\Repository\RepoRepository;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use function getcwd;
use function json_decode;
use const SIGKILL;

class LOCMetricCalculator
{
    private ParameterBagInterface $parameterBag;
    private RepoRepository $repoRepository;

    public function __construct(ParameterBagInterface $parameterBag, RepoRepository $repoRepository)
    {
        $this->parameterBag = $parameterBag;
        $this->repoRepository = $repoRepository;
    }

    private function getPublicDirectory(): string {
        return $this->parameterBag->get('kernel.project_dir') . '/public/repo';
    }

    public function executeScc(Repo $repo, int $timeout = null): array {
        try {
            $id = $repo->getUuid();
            $options = '-i=js,php,hpp,cu';
//        $process = new Process(['scc', '--no-gen', '--no-cocomo', '--format', 'csv',"-o=$cwd/$id/$out", '.'], "$cwd/$id/repo/");
            $process = new Process(['scc', '--no-gen', '--no-cocomo', '--format', 'json'],  $this->getPublicDirectory() . "/$id/repo/");
            if($timeout !== null) {
                $process->setTimeout($timeout);
            }
            $process->run();
            $repo->setGolangMetricsCalculated(true);
            $this->repoRepository->add($repo, true);
            return json_decode($process->getOutput(), true);
        } catch (Exception $exception) {
            $process->signal(SIGKILL);
            throw $exception;
        }
    }

    public function executeRustCodeAnalyzer(Repo $repo, int $timeout = null) {
        $id = $repo->getUuid();
        $cwd = $this->getPublicDirectory() . "/$id/repo/";
        $outputDir = $this->getPublicDirectory() . "/$id/rust/";
        $cmd = explode(' ', "rust-code-analysis-cli --paths $cwd --metrics -O json -o  --pr");
        $process = new Process([], $cwd);
        if($timeout !== null) {
            $process->setTimeout($timeout);
        }
        $process->run();
        $repo->setRustMetricsCalculated(true);
        $this->repoRepository->add($repo, true);

        return json_decode($process->getOutput(), true);
    }

    private function executeCustomerAnalyzer() {

    }
}
