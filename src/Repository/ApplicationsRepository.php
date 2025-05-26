<?php
// src/Repository/ApplicationsRepository.php
namespace App\Repository;

use App\Entity\Applications;
use App\Entity\Jobs;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApplicationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Applications::class);
    }

    /**
     * Build a new Applications entity (without persisting).
     *
     * @throws \LogicException if the user has already applied to this job
     */
    public function createApplication(Users $user, Jobs $job): Applications
{
    // Prevent duplicate application
    $existing = $this->findOneBy(['user_id' => $user, 'job_id' => $job]);
    if ($existing) {
        throw new \LogicException('You have already applied to this job.');
    }

    $application = new Applications();
    $application->setUserId($user);
    $application->setJobId($job);
    $application->setAppliedAt(new \DateTime());
    $application->setStatus('Pending');

    return $application;
}
public function findApplicationsForOrganizer(Users $organizer)
{
    return $this->createQueryBuilder('a')
        ->join('a.job_id_id', 'j')      // Match Applications->jobId property
        ->join('j.userId', 'u')     // Match Jobs->userId property
        ->where('u.id = :organizerId')
        ->setParameter('organizerId', $organizer->getId())
        ->orderBy('a.appliedAt', 'DESC')
        ->getQuery()
        ->getResult();
}
public function findApplicationsToOrganizerJobs(Users $organizer)
{
    return $this->createQueryBuilder('a')
        ->join('a.job_id_id', 'j')       // Join applications to jobs
        ->join('j.userId', 'u')      // Join jobs to their owner
        ->where('u.id = :organizerId')
        ->setParameter('organizerId', $organizer->getId())
        ->orderBy('a.appliedAt', 'DESC')
        ->getQuery()
        ->getResult();
}
    
public function findApplicationsByUser(Users $user)
{
    return $this->createQueryBuilder('a')
        ->andWhere('a.user_id_id = :user') // Match the Applications->userId property
        ->setParameter('user', $user)
        ->join('a.job_id_id', 'j') // Join with Jobs
        ->orderBy('a.appliedAt', 'DESC')
        ->getQuery()
        ->getResult();
}


public function findAllApplications(): array
{
    return $this->createQueryBuilder('a')
        ->leftJoin('a.user_id_id', 'u')
        ->leftJoin('a.job_id_id', 'j')
        ->addSelect('u', 'j')
        ->orderBy('a.appliedAt', 'DESC')
        ->getQuery()
        ->getResult();
}

}
