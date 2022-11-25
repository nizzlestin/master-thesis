<?php

namespace App\Command;

use App\Entity\FileChurn;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function fgetcsv;
use function fopen;

class CsvImportCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, string $name = 'somecmd')
    {
        parent::__construct($name);
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $row = 1;
        $handle = fopen('/Users/peanut/Desktop/master-thesis/filechurnresults.csv', 'r');;
        while (($data = fgetcsv($handle, 400, ',')) !== FALSE) {
            $repository = $this->entityManager->getRepository(FileChurn::class);
            $filechurn = $repository->findOneBy(['project' => 20, 'file' => $data[0]]);
            if($filechurn !== null) {
                $filechurn->setWordFix($data[1]);
                $this->entityManager->persist($filechurn);
            }
        }
        $this->entityManager->flush();
        fclose($handle);
    }
}
