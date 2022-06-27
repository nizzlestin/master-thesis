<?php


namespace App\Service;


use App\Entity\Repo;
use Exception;
use Symfony\Component\Process\Process;
use function getcwd;

class LOCMetricCalculator
{
    public function executeScc(Repo $repo, string $out = 'output.csv'): Process {
        $cwd = getcwd();
        $id = $repo->getUuid();
        '-i=js,php,hpp,cu';
        $process = new Process(['scc', '--no-gen', '--no-cocomo', '--format', 'csv',"-o=$cwd/$id/$out", '.'], "$cwd/$id/repo/");
        $process->run(function ($type, $buffer) {

        });

        return $process;
    }

    public function calculateAndStore(Repo $repo) {
        $process = $this->executeScc($repo);
        if(!empty($process->getErrorOutput())) {
            throw new Exception('Scc ran into an error');
        }
        $output = $process->getOutput();
        if(!empty($output)) {

        }
    }
}
