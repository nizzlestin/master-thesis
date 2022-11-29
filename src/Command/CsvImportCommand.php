<?php

namespace App\Command;

use App\Entity\FileChurn;
use App\Service\MetricDataProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function fgetcsv;
use function fopen;

class CsvImportCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private MetricDataProvider $metricDataProvider;

    public function __construct(EntityManagerInterface $entityManager, MetricDataProvider $metricDataProvider, string $name = 'somecmd')
    {
        parent::__construct($name);
        $this->entityManager = $entityManager;
        $this->metricDataProvider = $metricDataProvider;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $handle = fopen('/Users/peanut/Desktop/master-thesis/filechurnresults.csv', 'r');
        $i = 0;
        while (($data = fgetcsv($handle, 400, ',')) !== FALSE) {
            $repository = $this->entityManager->getRepository(FileChurn::class);
            $filechurn = $repository->findOneBy(['project' => 3, 'file' => $data[0]]);
            if($filechurn !== null) {
                $growth = $this->metricDataProvider->getTimelineForFileAndProject($filechurn->getFile(), $filechurn->getProject())['growth'];
                $filechurn->setWordFix($data[1]);
                if($growth !== 'NaN') {
                    $filechurn->setAvgGrowth(floatval($growth));
                }
                $this->entityManager->persist($filechurn);
            }
            $i++;
            if(($i%50) == 0) {
                echo $i . "\n";
            }
            if(($i%150) == 0) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
        fclose($handle);
    }
}
