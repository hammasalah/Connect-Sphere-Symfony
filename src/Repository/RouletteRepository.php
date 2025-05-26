<?php

namespace App\Repository;

use App\Entity\Roulette;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Roulette>
 *
 * @method Roulette|null find($id, $lockMode = null, $lockVersion = null)
 * @method Roulette|null findOneBy(array $criteria, array $orderBy = null)
 * @method Roulette[]    findAll()
 * @method Roulette[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouletteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Roulette::class);
    }

    
public function findByUser(Users $user): array
{
    return $this->createQueryBuilder('r')
        ->andWhere('r.user = :user')
        ->setParameter('user', $user)
        ->orderBy('r.date', 'DESC')
        ->getQuery()
        ->getResult();
}


//    /**
//     * @return Roulette[] Returns an array of Roulette objects
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

//    public function findOneBySomeField($value): ?Roulette
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
