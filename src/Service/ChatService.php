<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\Users;
use App\Repository\MessageRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class ChatService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UsersRepository $userRepository,
        private MessageRepository $messageRepository
    ) {}

    public function getLastMessagesWithUserInfo(Users $currentUser): array
    {
        return $this->messageRepository->findLastMessagesWithUserInfo($currentUser);
    }

    public function getLastMessages(Users $currentUser): array
    {
        return $this->messageRepository->findLastMessagesBetweenUsers($currentUser);
    }

    public function getMessagesBetweenUsers(Users $user1, Users $user2): array
    {
        return $this->messageRepository->findMessagesBetweenUsers($user1, $user2);
    }

    public function sendMessage(Users $sender, Users $receiver, string $content): Message
    {
        if (empty(trim($content))) {
            throw new InvalidArgumentException('Le contenu du message ne peut pas être vide.');
        }

        $message = new Message();
        $message->setSender($sender)
            ->setReceiver($receiver)
            ->setContent($content)
            ->setTimestamp(new \DateTime())
            ->setType("MESSAGE")
            ->setReadStatus(false);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }
}