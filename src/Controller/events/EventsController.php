<?php
// src/Controller/events/EventsController.php

namespace App\Controller\events;

use App\Entity\Events;
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

    public function __construct(
        LoggerInterface $logger,
        ParticipationRepository $participationRepository // Inject
        // Connection $connection // Uncomment if using DBAL
    ) {
        $this->logger = $logger;
        $this->participationRepository = $participationRepository;
        // $this->connection = $connection; // Uncomment if using DBAL
    }

    #[Route('/events', name: 'app_events', methods: ['GET'])]
    public function index(
        Request $request,
        EventsRepository $eventsRepository,
        CategoryRepository $categoryRepository,
        SessionInterface $session // Inject SessionInterface
    ): Response {
        /** @var Users|null $currentUser */
        $currentUser = $session->get('user');
        
        $excludeOrganizerId = null;
        $currentUserId = null; // To store the ID for checking participation
        if ($currentUser instanceof Users) {
            $excludeOrganizerId = $currentUser->getId();
            $currentUserId = $currentUser->getId();
        }

        // Get filters from URL
        $searchTerm = $request->query->get('search');
        $categoryIdParam = $request->query->get('category');
        $categoryId = null;
        if (!empty($categoryIdParam) && ctype_digit((string)$categoryIdParam)) {
            $categoryId = (int)$categoryIdParam;
        }

        $eventsWithStatus = []; // Array to hold event entities and their participation status
        $categories = [];
        try {
            $eventsList = $eventsRepository->findByNameDescriptionCategory($searchTerm, $categoryId, $excludeOrganizerId);
            $categories = $categoryRepository->findBy([], ['name' => 'ASC']);

            // For each event, check if the current user has participated
            foreach ($eventsList as $event) {
                $hasJoined = false;
                if ($currentUserId) {
                    // Option A: Using ORM (recommended if Participation entity is correctly mapped)
                    $participation = $this->participationRepository->findOneBy([
                        'participant' => $currentUserId, // Assumes 'participant' property in Participation entity links to User ID
                        'event' => $event->getId()      // Assumes 'event' property in Participation entity links to Event ID
                    ]);
                    if ($participation) {
                        $hasJoined = true;
                    }

                    // Option B: Using DBAL (if your Participation entity is not fully ORM-mapped for this query)
                    /*
                    $sql = "SELECT COUNT(*) FROM participation WHERE event_id = :eventId AND participant_id = :userId";
                    $stmt = $this->connection->prepare($sql);
                    $result = $stmt->executeQuery(['eventId' => $event->getId(), 'userId' => $currentUserId]);
                    if ($result->fetchOne() > 0) {
                        $hasJoined = true;
                    }
                    */
                }
                $eventsWithStatus[] = [
                    'entity' => $event,
                    'hasJoined' => $hasJoined
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('Error fetching events, categories, or participation status: ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('error', 'An error occurred while retrieving event data.');
        }

        return $this->render('events/events.html.twig', [
            'eventsWithStatus' => $eventsWithStatus, // Pass the enriched array to Twig
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
}
