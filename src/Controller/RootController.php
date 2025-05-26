<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\VisiteUtilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\VisitTrackerService;

class RootController extends AbstractController
{
    #[Route('', name: 'app_root')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route('/points', name: 'app_points')]
    public function points(EntityManagerInterface $entityManager, SessionInterface $session, \App\Repository\HistoriquePointsRepository $historiquePointsRepository, VisitTrackerService $visitTrackerService): Response
    {
        $sessionUser = $session->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('app_login');
        }
        $userRepository = $entityManager->getRepository(Users::class);
        $user = $userRepository->find($sessionUser->getId());
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $result = $visitTrackerService->trackVisitAdvanced($user);
        $visitesCount = $result['serie'] ?? 0;
        // Génération dynamique des données pour le graphique
        $pointsHistory = $historiquePointsRepository->findFilteredByUser($user, null, null, 100);
        $participationRepo = $entityManager->getRepository(\App\Entity\Participation::class);
        $myParticipations = $participationRepo->findBy(['participant' => $user]);
        $now = new \DateTime();
        $labels = [];
        $pointsEarned = [];
        $pointsLost = [];
        $totalEarned = 0;
        $totalLost = 0;
        for ($i = 6; $i >= 0; $i--) {
            $date = (clone $now)->modify("-$i days");
            $label = $date->format('D d/m');
            $labels[] = $label;
            $earned = 0;
            $lost = 0;
            foreach ($pointsHistory as $entry) {
                if ($entry->getDate() && $entry->getDate()->format('Y-m-d') === $date->format('Y-m-d')) {
                    if ($entry->getType() === 'gain') {
                        $earned += $entry->getPoints();
                        $totalEarned += $entry->getPoints();
                    } elseif ($entry->getType() === 'perte') {
                        $lost += abs($entry->getPoints());
                        $totalLost += abs($entry->getPoints());
                    }
                }
            }
            $pointsEarned[] = $earned;
            $pointsLost[] = $lost;
        }
        return $this->render('points/index.html.twig', [
            'user' => $user,
            'visitesCount' => $visitesCount,
            'pointAddedMessage' => $result['message'] ?? null,
            'labels' => $labels,
            'pointsEarned' => $pointsEarned,
            'pointsLost' => $pointsLost,
            'totalEarned' => $totalEarned,
            'totalLost' => $totalLost,
            'my_participations' => $myParticipations,
        ]);
    }


    

    #[Route('/points/convert', name: 'app_convert_points')]
    public function convertPoints(EntityManagerInterface $entityManager, SessionInterface $session, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $sessionUser = $session->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('app_login');
        }
        $userRepository = $entityManager->getRepository(Users::class);
        $user = $userRepository->find($sessionUser->getId());
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        // Récupérer les paramètres de filtrage
        $search = $request->query->get('search', null);
        $date = $request->query->get('date', null);
        // Récupérer l'historique filtré
        $pointsHistory = $entityManager->getRepository(\App\Entity\HistoriquePoints::class)->findFilteredByUser($user, $search, $date, 100);
        // Récupérer l'historique des conversions de l'utilisateur
        $conversions = [];
        try {
            // Récupérer les conversions depuis la table Conversion
            $conversionRepository = $entityManager->getRepository(\App\Entity\Conversion::class);
            $conversions = $conversionRepository->findBy(
                ['userId' => $user],
                ['date' => 'DESC'],
                10
            );
            // Si aucune conversion n'est trouvée, essayer de récupérer depuis TransactionArgent comme fallback
            if (empty($conversions)) {
                $transactionRepository = $entityManager->getRepository(\App\Entity\TransactionArgent::class);
                $conversions = $transactionRepository->findBy(
                    ['user' => $user],
                    ['date' => 'DESC'],
                    10
                );
            }
        } catch (\Exception $e) {
            // Si la table n'existe pas encore, on continue avec un tableau vide
            error_log('Erreur lors de la récupération des conversions: ' . $e->getMessage());
        }
        return $this->render('points/convert_simple.html.twig', [
            'user' => $user,
            'conversions' => $conversions,
            'pointsHistory' => $pointsHistory
        ]);
    }

    // La route fortune-wheel est maintenant gérée exclusivement par RouletteController

    #[Route('/api/points/chart-data', name: 'api_points_chart_data', methods: ['GET'])]
    public function getPointsChartData(EntityManagerInterface $entityManager, SessionInterface $session, \App\Repository\HistoriquePointsRepository $historiquePointsRepository, VisitTrackerService $visitTrackerService): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $sessionUser = $session->get('user');
        if (!$sessionUser) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }
        $userRepository = $entityManager->getRepository(Users::class);
        $user = $userRepository->find($sessionUser->getId());
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }
        $result = $visitTrackerService->trackVisitAdvanced($user);
        $visitesCount = $result['serie'] ?? 0;
        $pointsHistory = $historiquePointsRepository->findFilteredByUser($user, null, null, 10);
        $now = new \DateTime();
        $labels = [];
        $pointsEarned = [];
        $pointsLost = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = (clone $now)->modify("-$i days");
            $label = $date->format('D d/m');
            $labels[] = $label;
            $earned = 0;
            $lost = 0;
            foreach ($pointsHistory as $entry) {
                if ($entry->getDate() && $entry->getDate()->format('Y-m-d') === $date->format('Y-m-d')) {
                    if ($entry->getType() === 'gain') {
                        $earned += $entry->getPoints();
                    } elseif ($entry->getType() === 'perte') {
                        $lost += abs($entry->getPoints());
                    }
                }
            }
            $pointsEarned[] = $earned;
            $pointsLost[] = $lost;
        }
        return $this->json([
            'labels' => $labels,
            'pointsEarned' => $pointsEarned,
            'pointsLost' => $pointsLost,
            'visitesCount' => $visitesCount,
        ]);
    }
}
