<?php

namespace App\Controller\Points;

use App\Entity\Users;
use App\Entity\VisiteUtilisateur;
use App\Service\VisitTrackerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class VisiteUtilisateurController extends AbstractController
{
    #[Route('/points/visites', name: 'app_points_visites')]
    public function visites(
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        VisitTrackerService $visitTrackerService
    ): Response {
        $sessionUser = $session->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('app_login');
        }
        $userRepository = $entityManager->getRepository(Users::class);
        $user = $userRepository->find($sessionUser->getId());
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        // Utiliser la logique avancée
        $result = $visitTrackerService->trackVisitAdvanced($user);
        // Afficher le streak réel du jour (même après reset)
        $visitesCount = $result['serie'] ?? 0;
        return $this->render('points/index.html.twig', [
            'user' => $user,
            'visitesCount' => $visitesCount,
            'pointAddedMessage' => $result['message'] ?? null,
        ]);
    }

    #[Route('/points/visit', name: 'app_points_visit')]
    public function visit(
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        VisitTrackerService $visitTrackerService
    ): Response {
        $sessionUser = $session->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('app_login');
        }
        $userRepository = $entityManager->getRepository(Users::class);
        $user = $userRepository->find($sessionUser->getId());
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        // Utiliser la logique avancée
        $result = $visitTrackerService->trackVisitAdvanced($user);
        // Afficher le streak réel du jour (même après reset)
        $visitesCount = $result['serie'] ?? 0;
        return $this->render('points/index.html.twig', [
            'user' => $user,
            'visitesCount' => $visitesCount,
            'pointAddedMessage' => $result['message'] ?? null,
        ]);
    }
}
