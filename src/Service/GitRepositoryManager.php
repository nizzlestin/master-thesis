<?php


namespace App\Service;


use App\Entity\Repo;
use App\Repository\RepoRepository;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use function explode;
use function intval;

class GitRepositoryManager
{
    private RepoRepository $repoRepository;

    public function __construct(ParameterBagInterface $parameterBag, RepoRepository $repoRepository)
    {
        $this->parameterBag = $parameterBag;
        $this->repoRepository = $repoRepository;
    }

    public function cloneGitRepository(Repo $repo, int $timeout = null): Process {
        $clonedAt = new DateTimeImmutable();
        $process = new Process(['git', 'clone', $repo->getUrl(), 'repo'], $this->cwd($repo->getUuid()));
        if($timeout !== null) {
            $process->setTimeout($timeout);
        }
        $process->run();
        if($process->isSuccessful()) {
            $repo->setCloned(true);
            $repo->setClonedAt($clonedAt);
            $repo->setTotalCommits($this->getRevisionCount($repo));
            $this->repoRepository->add($repo, true);
        }
        return $process;
    }

    private function getRevisionCount(Repo $repo): int {
        $cmd = explode(' ', 'git rev-list --count HEAD');
        $process = new Process($cmd, $this->repo($repo->getUuid()));
        $process->run();
        echo $process->getOutput();
        return intval($process->getOutput());
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
        $cmd = explode(' ', 'git --no-pager log --pretty=format:"%H,%ci,%cE" --no-merges');
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
        return $this->parameterBag->get('app.repo_dir').'/'.$subdir;
    }

    private function repo(string $subdir): string {
        return $this->cwd($subdir).'/repo';
    }
}
