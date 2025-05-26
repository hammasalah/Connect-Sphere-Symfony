<?php

namespace App\Repository;

use App\Entity\FeedPosts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedPosts>
 *
 * @method FeedPosts|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedPosts|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedPosts[]    findAll()
 * @method FeedPosts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedPostsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedPosts::class);
    }

//    /**
//     * @return FeedPosts[] Returns an array of FeedPosts objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FeedPosts
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
