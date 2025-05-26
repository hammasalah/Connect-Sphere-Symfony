<?php

namespace App\Repository;

use App\Entity\Events;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Events::class);
    }

    public function findByOrganizer($organizer): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organizerId = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('e.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByNameDescriptionCategory(?string $searchTerm, ?int $categoryId, ?int $excludeOrganizerId = null): array
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->leftJoin('e.categoryId', 'c')
            ->addSelect('c');

        // Exclude events by organizer if specified
        if ($excludeOrganizerId !== null) {
            $queryBuilder
                ->andWhere('e.organizerId != :excludeOrgId')
                ->setParameter('excludeOrgId', $excludeOrganizerId);
        }

        // Category filter
        if ($categoryId !== null && $categoryId > 0) {
            $queryBuilder
                ->andWhere('e.categoryId = :catId')
                ->setParameter('catId', $categoryId);
        }

        // Search term filter
        $cleanSearchTerm = $searchTerm !== null ? trim($searchTerm) : null;
        if (!empty($cleanSearchTerm)) {
            $queryBuilder
                ->andWhere('LOWER(e.name) LIKE LOWER(:term) OR LOWER(e.description) LIKE LOWER(:term)')
                ->setParameter('term', '%' . $cleanSearchTerm . '%');
        }

        // Ordering
        $queryBuilder->orderBy('e.startTime', 'ASC')
                     ->addOrderBy('e.name', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }
}