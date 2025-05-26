<?php

namespace App\Repository;

use App\Entity\WorkerRaitings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkerRaitings>
 *
 * @method WorkerRaitings|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkerRaitings|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkerRaitings[]    findAll()
 * @method WorkerRaitings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkerRaitingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkerRaitings::class);
    }

//    /**
//     * @return WorkerRaitings[] Returns an array of WorkerRaitings objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?WorkerRaitings
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
