<?php

namespace App\Controller;

use App\Entity\Repo;
use App\Form\RepoType;
use App\Message\CloneMessage;
use App\Repository\RepoRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/repo')]
class RepoController extends AbstractController
{
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
    public function show(Repo $repo): Response
    {
        return $this->render('repo/show.html.twig', [
            'repo' => $repo
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

    #[Route('/{id}/delete', name: 'app_repo_delete', methods: ['POST'])]
    public function delete(Request $request, Repo $repo, RepoRepository $repoRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $repo->getId(), $request->request->get('_token'))) {
            $repoRepository->remove($repo, true);
        }

        return $this->redirectToRoute('app_dashboard_index', [], Response::HTTP_SEE_OTHER);
    }
}
