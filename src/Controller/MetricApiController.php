<?php

namespace App\Controller;

use App\Entity\FileChurn;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\StatisticRepository;
use App\Service\GitRepositoryManager;
use App\Service\MetricDataProvider;
use phpDocumentor\Reflection\File;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/metrics', name: 'app_metrics')]
class MetricApiController extends AbstractController
{
    private StatisticRepository $statisticRepository;
    private ProjectRepository $projectRepository;
    private MetricDataProvider $metricDataProvider;

    public function __construct(StatisticRepository $statisticRepository, ProjectRepository $projectRepository, MetricDataProvider $metricDataProvider)
    {
        $this->statisticRepository = $statisticRepository;
        $this->projectRepository = $projectRepository;
        $this->metricDataProvider = $metricDataProvider;
    }

    #[Route('/by-file/{project}', name: 'by_file')]
    public function getFileMetrics(Project $project): JsonResponse
    {
        $data = $this->metricDataProvider->getMostComplexFiles($project);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/project/{project}', name: 'by_project')]
    public function getProjectMetrics(Project $project): JsonResponse
    {
        $data = $this->metricDataProvider->getAggregateMetricsGroupedByLanguageAndCommit($project);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/single-file/{id}', name: 'by_singe_file')]
    public function getSingleFileMetrics(FileChurn $fileChurn): JsonResponse
    {
        $data = $this->metricDataProvider->getTimelineForFileAndProject($fileChurn->getFile(), $fileChurn->getProject());
        return new JsonResponse($data, 200, []);
    }

    #[Route('/code-churn/{project}', name: 'code_churn')]
    public function getCodeChurn(Project $project): JsonResponse
    {
        $data = $this->metricDataProvider->getComplexityChurnMetrics($project);
        return new JsonResponse($data, 200, [], true);
    }


}
