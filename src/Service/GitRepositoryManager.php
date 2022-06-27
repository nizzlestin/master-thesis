<?php


namespace App\Service;


use App\Entity\Repo;
use Symfony\Component\Process\Process;
use function explode;
use function getcwd;
use function implode;
use function substr;
use function var_dump;

class GitRepositoryManager
{
    public function cloneGitRepository(Repo $repo): Process {
        $process = new Process(['git', 'clone', $repo->getUrl(), 'repo'], $this->cwd($repo->getUuid()));
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
        $cmd = explode(' ', 'git --no-pager log --pretty=format:"%H"');
        $process = new Process($cmd, $this->repo($repo->getUuid()));
        $process->run();
        $cleanedOutput = str_replace('"', '',$process->getOutput());
        return explode("\n", $cleanedOutput);
    }

    private function cwd(string $subdir): string {
        return getcwd().'/'.$subdir;
    }

    private function repo(string $subdir): string {
        return $this->cwd($subdir).'/repo';
    }
}
