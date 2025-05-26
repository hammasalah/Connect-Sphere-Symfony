<?php

namespace App\Repository;

use App\Entity\UserMessages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserMessages>
 *
 * @method UserMessages|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserMessages|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserMessages[]    findAll()
 * @method UserMessages[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserMessagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserMessages::class);
    }

//    /**
//     * @return UserMessages[] Returns an array of UserMessages objects
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

//    public function findOneBySomeField($value): ?UserMessages
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
