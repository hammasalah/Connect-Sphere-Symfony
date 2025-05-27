<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\Users;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercurePublisherService
{
    public function __construct(private HubInterface $hub)
    {
    }

    /**
     * Publie un message sur le topic Mercure pour le destinataire.
     * Le topic utilisé est "chat/user/{receiverId}", ce qui permet au destinataire
     * de recevoir les messages en temps réel.
     *
     * @param Message $message Le message à publier
     */
    public function publishMessage(Message $message): void
    {
        $topic = "chat/user/{$message->getReceiver()->getId()}";
        $data = json_encode([
            'id' => $message->getMessageId(),
            'content' => $message->getContent(),
            'timestamp' => $message->getTimestamp()->format('Y-m-d H:i:s'),
            'type' => $message->getType(),
            'read_status' => $message->getReadStatus(),
            'senderId' => $message->getSender()->getId(),
            'receiverId' => $message->getReceiver()->getId(),
            'senderUsername' => $message->getSender()->getUsername(),
        ]);

        $update = new Update($topic, $data);
        $this->hub->publish($update);
    }

    /**
     * Publie le statut en ligne/hors ligne d'un utilisateur.
     * Le topic utilisé est "user/status", qui peut être utilisé pour
     * notifier les autres utilisateurs du changement de statut.
     *
     * @param Users $user L'utilisateur dont le statut a changé
     * @param bool $isOnline True si l'utilisateur est en ligne, false sinon
     */
    public function publishUserStatus(Users $user, bool $isOnline): void
    {
        $topic = 'user/status';
        $data = json_encode([
            'userId' => $user->getId(),
            'isOnline' => $isOnline,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
        $update = new Update($topic, $data);
        $this->hub->publish($update);
    }
}