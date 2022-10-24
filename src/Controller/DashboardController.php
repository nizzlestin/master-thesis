<?php

namespace App\Controller;

use App\Entity\Repo;
use App\Repository\RepoRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/d', name: 'app_dashboard')]
class DashboardController extends AbstractController
{
    #[Route('', name: '_index')]
    public function index(Request $request, RepoRepository $repoRepository, PaginatorInterface $paginator): Response
    {
        $pagination = $paginator->paginate(
            $repoRepository->findBy([], ['id' => 'DESC']),
            $request->query->getInt('page', 1),
            7
        );
        return $this->render('dashboard/index.html.twig', [
            'repos' => $pagination
        ]);
    }

    #[Route('/small-multiples/{id}', name: '_small_multiples', methods: ['GET'])]
    public function smallMultiples(Repo $repo): Response
    {
        return $this->render('repo/prototype.html.twig', [
            'repo' => $repo,
        ]);
    }

    #[Route('/biplot/{id}', name: '_biplot', methods: ['GET'])]
    public function biplot(Repo $repo): Response
    {
        return $this->render('repo/biplot.html.twig', [
            'repo' => $repo,
        ]);
    }
}
