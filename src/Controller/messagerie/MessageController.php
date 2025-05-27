<?php
// src/Controller/MessageController.php

namespace App\Controller\messagerie;
use App\Entity\Users;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MessageController extends AbstractController
{
    #[Route('/api/message/save', name: 'api_message_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        /** @var Users $sender */
        $sender = $this->getUser(); // Récupère l'utilisateur connecté
    
        if (!$sender) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }
    
        $receiver = $em->getRepository(\App\Entity\Users::class)->find($data['to']);
        if (!$receiver) {
            return new JsonResponse(['error' => 'Receiver not found'], 400);
        }
    
        $message = new \App\Entity\Message();
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setContent($data['message']);
        $message->setTimestamp(new \DateTimeImmutable());
    
        $em->persist($message);
        $em->flush();
    
        return new JsonResponse(['status' => 'saved']);
    }
    
    

    #[Route('/api/message/history/{userId}', name: 'api_message_history')]
public function history(int $userId, EntityManagerInterface $em): JsonResponse
{
/** @var Users $user */
$user = $this->getUser();
    if (!$user instanceof Users) {
        return $this->json(['error' => 'Unauthorized'], 401);
    }

    $otherUser = $em->getRepository(Users::class)->find($userId);
    if (!$otherUser) {
        return $this->json(['error' => 'User not found'], 404);
    }

    $messages = $em->getRepository(Message::class)->createQueryBuilder('m')
        ->where('(m.sender = :me AND m.receiver = :them) OR (m.sender = :them AND m.receiver = :me)')
        ->setParameter('me', $user)
        ->setParameter('them', $otherUser)
        ->orderBy('m.createdAt', 'ASC')
        ->getQuery()
        ->getArrayResult();

    return $this->json($messages);
}

}