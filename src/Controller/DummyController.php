<?php

namespace App\Controller;

use App\Entity\Repo;
use App\Repository\RepoRepository;
use App\Service\GitRepositoryManager;
use App\Service\RustCodeAnalyzerMetricCalculator;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function array_merge;
use function dd;
use function fclose;
use function fopen;
use function fwrite;
use function intval;
use function json_encode;
use function mkdir;
use function round;
use function sizeof;

#[Route('dummy')]
class DummyController extends AbstractController
{
    #[Route('/rust', name: 'app_repo_rust', methods: ['GET'])]
    public function rustPipeline(RustCodeAnalyzerMetricCalculator $calculator, RepoRepository $repoRepository, GitRepositoryManager $gitRepositoryManager, ParameterBagInterface $parameterBag): Response {
        $uuid = "6e72be50-a65e-4e75-955c-ae7b6dcd440f";
        $repo = $repoRepository->findOneBy(['uuid' => $uuid]);
        $hashes = $gitRepositoryManager->getCommitHashesOfCurrent($repo);
        $repoRepository->add($repo, true);
        $tmpMetrics = [];

        $tmpMetrics[$calculator->getName()] = [];

        $increment = 1;
        $limit = 1000;
        $sizeOfHashes = sizeof($hashes);
        if ($sizeOfHashes > $limit) {
            $tmpIncrement = round($sizeOfHashes / $limit, 0);
            $increment = intval($tmpIncrement);
        }

        for ($c = 0; $c < $sizeOfHashes; $c += $increment) {
            $h = $hashes[$c];
            $hash = $h[0];
            $date = $h[1];
            $committerEmail = $h[2];
            $dateTime = new DateTimeImmutable($date);
            $gitRepositoryManager->checkoutCommit($repo, $hash);

            $result = $calculator->execute($repo, 1800);
            $tmpMetrics[$calculator->getName()][] = ['date' => $dateTime->format('d/m/Y'), 'committer' => $committerEmail, 'hash' => $hash, 'metrics' => $result];
        }
        $repoRepository->add($repo, true);

        $metrics = [];
        foreach ($tmpMetrics['rust'] as $commit) {
            foreach ($commit['metrics'] as $metric) {
                $language = $metric['Name'];
                unset($metric['Name']);
                $metrics[] = array_merge(['date' => $commit['date'], 'hash' => $commit['hash'], 'language' => $language], $metric);
            }
        }
        $repoRepository->add($repo, true);


        $file = fopen($parameterBag->get('kernel.project_dir') . '/public/repo' . '/' . $repo->getUuid() . '/rust-metrics.json', 'w');
        fwrite($file, json_encode($metrics));
        fclose($file);
        $repoRepository->add($repo, true);
        return new Response();
    }

    #[Route('/testClone', name: 'app_repo_testClone', methods: ['GET'])]
    public function testClone(RepoRepository $repoRepository, GitRepositoryManager $gitRepositoryManager, ParameterBagInterface $parameterBag): Response {
        $repo = new Repo();
        $repo->setUrl("git@github.com:kingpfogel/master-project.git");
        $repoRepository->add($repo, true);

        mkdir($parameterBag->get('kernel.project_dir') . '/public/repo' . '/' . $repo->getUuid());
        $cloneProcess = $gitRepositoryManager->cloneGitRepository($repo, 1800);
        $repo->setCloned(true);
        $repo->setClonedAt(new DateTimeImmutable());
        $repoRepository->add($repo, true);
        return new Response($repo->getUuid());
    }

    #[Route('/xxx', name: 'app_repo_xxx', methods: ['GET'])]
    public function xxx(RepoRepository $repoRepository, GitRepositoryManager $gitRepositoryManager, ParameterBagInterface $parameterBag): Response {
        return $this->render('d3.html.twig');
    }
}
