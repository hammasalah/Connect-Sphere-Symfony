<?php
// src/Controller/participation/ParticipationController.php

namespace App\Controller\participation;

use App\Entity\Events;
use App\Entity\Participation; // Use the corrected Participation entity
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request; // Kept for future use if needed
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ParticipationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/events/participate/{eventId}', name: 'app_event_participate', methods: ['POST'])]
    public function participate(int $eventId, SessionInterface $session): JsonResponse
    {
        try {
            /** @var Users|null $currentUserSessionData */
            $currentUserSessionData = $session->get('user');

            if (!$currentUserSessionData || !($currentUserSessionData instanceof Users)) {
                $this->logger->warning('Participation attempt by unauthenticated or invalid user in session.');
                return new JsonResponse([
                    'success' => false,
                    'message' => 'You must be logged in to participate.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $userId = $currentUserSessionData->getId();
            if (!$userId) {
                $this->logger->error('User object in session has no ID.', ['session_user_obj_details' => is_object($currentUserSessionData) ? get_class($currentUserSessionData) : gettype($currentUserSessionData)]);
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid user session data.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Fetch managed entities
            $user = $this->entityManager->getRepository(Users::class)->find($userId);
            if (!$user) {
                $this->logger->error(sprintf('User with ID %d from session not found in database.', $userId));
                return new JsonResponse(['success' => false, 'message' => 'User not found.'], Response::HTTP_NOT_FOUND);
            }

            $event = $this->entityManager->getRepository(Events::class)->find($eventId);
            if (!$event) {
                $this->logger->warning(sprintf('Participation attempt for non-existent event ID: %d', $eventId));
                return new JsonResponse(['success' => false, 'message' => 'Event not found.'], Response::HTTP_NOT_FOUND);
            }

            // Check if participation already exists using the ORM
            $existingParticipation = $this->entityManager->getRepository(Participation::class)->findOneBy([
                'participant' => $user,
                'event' => $event
            ]);

            if ($existingParticipation) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'You are already participating in this event.'
                ], Response::HTTP_CONFLICT);
            }

            // Create and persist new Participation entity
            $newParticipation = new Participation();
            $newParticipation->setParticipant($user);
            $newParticipation->setEvent($event);
            // If you added $participatedAt to your Entity and it's not set by constructor/DB default:
            // $newParticipation->setParticipatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($newParticipation);

            // Award points
            $pointsToAward = 10; // Define how many points to award
            $currentPoints = $user->getPoints() ?? 0;
            $user->setPoints($currentPoints + $pointsToAward);
            // $this->entityManager->persist($user); // Not strictly needed if $user is already managed by EM,
                                                // as changes to managed entities are automatically detected by flush.

            $this->entityManager->flush(); // Persist new participation and user points update in one transaction

            $this->logger->info(sprintf('User ID %d (username: %s) joined event ID %d (name: %s) and earned %d points. New total points: %d',
                $user->getId(), $user->getUsername(), $event->getId(), $event->getName(), $pointsToAward, $user->getPoints()));

            return new JsonResponse([
                'success' => true,
                'message' => 'Successfully joined the event and earned ' . $pointsToAward . ' points!'
            ]);

        } catch (\Exception $e) {
            $this->logger->error(
                'Participation error: ' . $e->getMessage(),
                ['exception_trace' => $e->getTraceAsString()] // Provides full stack trace in logs
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while trying to join the event. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
}
}
