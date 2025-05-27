<?php
// src/Controller/events/EventsController.php

namespace App\Controller\events;
use App\Form\ReviewType; // Importer le type de formulaire ReviewType
use App\Entity\Events;
use App\Entity\Reviews;
use App\Entity\Users;
use App\Form\EventsType; // Assuming you have this for event creation
use App\Repository\EventsRepository;
use App\Repository\CategoryRepository;
use App\Repository\ParticipationRepository; // For ORM check
// OR use Doctrine\DBAL\Connection; // If you prefer DBAL check for participation
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile; // For event creation
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface; // For getting current user

class EventsController extends AbstractController
{
    private LoggerInterface $logger;
    private ParticipationRepository $participationRepository; // Injected for ORM check
    // private Connection $connection; // Uncomment and inject if using DBAL check
        private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        ParticipationRepository $participationRepository, // Inject
        // Connection $connection // Uncomment if using DBAL
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->participationRepository = $participationRepository;
        // $this->connection = $connection; // Uncomment if using DBAL
        $this->entityManager = $entityManager;
    }

     #[Route('/events', name: 'app_events', methods: ['GET'])]
    public function index(
        Request $request,
        EventsRepository $eventsRepository,
        CategoryRepository $categoryRepository,
        SessionInterface $session
    ): Response {
        $upcomingEventsWithStatus = [];
        $pastEventsWithStatus = [];

        try {
            // Get current user from session
            $userSession = $session->get('user');
            $currentUser = null;
            $excludeOrganizerId = null;
            if ($currentUser instanceof Users) {
                $excludeOrganizerId = $currentUser->getId();
            }
            if ($userSession) {
                $currentUser = $this->entityManager->getRepository(Users::class)->find($userSession->getId());
            }

            $searchTerm = $request->query->get('search');
            $categoryId = $request->query->get('category');

            // Get upcoming events
            $upcomingEvents = $eventsRepository->findByNameDescriptionCategory($searchTerm, $categoryId, $excludeOrganizerId);

            // Get past events
            $pastEvents = $eventsRepository->findPastEvents();

            // Get all categories for filter
            $categories = $categoryRepository->findBy([], ['name' => 'ASC']);

            // Process upcoming events
            foreach ($upcomingEvents as $event) {
                $hasJoined = false;
                if ($currentUser) {
                    $participation = $this->participationRepository->findOneBy([
                        'participant' => $currentUser,
                        'event' => $event
                    ]);
                    if ($participation) {
                        $hasJoined = true;
                    }
                }
                $upcomingEventsWithStatus[] = [
                    'entity' => $event,
                    'hasJoined' => $hasJoined
                ];
            }

            // Process past events
            foreach ($pastEvents as $event) {
                $hasJoined = false;
                if ($currentUser) {
                    $participation = $this->participationRepository->findOneBy([
                        'participant' => $currentUser,
                        'event' => $event
                    ]);
                    if ($participation) {
                        $hasJoined = true;
                    }
                }
                $pastEventsWithStatus[] = [
                    'entity' => $event,
                    'hasJoined' => $hasJoined
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('Error fetching events: ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('error', 'An error occurred while retrieving event data.');
        }

        return $this->render('events/events.html.twig', [
            'upcomingEventsWithStatus' => $upcomingEventsWithStatus,
            'pastEventsWithStatus' => $pastEventsWithStatus,
            'categories' => $categories,
        ]);
    }

    #[Route('/events/add', name: 'app_event_add_page', methods: ['GET'])]
    public function addEventPage(Request $request): Response
    {
        $session = $request->getSession();
        $currentUser = $session->get('user');
        
        if (!$currentUser instanceof Users) {
            $this->addFlash('error', 'You must be logged in to create events.');
            return $this->redirectToRoute('app_login');
        }

        $event = new Events();
        $form = $this->createForm(EventsType::class, $event, [
             'action' => $this->generateUrl('app_event_new'),
             'method' => 'POST',
        ]);

        return $this->render('events/add_event.html.twig', [
            'create_event_form' => $form->createView(),
        ]);
    }

    #[Route('/events/new', name: 'app_event_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, SessionInterface $session): Response // Added SessionInterface
    {
        // $session = $request->getSession(); // Already injected
        $currentUser = $session->get('user');
        
        if (!$currentUser instanceof Users) {
            $this->addFlash('error', 'You must be logged in to create events.');
            return $this->redirectToRoute('app_login');
        }

        $event = new Events();
        $form = $this->createForm(EventsType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    if (!$currentUser->getId()) {
                        throw new \Exception('Invalid user ID in session for event creation.');
                    }
                    $userReference = $em->getReference(Users::class, $currentUser->getId());
                    $event->setOrganizerId($userReference);

                    $startTime = $form->get('startTime')->getData();
                    $endTime = $form->get('endTime')->getData();
                    if ($startTime instanceof \DateTimeInterface && $endTime instanceof \DateTimeInterface) {
                        $event->setStartTime($startTime->format('Y-m-d H:i:s'));
                        $event->setEndTime($endTime->format('Y-m-d H:i:s'));
                    } else {
                        // Handle error or set default if dates are not DateTimeInterface (should be due to form type)
                        $this->logger->error('Invalid date format received from event form.');
                        $this->addFlash('error', 'Invalid date format submitted.');
                        // Re-render form or throw exception
                         return $this->render('events/add_event.html.twig', [
                            'create_event_form' => $form->createView(),
                        ]);
                    }


                    /** @var UploadedFile|null $imageFile */
                    $imageFile = $form->get('image')->getData();
                    if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                        $event->setImage(base64_encode(file_get_contents($imageFile->getPathname())));
                    } else {
                        $event->setImage(''); // Or handle as error if image is required
                    }

                    if ($event->getPoints() === null) { // Check for null explicitly
                        $event->setPoints(0);
                    }

                    $em->persist($event);
                    $em->flush();

                    $this->addFlash('success', 'Event created successfully!');
                    return $this->redirectToRoute('app_events');

                } catch (\Exception $e) {
                    $this->logger->error('Event creation failed: ' . $e->getMessage(), ['exception' => $e]);
                    $this->addFlash('error', 'Error creating event: ' . $e->getMessage());
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('events/add_event.html.twig', [
        'create_event_form' => $form->createView(),
    ]);
}
#[Route('/events/{id}/review', name: 'app_event_review', methods: ['GET', 'POST'])]
    public function addReview(
        int $id,
        Request $request,
        EventsRepository $eventsRepository,
        SessionInterface $session
    ): Response {
        $event = $eventsRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        // Get user from session and database
        $userSession = $session->get('user');
        if (!$userSession) {
            $this->addFlash('error', 'Vous devez être connecté pour donner votre avis.');
            return $this->redirectToRoute('app_login');
        }

        $currentUser = $this->entityManager->getRepository(Users::class)->find($userSession->getId());
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'événement est passé
        $now = new \DateTime();
        $endTime = new \DateTime($event->getEndTime());
        if ($endTime > $now) {
            $this->addFlash('error', 'Vous ne pouvez donner votre avis que sur les événements passés.');
            return $this->redirectToRoute('app_events');
        }

        // Vérifier si l'utilisateur a participé à l'événement
        $hasParticipated = $this->participationRepository->findOneBy([
            'participant' => $currentUser,
            'event' => $event
        ]);

        if (!$hasParticipated) {
            $this->addFlash('error', 'Vous ne pouvez donner votre avis que sur les événements auxquels vous avez participé.');
            return $this->redirectToRoute('app_events');
        }

        // Vérifier si l'utilisateur a déjà donné son avis
        $existingReview = $this->entityManager->getRepository(Reviews::class)->findOneBy([
            'participant' => $currentUser,
            'event' => $event
        ]);

        if ($existingReview) {
            $this->addFlash('error', 'You have already submitted your review for this event.');
            return $this->redirectToRoute('app_events');
        }

        $review = new Reviews();
        $review->setEvent($event);
        $review->setParticipant($currentUser);
        $review->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($review);
                $this->entityManager->flush();

                $this->addFlash('success', 'Your feedback has been successfully recorded!');
                return $this->redirectToRoute('app_events');
            } catch (\Exception $e) {
                $this->logger->error('Error saving review: ' . $e->getMessage());
                $this->addFlash('error', 'There was an error submitting your review.');
            }
        }

        return $this->render('events/review.html.twig', [
            'event' => $event,
            'form' => $form->createView()
        ]);
    }

    #[Route('/events/{id}/reviews', name: 'app_event_reviews', methods: ['GET'])]
    public function showReviews(int $id, EventsRepository $eventsRepository): Response
    {
        $event = $eventsRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        return $this->render('events/reviews.html.twig', [
            'event' => $event,
            'reviews' => $event->getReviews()
        ]);
    }
}
