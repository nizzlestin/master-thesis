<?php


namespace App\Service;


use App\Entity\Repo;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use function getcwd;
use function json_decode;
use const SIGKILL;

class LOCMetricCalculator
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    private function getPublicDirectory(): string {
        return $this->parameterBag->get('kernel.project_dir') . '/public/repo';
    }

    public function executeScc(Repo $repo, string $out = 'output.csv', int $timeout = null): array {
        try {
            $cwd = getcwd();
            $id = $repo->getUuid();
            $options = '-i=js,php,hpp,cu';
//        $process = new Process(['scc', '--no-gen', '--no-cocomo', '--format', 'csv',"-o=$cwd/$id/$out", '.'], "$cwd/$id/repo/");
            $process = new Process(['scc', '--no-gen', '--no-cocomo', '--format', 'json'],  $this->getPublicDirectory() . "/$id/repo/");
            if($timeout !== null) {
                $process->setTimeout($timeout);
            }
            $process->run(function ($type, $buffer) {

            });
            return json_decode($process->getOutput(), true);
        } catch (Exception $exception) {
            $process->signal(SIGKILL);
            throw $exception;
        }
    }
}
