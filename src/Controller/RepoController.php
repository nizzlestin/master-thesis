<?php

namespace App\Controller;

use App\Entity\Repo;
use App\Form\RepoType;
use App\Message\CloneMessage;
use App\Repository\RepoRepository;
use App\Service\GitRepositoryManager;
use App\Service\RustCodeAnalyzerMetricCalculator;
use DateTimeImmutable;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use function array_merge;
use function fclose;
use function fopen;
use function fwrite;
use function intval;
use function json_encode;
use function mkdir;
use function round;
use function sizeof;

#[Route('/repo')]
class RepoController extends AbstractController
{
    #[Route('/rust', name: 'app_repo_rust', methods: ['GET'])]
    public function rustPipeline(RustCodeAnalyzerMetricCalculator $calculator, RepoRepository $repoRepository, GitRepositoryManager $gitRepositoryManager, ParameterBagInterface $parameterBag): Response {
        $uuid = "6e72be50-a65e-4e75-955c-ae7b6dcd440f";
        $repo = $repoRepository->findOneBy(['uuid' => $uuid]);
        $hashes = $gitRepositoryManager->getCommitHashesOfCurrent($repo);
        $repo->setStatus('hashes_computed');
        $repoRepository->add($repo, true);
        dd($hashes);
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
        $repo->setStatus('run_scc_on_commits');
        $repoRepository->add($repo, true);

        $metrics = [];
        foreach ($tmpMetrics['rust'] as $commit) {
            foreach ($commit['metrics'] as $metric) {
                $language = $metric['Name'];
                unset($metric['Name']);
                $metrics[] = array_merge(['date' => $commit['date'], 'hash' => $commit['hash'], 'language' => $language], $metric);
            }
        }
        $repo->setStatus('restructured');
        $repoRepository->add($repo, true);


        $file = fopen($parameterBag->get('kernel.project_dir') . '/public/repo' . '/' . $repo->getUuid() . '/rust-metrics.json', 'w');
        fwrite($file, json_encode($metrics));
        fclose($file);
        $repo->setStatus('done');
        $repoRepository->add($repo, true);
        return new Response();
    }

    #[Route('/testClone', name: 'app_repo_testClone', methods: ['GET'])]
    public function testClone(RepoRepository $repoRepository, GitRepositoryManager $gitRepositoryManager, ParameterBagInterface $parameterBag): Response {
        $repo = new Repo();
        $repo->setUrl("git@github.com:kingpfogel/master-project.git");
        $repo->setStatus('init');
        $repoRepository->add($repo, true);

        mkdir($parameterBag->get('kernel.project_dir') . '/public/repo' . '/' . $repo->getUuid());
        $cloneProcess = $gitRepositoryManager->cloneGitRepository($repo, 1800);
        $repo->setStatus('repository_cloned');
        $repo->setCloned(true);
        $repo->setClonedAt(new DateTimeImmutable());
        $repoRepository->add($repo, true);
        return new Response($repo->getUuid());
    }
    #[Route('/', name: 'app_repo_index', methods: ['GET'])]
    public function index(RepoRepository $repoRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $pagination = $paginator->paginate(
            $repoRepository->findAll(),
            $request->query->getInt('page', 1),
            7
        );

        return $this->render('repo/index.html.twig', [
            'repos' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_repo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, RepoRepository $repoRepository, MessageBusInterface $bus): Response
    {
        $repo = new Repo();
        $form = $this->createForm(RepoType::class, $repo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repoOrNull = $repoRepository->findOneBy(['url' => $repo->getUrl()]);
            if($repoOrNull !== null) {
                return $this->redirectToRoute('app_repo_show', ['id' => $repoOrNull->getId()]);
            }
            $repoRepository->add($repo, true);
            $bus->dispatch(new CloneMessage($repo->getUuid()));

            return $this->redirectToRoute('app_repo_show', ['id' => $repo->getId()]);
        }
        return $this->renderForm('repo/new.html.twig', [
            'repo' => $repo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_repo_show', methods: ['GET'])]
    public function show(Repo $repo, ParameterBagInterface $parameterBag, MessageBusInterface $bus): Response
    {
//        $bus->dispatch(new GitClone($repo->getUuid()));
//        $fileContent = file_get_contents($parameterBag->get('kernel.project_dir') . '/public/repo/'.$repo->getUuid().'/metrics.json');
        return $this->render('repo/show.html.twig', [
            'repo' => $repo,
//            'fileContent' => json_decode($fileContent)
        ]);
    }

    #[Route('/{id}/edit', name: 'app_repo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Repo $repo, RepoRepository $repoRepository): Response
    {
        $form = $this->createForm(RepoType::class, $repo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repoRepository->add($repo, true);

            return $this->redirectToRoute('app_repo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('repo/edit.html.twig', [
            'repo' => $repo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_repo_delete', methods: ['POST'])]
    public function delete(Request $request, Repo $repo, RepoRepository $repoRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $repo->getId(), $request->request->get('_token'))) {
            $repoRepository->remove($repo, true);
        }

        return $this->redirectToRoute('app_repo_index', [], Response::HTTP_SEE_OTHER);
    }
}
