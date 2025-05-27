<?php

namespace App\Repository;

use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Users>
 *
 * @method Users|null find($id, $lockMode = null, $lockVersion = null)
 * @method Users|null findOneBy(array $criteria, array $orderBy = null)
 * @method Users[]    findAll()
 * @method Users[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Users::class);
    }

  public function findIdByUsername(string $username): ?int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.id')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SCALAR);
        return $result['id'] ?? null;
    }

    public function findAllUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.username')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function searchByUsername(string $searchTerm): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.username')
            ->where('u.username LIKE :term')
            ->setParameter('term', '%'.$searchTerm.'%')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
 /**
     * Recherche des utilisateurs par nom d'utilisateur ou email
     *
     * @param string $searchTerm Le terme à rechercher
     * @return Users[] Returns an array of Users objects
     */
 public function findBySearch(string $searchTerm): array
    {
        return $this->createQueryBuilder('u')
            ->where('LOWER(u.username) LIKE LOWER(:search)')
            ->orWhere('LOWER(u.email) LIKE LOWER(:search)')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('u.username', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
}
