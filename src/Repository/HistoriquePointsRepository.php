<?php

namespace App\Repository;

use App\Entity\HistoriquePoints;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HistoriquePointsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriquePoints::class);
    }

    public function findFilteredByUser(Users $user, ?string $search = null, ?string $date = null, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('h')
            ->where('h.user = :user')
            ->setParameter('user', $user)
            ->orderBy('h.date', 'DESC')
            ->setMaxResults($limit);
        if ($search) {
            $qb->andWhere('h.raison LIKE :search OR h.type LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($date) {
            $qb->andWhere('DATE(h.date) = :date')
               ->setParameter('date', $date);
        }
        return $qb->getQuery()->getResult();
    }
}