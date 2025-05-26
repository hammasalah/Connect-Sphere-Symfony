<?php

namespace App\Repository;

use App\Entity\GroupFeedPosts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupFeedPosts>
 *
 * @method GroupFeedPosts|null find($id, $lockMode = null, $lockVersion = null)
 * @method GroupFeedPosts|null findOneBy(array $criteria, array $orderBy = null)
 * @method GroupFeedPosts[]    findAll()
 * @method GroupFeedPosts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupFeedPostsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupFeedPosts::class);
    }

//    /**
//     * @return GroupFeedPosts[] Returns an array of GroupFeedPosts objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?GroupFeedPosts
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
