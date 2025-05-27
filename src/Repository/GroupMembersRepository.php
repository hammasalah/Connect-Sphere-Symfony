<?php

namespace App\Repository;

use App\Entity\GroupMembers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<GroupMembers>
 *
 * @method GroupMembers|null find($id, $lockMode = null, $lockVersion = null)
 * @method GroupMembers|null findOneBy(array $criteria, array $orderBy = null)
 * @method GroupMembers[]    findAll()
 * @method GroupMembers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupMembersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupMembers::class);
    }

//    /**
//     * @return GroupMembers[] Returns an array of GroupMembers objects
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

//    public function findOneBySomeField($value): ?GroupMembers
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

/**
     * Récupère toutes les demandes d'adhésion en attente pour les groupes d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur (créateur des groupes)
     * @return array Les demandes d'adhésion en attente
     */
    public function findPendingRequestsForUserGroups(int $userId): array
    {
        return $this->createQueryBuilder('gm')
            ->join('gm.group_it', 'g')
            ->join('gm.user_id', 'u')
            ->where('g.creator_id = :userId')
            ->andWhere('gm.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', GroupMembers::STATUS_PENDING)
            ->orderBy('gm.created_at', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
