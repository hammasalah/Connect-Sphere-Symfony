<?php
// src/Controller/HomeController.php

namespace App\Controller\home; // Gardez le même namespace ou créez-en un nouveau (ex: App\Controller\Main)

use App\Repository\EventsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class HomeController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    // --- NOUVELLE ROUTE pour la page d'accueil post-login ---
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(EventsRepository $eventsRepository): Response
    {
        $this->logger->info('Home Page: Request received.');
        $eventsJsonForMap = '[]'; // JSON par défaut

        try {
            // 1. Récupérer les événements (pour la carte)
            $allEvents = $eventsRepository->findBy([], ['startTime' => 'DESC'], 50);

            // 2. Préparer les données JSON pour la carte
            $eventsDataForMap = [];
            foreach ($allEvents as $event) {
                $locationText = $event->getLocation();
                if (!empty($locationText)) {
                    $eventsDataForMap[] = [ /* ... données pour la carte ... */
                        'id' => $event->getId(), 'name' => $event->getName() ?? '?',
                        'description' => $event->getDescription() ?? '', 'location' => $locationText,
                        'start_time' => $event->getStartTime() ? date('d/m/Y H:i', strtotime($event->getStartTime())) : 'N/A',
                        'category' => $event->getCategoryId() ? $event->getCategoryId()->getName() : 'N/A',
                    ];
                }
            }
            $this->logger->info(sprintf('Home Page: Prepared %d events for map JSON.', count($eventsDataForMap)));

            // 3. Encoder en JSON
            $_eventsJson = json_encode($eventsDataForMap);
            if ($_eventsJson !== false) { $eventsJsonForMap = $_eventsJson; }
            else { $this->logger->error('Home Page: JSON encoding failed.'); }

        } catch (\Exception $e) {
            $this->logger->error('Home Page: Error loading data.', ['exception' => $e]);
            $this->addFlash('error', 'Could not load map data.');
            $eventsJsonForMap = '[]';
        }

        // 4. Rendre un NOUVEAU template dédié pour cette page
        return $this->render('home/home.html.twig', [ // Nom de template suggéré
            'eventsJsonForMap' => $eventsJsonForMap,
]);

    }
}