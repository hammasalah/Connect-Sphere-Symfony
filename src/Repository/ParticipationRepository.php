<?php

namespace App\Repository;

use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Events;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Participation>
 *
 * @method Participation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Participation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Participation[]    findAll()
 * @method Participation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipationRepository extends ServiceEntityRepository
{
     private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Participation::class);
        $this->entityManager = $entityManager;
    }

 public function getGenderDistributionForEvent(Events $event): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('participant.gender AS gender, COUNT(participant.id) AS genderCount')
            ->from(Participation::class, 'p_event')
            ->innerJoin('p_event.participant', 'participant')
            ->where('p_event.event = :event')
            ->andWhere('participant.gender IS NOT NULL') // Only consider participants with a gender
            ->groupBy('participant.gender')
            ->setParameter('event', $event);

        $results = $qb->getQuery()->getResult();

        // Ensure we always have entries for male and female, even if count is 0
        $genders = ['male' => 1, 'female' => 0]; 
        foreach ($results as $result) {
            if (isset($result['gender']) && array_key_exists(strtolower($result['gender']), $genders)) {
                $genders[strtolower($result['gender'])] = (int)$result['genderCount'];
            }
        }
        // Transform to desired array structure for the chart
        $output = [];
        foreach($genders as $genderKey => $count) {
            $output[] = ['gender' => ucfirst($genderKey), 'count' => $count];
        }
        return $output;
    }

public function findParticipantsOfMyEvents($organizer): array
{
    return $this->createQueryBuilder('p')
        ->join('p.event', 'e')
        ->where('e.organizerId = :organizer')
        ->setParameter('organizer', $organizer)
        ->getQuery()
        ->getResult();
}




}
