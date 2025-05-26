<?php

namespace App\Repository;

use App\Entity\TransactionArgent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransactionArgent>
 *
 * @method TransactionArgent|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransactionArgent|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransactionArgent[]    findAll()
 * @method TransactionArgent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionArgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionArgent::class);
    }

//    /**
//     * @return TransactionArgent[] Returns an array of TransactionArgent objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TransactionArgent
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
