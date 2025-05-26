<?php

namespace App\Repository;

use App\Entity\Jobs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Users;

/**
 * @extends ServiceEntityRepository<Jobs>
 */
class JobsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Jobs::class);
    }

    /**
     * @return Jobs[] Returns all jobs sorted alphabetically by title
     */
    public function findAllSortedByTitle(): array
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.jobTitle', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findAllExceptUser($user): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.userId != :user')  // use 'userId' here
            ->setParameter('user', $user)
            ->orderBy('j.jobTitle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Jobs[] //Returns all jobs with optional sorting
     */
    public function findAllWithSorting(string $sortField = 'jobTitle', string $sortDirection = 'ASC'): array
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.' . $sortField, $sortDirection)
            ->getQuery()
            ->getResult();
    }

    public function findByUser(Users $user): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.userId = :user')
            ->setParameter('user', $user)
            ->orderBy('j.jobTitle', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function delete(Jobs $job): void
{
    $this->_em->remove($job);
    $this->_em->flush();
}

}

// In JobsRepository.php

// // Find jobs by event
// public function findByEvent(Events $event): array
// {
//     return $this->createQueryBuilder('j')
//         ->andWhere('j.eventId = :event')
//         ->setParameter('event', $event)
//         ->orderBy('j.jobTitle', 'ASC')
//         ->getQuery()
//         ->getResult();
// }

// // Find jobs with salary range
// public function findBySalaryRange(int $min, int $max): array
// {
//     return $this->createQueryBuilder('j')
//         ->andWhere('j.minSalary >= :min')
//         ->andWhere('j.maxSalary <= :max')
//         ->setParameter('min', $min)
//         ->setParameter('max', $max)
//         ->orderBy('j.jobTitle', 'ASC')
//         ->getQuery()
//         ->getResult();
// }

// // Search jobs by title or description
// public function searchJobs(string $query): array
// {
//     return $this->createQueryBuilder('j')
//         ->andWhere('j.jobTitle LIKE :query OR j.jobDescreption LIKE :query')
//         ->setParameter('query', '%'.$query.'%')
//         ->orderBy('j.jobTitle', 'ASC')
//         ->getQuery()
//         ->getResult();
// }
//}

// src/Repository/JobsRepository.php
// namespace App\Repository;

// use App\Entity\Jobs;
// use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
// use Doctrine\Persistence\ManagerRegistry;
// use Psr\Log\LoggerInterface;

// class JobsRepository extends ServiceEntityRepository
// {
//     private $logger;

//     public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
//     {
//         parent::__construct($registry, Jobs::class);
//         $this->logger = $logger;
//     }

//     public function findAllSortedByTitle(): array
//     {
//         $query = $this->createQueryBuilder('j')
//             ->orderBy('j.jobTitle', 'ASC')
//             ->getQuery();

//         // Log the generated SQL
//         $this->logger->debug('Jobs Query: '.$query->getSQL());

//         return $query->getResult();
//     }
