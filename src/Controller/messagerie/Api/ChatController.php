<?php

namespace App\Controller\messagerie\Api;

use App\Entity\Message;
use App\Entity\Users;
use App\Repository\MessageRepository;
use App\Repository\UsersRepository;
use App\Service\MercurePublisherService;
use App\Service\ChatService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

#[Route('/messagerie')]
class ChatController extends AbstractController
{
    #[Route('/app_messagerie', name: 'app_messagerie_index', methods: ['GET'])]
    public function index(Request $request, UsersRepository $usersRepo): Response
    {
        $session = $request->getSession();
        $user = $session->get('user');

        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        $allUsers = $usersRepo->findAllUsers();

        $jwtSecret = $this->getParameter('mercure_jwt_secret');
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($jwtSecret));
        $jwt = $config->builder()
            ->withClaim('mercure', [
                'subscribe' => ["chat/user/{$user->getId()}"],
            ])
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        return $this->render('messagerie/messagerie/index.html.twig', [
            'userId' => $user->getId(),
            'mercure_hub_url' => $this->getParameter('mercure_hub_url'),
            'mercure_jwt' => $jwt,
            'users' => $allUsers,
        ]);
    }

    #[Route('/conversations', name: 'api_chat_conversations', methods: ['GET'])]
    public function getConversations(Request $request, ChatService $chatService): JsonResponse
    {
        $session = $request->getSession();
        $currentUser = $session->get('user');
        if (!$currentUser instanceof Users) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        $messages = $chatService->getLastMessagesWithUserInfo($currentUser);

        return $this->json(['conversations' => $messages]);
    }

    #[Route('/messages/get/{userId}', name: 'api_chat_get_messages', methods: ['GET'])]
    public function getMessages(int $userId, UsersRepository $userRepository, MessageRepository $messageRepository, Request $request): JsonResponse
    {
        $session = $request->getSession();
        $currentUser = $session->get('user');
        if (!$currentUser instanceof Users) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        $otherUser = $userRepository->find($userId);
        if (!$otherUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $messages = $messageRepository->findMessagesBetweenUsers($currentUser, $otherUser);

        $formattedMessages = [];
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'id' => $message->getMessageId(),
                'content' => $message->getContent(),
                'timestamp' => $message->getTimestamp()->format('Y-m-d H:i:s'),
                'type' => $message->getType(),
                'read_status' => $message->getReadStatus(),
                'isSentByMe' => $message->isSentBy($currentUser),
            ];
        }

        return $this->json([
            'messages' => $formattedMessages,
            'withUser' => [
                'id' => $otherUser->getId(),
                'username' => $otherUser->getUsername(),
            ],
        ]);
    }

    #[Route('/messages/send/{userId}', name: 'api_chat_send_message', methods: ['POST'])]
    public function sendMessage(
        int $userId,
        Request $request,
        UsersRepository $userRepository,
        ChatService $chatService,
        MercurePublisherService $mercurePublisher
    ): JsonResponse {
        $session = $request->getSession();
        $currentUser = $session->get('user');
        if (!$currentUser instanceof Users) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        $receiver = $userRepository->find($userId);
        if (!$receiver) {
            return $this->json(['error' => 'Destinataire non trouvé'], 404);
        }

        $content = $request->request->get('content') ?? json_decode($request->getContent(), true)['content'] ?? null;
        if (!$content) {
            return $this->json(['error' => 'Contenu du message manquant'], 400);
        }

        try {
            $message = $chatService->sendMessage($currentUser, $receiver, $content);
            $mercurePublisher->publishMessage($message);
            return $this->json([
                'message' => [
                    'id' => $message->getMessageId(),
                    'content' => $message->getContent(),
                    'timestamp' => $message->getTimestamp()->format('Y-m-d H:i:s'),
                    'type' => $message->getType(),
                    'read_status' => $message->getReadStatus(),
                    'isSentByMe' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'envoi du message'], 500);
        }
    }

    #[Route('/users/search', name: 'api_users_search', methods: ['GET'])]
    public function searchUsers(Request $request, UsersRepository $usersRepo): JsonResponse
    {
        $session = $request->getSession();
        $currentUser = $session->get('user');
        if (!$currentUser instanceof Users) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        $searchTerm = $request->query->get('term');
        if (!$searchTerm) {
            return $this->json(['error' => 'Terme de recherche manquant'], 400);
        }

        $results = $usersRepo->searchByUsername($searchTerm);
        $filteredResults = array_filter($results, fn($user) => $user['id'] !== $currentUser->getId());

        return $this->json(['users' => array_values($filteredResults)]);
    }
}