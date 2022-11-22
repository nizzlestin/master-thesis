<?php


namespace App\Service;


use App\Entity\Project;
use App\Repository\ProjectRepository;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use function explode;
use function intval;
use function removeTrailingWhitespace;

class GitRepositoryManager
{
    private ProjectRepository $projectRepository;

    public function __construct(ParameterBagInterface $parameterBag, ProjectRepository $projectRepository)
    {
        $this->parameterBag = $parameterBag;
        $this->projectRepository = $projectRepository;
    }

    public function cloneGitRepository(Project $project, int $timeout = null): Process {
        $clonedAt = new DateTimeImmutable();
        $process = new Process(['git', 'clone', $project->getUrl(), 'repo'], $this->cwd($project->getUuid()));
        if($timeout !== null) {
            $process->setTimeout($timeout);
        }
        $process->run();
        if($process->isSuccessful()) {
            $project->setCloned(true);
            $project->setClonedAt($clonedAt);
            $project->setTotalCommits($this->getRevisionCount($project));
            $this->projectRepository->add($project, true);
        }
        return $process;
    }

    private function getRevisionCount(Project $project): int {
        $cmd = explode(' ', 'git rev-list --count HEAD');
        $process = new Process($cmd, $this->project($project->getUuid()));
        $process->run();
        echo $process->getOutput();
        return intval($process->getOutput());
    }

    public function checkoutCommit(Project $project, string $hash) {
        $process = new Process(['git', 'checkout', $hash], $this->project($project->getUuid()));
        $process->run();
        return $process;
    }

    public function getCommitHashesOfCurrent(Project $project): array {
        $cmd = explode(' ', 'git --no-pager log --pretty=format:"%H,%ci,%cE" --first-parent');
        $process = new Process($cmd, $this->project($project->getUuid()));
        $process->run();
        $cleanedOutput = str_replace('"','',$process->getOutput());
        $xs = explode("\n", $cleanedOutput);
        $out = [];
        foreach ($xs as $x) {
            $out[] = explode(',', $x);
        }
        return $out;
    }

    public function getTotalCommitsByDeveloper(Project $project) {

    }

    public function cwd(string $subdir): string {
        return $this->parameterBag->get('app.project_dir').'/'.$subdir;
    }

    public function project(string $subdir): string {
        return $this->cwd($subdir).'/repo';
    }
}
