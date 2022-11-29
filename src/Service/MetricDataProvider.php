<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\StatisticFile;
use App\Repository\StatisticRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use function array_map;
use function array_sum;
use function array_values;
use function count;
use function fclose;
use function file_get_contents;
use function filemtime;
use function fopen;
use function fwrite;
use function json_encode;
use function md5;
use function round;
use function strval;
use const DIRECTORY_SEPARATOR;
use const PATH_SEPARATOR;

class MetricDataProvider
{
    private StatisticRepository $statisticRepository;
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private ParameterBagInterface $parameterBag;

    public function __construct(EntityManagerInterface $entityManager, StatisticRepository $statisticRepository, ParameterBagInterface $parameterBag)
    {
        $this->statisticRepository = $statisticRepository;
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->parameterBag = $parameterBag;
    }

    public function getAggregateMetricsGroupedByLanguageAndCommit(Project $project): string
    {
        $statFile = $project->getStatisticFilesByFunctionName(__FUNCTION__);
        if ($statFile !== null) {
            return file_get_contents($this->parameterBag->get('app.metric_dir') . DIRECTORY_SEPARATOR . $statFile->getFilename());
        }
        $stmt = $this->connection->prepare(
//            "SELECT JSON_ARRAYAGG(JSON_OBJECT('language', s0.language, 'commit', s0.commit, 'commit_date', s0.commit_date, 'byte', s0.byte, 'blank', s0.blank, 'comment', s0.comment , 'code', s0.code, 'complexity', s0.complexity))
//                FROM
            "SELECT language as language, commit as commit, DATE_FORMAT(commit_date, '%d/%m/%Y') as commit_date, sum(byte) as byte, sum(blank) as blank, sum(comment) as comment, sum(code) as code, sum(complexity) as complexity 
                FROM statistic s
                WHERE s.project_id = :project
                GROUP BY s.language, s.commit ORDER BY s.commit_date ASC;"//, s.language ASC;"
        );
        $result = $stmt->executeQuery(['project' => $project->getId()])->fetchAllAssociative();

        $json = json_encode($result);
        $this->writeStatisticsToFile($project, __FUNCTION__, $json);
        return $json;
    }

