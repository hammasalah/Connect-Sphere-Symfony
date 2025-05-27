<?php

namespace App\Repository;

use App\Entity\Users;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findLastMessagesWithUserInfo(Users $currentUser): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select('m.messageId, m.content, m.timestamp, u.id as userId, u.username, m.read_status')
            ->leftJoin('m.sender', 's')
            ->leftJoin('m.receiver', 'r')
            ->leftJoin(Users::class, 'u', 'WITH', 'u.id = CASE WHEN s.id = :currentUser THEN r.id ELSE s.id END')
            ->where('m.sender = :currentUser OR m.receiver = :currentUser')
            ->setParameter('currentUser', $currentUser)
            ->andWhere('m.timestamp = (
                SELECT MAX(m2.timestamp)
                FROM App\Entity\Message m2
                WHERE (m2.sender = :currentUser AND m2.receiver = u) OR (m2.sender = u AND m2.receiver = :currentUser)
            )')
            ->orderBy('m.timestamp', 'DESC');

        $results = $qb->getQuery()->getResult();

        return array_map(function ($row) {
            return [
                'user' => [
                    'id' => $row['userId'],
                    'username' => $row['username'],
                ],
                'message' => [
                    'id' => $row['messageId'],
                    'content' => $row['content'],
                    'timestamp' => $row['timestamp']->format('Y-m-d H:i:s'),
                    'read_status' => $row['read_status'],
                ],
            ];
        }, $results);
    }

    public function findLastMessagesBetweenUsers(Users $user): array
    {
        $qb1 = $this->createQueryBuilder('m1')
            ->select('DISTINCT CASE WHEN m1.sender = :user THEN m1.receiver ELSE m1.sender END AS userId')
            ->where('m1.sender = :user OR m1.receiver = :user')
            ->setParameter('user', $user);

        $userIds = array_column($qb1->getQuery()->getArrayResult(), 'userId');

        if (empty($userIds)) {
            return [];
        }

        $results = [];
        foreach ($userIds as $otherUserId) {
            $qb2 = $this->createQueryBuilder('m2')
                ->where('(m2.sender = :user AND m2.receiver = :otherUser) OR (m2.sender = :otherUser AND m2.receiver = :user)')
                ->setParameter('user', $user)
                ->setParameter('otherUser', $otherUserId)
                ->orderBy('m2.timestamp', 'DESC')
                ->setMaxResults(1);

            $lastMessage = $qb2->getQuery()->getOneOrNullResult();
            if ($lastMessage) {
                $results[] = $lastMessage;
            }
        }

        return $results;
    }

    public function findMessagesBetweenUsers(Users $user1, Users $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.timestamp', 'ASC')
            ->getQuery()
            ->getResult();
    }
}