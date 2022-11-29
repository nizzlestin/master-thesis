<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function add(Project $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Project $entity, bool $flush = false): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $deleteStatisticStmt = $conn->prepare("DELETE FROM statistic WHERE project_id = :projectId");
        $deleteStatisticStmt->executeStatement(['projectId' => $entity->getId()]);
        $deleteStatisticStmt = $conn->prepare("DELETE FROM evaluation_stats WHERE project_id = :projectId");
        $deleteStatisticStmt->executeStatement(['projectId' => $entity->getId()]);
        $deleteStatisticStmt = $conn->prepare("DELETE FROM file_churn WHERE project_id = :projectId");
        $deleteStatisticStmt->executeStatement(['projectId' => $entity->getId()]);
        $deleteStatisticFileStmt = $conn->prepare("DELETE FROM statistic_file WHERE project_id = :projectId");
        $deleteStatisticFileStmt->executeStatement(['projectId' => $entity->getId()]);
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Project[] Returns an array of Project objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Project
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
