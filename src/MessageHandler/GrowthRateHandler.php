<?php

namespace App\MessageHandler;

use App\Entity\Project;
use App\Message\CleanupFilesystemMessage;
use App\Message\GrowthRateMessage;
use App\Repository\ProjectRepository;
use App\Repository\StatisticRepository;
use App\Service\GitRepositoryManager;
use App\Service\SccMetricCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use function array_map;
use function array_reduce;
use function file_put_contents;
use function json_encode;
use function max;
use function min;

#[AsMessageHandler]
class GrowthRateHandler
{
    private ProjectRepository $projectRepository;
    private StatisticRepository $statisticRepository;
    private MessageBusInterface $messageBus;
    private EntityManagerInterface $entityManager;

    public function __construct(ProjectRepository $projectRepository, MessageBusInterface $messageBus, EntityManagerInterface $entityManager, StatisticRepository $statisticRepository)
    {
        $this->projectRepository = $projectRepository;
        $this->messageBus = $messageBus;
        $this->entityManager = $entityManager;
        $this->statisticRepository = $statisticRepository;
    }

    public function __invoke(GrowthRateMessage $message)
    {
        $project = $this->projectRepository->find($message->getId());
//        $rs = $this->retrieveMetrics($message->getId());
//        $rsGroupedByLanguage = [];
//        foreach ($rs as $key => $value) {
//            $rsGroupedByLanguage[$value['language']][$key] = $value;
//        }
//        $rates = [];
//        foreach ($rsGroupedByLanguage as $group) {
//            $previous = null;
//            foreach ($group as $key => $current) {
//                if (null === $previous) {
//                    $previous = $current;
//                } else {
//                    $codeRate = ($current['code'] - $previous['code']) / $previous['code'];
//                    $compRate = ($current['complexity'] - $previous['complexity']) / $previous['complexity'];
//                    $rates[] = ['commit_date' => $current['commit_date'],'language' => $key,'complexity' => $compRate, 'code' => $codeRate];
//                }
//            }
//        }
//        $content = json_encode($rates);
//        file_put_contents('test.file.json', $content);
        $this->messageBus->dispatch(new CleanupFilesystemMessage($project->getUuid()));
    }

    private function retrieveMetrics(int $id)
    {
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare("
            SELECT language, commit_date, sum(complexity) complexity, sum(code) as code FROM statistic
                    WHERE project_id = :id
                    GROUP BY commit, language
                    ORDER BY  language ASC,commit_date ASC;
            "
        );
        return $stmt->executeQuery(['id' => $id])->fetchAllAssociative();
    }
}
