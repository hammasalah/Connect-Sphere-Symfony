<?php

namespace App\Repository;

use App\Entity\UserRewards;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRewards>
 *
 * @method UserRewards|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRewards|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRewards[]    findAll()
 * @method UserRewards[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRewardsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRewards::class);
    }

//    /**
//     * @return UserRewards[] Returns an array of UserRewards objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserRewards
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
