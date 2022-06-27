<?php


namespace App\Service;


use App\Entity\Repo;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use function explode;
use function getcwd;
use function implode;
use function substr;
use function var_dump;

class GitRepositoryManager
{
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    private function getPublicDirectory(): string {
        return $this->parameterBag->get('kernel.project_dir') . '/public/repo';
    }

    public function cloneGitRepository(Repo $repo, int $timeout = null): Process {

        $process = new Process(['git', 'clone', $repo->getUrl(), 'repo'], $this->cwd($repo->getUuid()));
        if($timeout !== null) {
            $process->setTimeout($timeout);
        }
        $process->run();
        return $process;
    }

    public function checkoutCommit(Repo $repo, string $hash) {
//        $process = new Process(['git', 'reset', '--hard', $hash], $this->repo($repo->getUuid()));
//        $process = new Process(['git', 'checkout', '-b', 'b_'.substr($hash, 0, 7), $hash], $this->repo($repo->getUuid()));
        $process = new Process(['git', 'checkout', $hash], $this->repo($repo->getUuid()));
        $process->run(function ($type, $buffer) {
//            echo $buffer."\n";
        });
        return $process;
    }

    public function getCommitHashesOfCurrent(Repo $repo): array {
        $cmd = explode(' ', 'git --no-pager log --pretty=format:"%H,%ci"');
        $process = new Process($cmd, $this->repo($repo->getUuid()));
        $process->run();
        $cleanedOutput = str_replace('"','',$process->getOutput());
        $xs = explode("\n", $cleanedOutput);
        $out = [];
        foreach ($xs as $x) {
            $out[] = explode(',', $x);
        }
        return $out;
    }

    private function cwd(string $subdir): string {
        return $this->getPublicDirectory().'/'.$subdir;
    }

    private function repo(string $subdir): string {
        return $this->cwd($subdir).'/repo';
    }
}
