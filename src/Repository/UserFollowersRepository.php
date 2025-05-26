<?php

namespace App\Repository;

use App\Entity\UserFollowers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserFollowersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFollowers::class);
    }

    public function findFollowers(int $userId): array
    {
        return $this->createQueryBuilder('uf')
            ->where('uf.followed = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findFollowing(int $userId): array
    {
        return $this->createQueryBuilder('uf')
            ->where('uf.follower = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult()
        ;
    }
}