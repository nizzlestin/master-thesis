<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Statistic;
use App\Repository\ProjectRepository;
use App\Repository\StatisticRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard', name: 'app_dashboard')]
class DashboardController extends AbstractController
{
    #[Route('', name: '_index')]
    public function index(Request $request, ProjectRepository $projectRepository, PaginatorInterface $paginator): Response
    {
        $pagination = $paginator->paginate(
            $projectRepository->findBy([], ['id' => 'DESC']),
            $request->query->getInt('page', 1),
            7
        );
        return $this->render('dashboard/index.html.twig', [
            'projects' => $pagination
        ]);
    }

    #[Route('/small-multiples/{id}', name: '_small_multiples', methods: ['GET'])]
    public function smallMultiples(Project $project): Response
    {
        return $this->render('project/prototype.html.twig', [
            'project' => $project
        ]);
    }

    #[Route('/small-multiples-by-file/{id}', name: '_small_multiples_by_file', methods: ['GET'])]
    public function smallMultiples2(Project $project): Response
    {
        return $this->render('project/prototype2.html.twig', [
            'project' => $project
        ]);
    }

    #[Route('/biplot/{id}', name: '_biplot', methods: ['GET'])]
    public function biplot(Project $project): Response
    {
        return $this->render('project/biplot.html.twig', [
            'project' => $project,
        ]);
    }
}
