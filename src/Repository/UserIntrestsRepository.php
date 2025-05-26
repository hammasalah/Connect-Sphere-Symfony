<?php

namespace App\Repository;

use App\Entity\UserIntrests;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserIntrests>
 *
 * @method UserIntrests|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserIntrests|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserIntrests[]    findAll()
 * @method UserIntrests[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserIntrestsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserIntrests::class);
    }

//    /**
//     * @return UserIntrests[] Returns an array of UserIntrests objects
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

//    public function findOneBySomeField($value): ?UserIntrests
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
