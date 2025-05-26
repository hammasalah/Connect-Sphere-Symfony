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
    public function points(EntityManagerInterface $entityManager, SessionInterface $session, VisitTrackerService $visitTrackerService): Response
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
        return $this->render('points/index.html.twig', [
            'user' => $user,
            'visitesCount' => $visitesCount,
            'pointAddedMessage' => $result['message'] ?? null,
        ]);
    }


    

    #[Route('/points/convert', name: 'app_convert_points')]
    public function convertPoints(EntityManagerInterface $entityManager, SessionInterface $session): Response
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
        
        return $this->render('points/convert.html.twig', [
            'user' => $user,
            'conversions' => $conversions
        ]);
    }

    // La route fortune-wheel est maintenant gérée exclusivement par RouletteController
}