    public function getMostComplexFiles(Project $project, int $limit = 10)
    {
        $statFile = $project->getStatisticFilesByFunctionName(__FUNCTION__);
        if($statFile !== null)
        {
            return file_get_contents($this->parameterBag->get('app.metric_dir').DIRECTORY_SEPARATOR.$statFile->getFilename());
        }

        $sql = "SELECT JSON_ARRAYAGG(JSON_OBJECT(
            'commit',s0.commit, 'language', s0.language, 'file',s0.file_basename, 'comment', s0.comment,'code', s0.code,'complexity', s0.complexity,'commit_date', DATE_FORMAT(s0.commit_date, '%d/%m/%Y')) order by s0.commit_date ASC, s0.file) FROM
             statistic s0
            WHERE s0.project_id = :project
                AND s0.file_basename in (SELECT * FROM (SELECT file_basename 
            FROM statistic s1
            WHERE s1.project_id = :project
              AND s1.commit_date = (SELECT max(s2.commit_date) as maxDate FROM statistic s2 WHERE s2.project_id = :project)
            GROUP BY s1.file_basename, s1.commit
            ORDER BY s1.complexity DESC LIMIT 10) subq);";
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery(['project' => $project->getId()])->fetchOne();
        $this->writeStatisticsToFile($project, __FUNCTION__, $result);
        return $result;
    }

    private function writeStatisticsToFile(Project $project, string $functionName, $fileContents)
    {
        $fileName = $project->getId() . '_' . md5($functionName) . '.json';
        $filePath = $this->parameterBag->get('app.metric_dir') . DIRECTORY_SEPARATOR . $fileName;
        $fileHandle = fopen($filePath, 'w');
        fwrite($fileHandle, $fileContents);
        fclose($fileHandle);
        $statFile = new StatisticFile();
        $statFile->setFilename($fileName);
        $statFile->setFunctionName($functionName);
        $project->addStatisticFile($statFile);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    }

    public function getPerLanguagePerCommitAggregates(Project $project)
    {
        $statFile = $project->getStatisticFilesByFunctionName(__FUNCTION__);
        if ($statFile !== null) {
            return file_get_contents($this->parameterBag->get('app.metric_dir') . DIRECTORY_SEPARATOR . $statFile->getFilename());
        }
        $sql = "SELECT JSON_ARRAYAGG(JSON_OBJECT('commit', s.commit, 'language', s.language, 'commit_date', s.commit_date, 'avg_complexity', ROUND(avg_complexity,2), 'med_complexity', ROUND(med_table.med,2))) 
                FROM (SELECT s0.language, s0.commit, s0.commit_date, avg(s0.complexity) as avg_complexity from statistic s0 where s0.project_id = :project group by s0.commit, s0.language) s
                LEFT JOIN 
                (SELECT DISTINCT x.language, x.commit, x.med  FROM 
                (SELECT s1.language, s1.commit, s1.project_id, median(s1.complexity) over (partition by s1.language, s1.commit) as med from statistic s1 where s1.project_id = :project) x)
                 as med_table 
                 ON s.commit = med_table.commit and s.language = med_table.language;
                ";

        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery(['project' => $project->getId()])->fetchOne();
        $this->writeStatisticsToFile($project, __FUNCTION__, $result);
        return $result;
    }

    public function getTimelineForFileAndProject(string $fileBasename, Project $project) {
        $st = $this->statisticRepository->createQueryBuilder('s')
            ->andWhere('s.project = :project')
            ->andWhere('s.fileBasename = :fileBasename')
            ->setParameter('project', $project)
            ->setParameter('fileBasename', $fileBasename)->getQuery()->getArrayResult();
//        foreach ($st as $k =>$v) {
//            $v['commit_date'] = $v['commitDate']->format('Y-m-d');
//            unset($v['commitDate']);
//            $st[$k]  = $v;
//        }
        $previous = null;
        $growthRates = [];
        foreach ($st as $k => $current) {
            if ($current['code'] === 0) {
                unset($st[$k]);
                continue;
            }
            if($previous === null) {
                $previous = $current;
                continue;
            }

            $growthRates[] = 100*($current['code'] - $previous['code'])/$previous['code'];

            $previous = $current;
        }
        foreach ($st as $k =>$v) {
            $v['commit_date'] = $v['commitDate']->format('Y-m-d');
            unset($v['commitDate']);
            $st[$k]  = $v;
        }
        if(empty($growthRates))
        {
            $growth = 'NaN';
        } else {
            $growth = strval(round(array_sum($growthRates)/count($growthRates), 3));
        }
        return ['data' => $st, 'growth' => $growth];
    }

    public function getComplexityChurnMetrics(Project $project)
    {
        $statFile = $project->getStatisticFilesByFunctionName(__FUNCTION__);
        if ($statFile !== null) {
            return file_get_contents($this->parameterBag->get('app.metric_dir') . DIRECTORY_SEPARATOR . $statFile->getFilename());
        }
        $sql = "SELECT s.file, s.file_basename, complexity, churn, f.id as fid FROM statistic s
                    LEFT JOIN file_churn f ON s.project_id = f.project_id AND f.file = s.file_basename 
                    WHERE s.project_id = :id AND s.commit=:hash AND f.project_id = :id;";
        $stmt = $this->connection->prepare($sql);
        $results = $stmt->executeQuery(['id' => $project->getId(), 'hash' => $project->getLatestKnownCommit()])->fetchAllAssociative();
        $churns = array_map(function ($r){
            return $r['churn'];
        }, $results);
        $complexities = array_map(function ($r){
            return $r['complexity'];
        }, $results);
        $minChurn = min($churns);
        $maxChurn = max($churns);
        $minComp = min($complexities);
        $maxComp = max($complexities);
        $returnArray = [];
        foreach ($results as $result) {
            $returnArray[] = [
                'x' => round(($result['complexity']-$minComp)/($maxComp-$minComp), 2),
                'y' => round(($result['churn']-$minChurn)/($maxChurn-$minChurn), 2),
                'z' => $result['file'],
                'full' => $result['file_basename'],
                'fid' => $result['fid']
            ];
        }
        $json = json_encode($returnArray);
        $this->writeStatisticsToFile($project, __FUNCTION__, $json);
        return $json;

    }
}
