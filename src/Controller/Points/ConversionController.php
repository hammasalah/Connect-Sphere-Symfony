<?php

namespace App\Controller\Points;

use App\Entity\Users;
use App\Entity\TransactionArgent;
use App\Entity\HistoriquePoints;
use App\Service\PointsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/points')]
class ConversionController extends AbstractController
{
    private $entityManager;
    private $requestStack;
    private $pointsService;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        PointsService $pointsService
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->pointsService = $pointsService;
    }

    #[Route('/convert', name: 'app_points_convert')]
    public function index(): Response
    {
        // Récupérer l'utilisateur connecté depuis la session
        $session = $this->requestStack->getSession();
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'utilisateur complet depuis la base de données pour les points mis à jour
        $userRepository = $this->entityManager->getRepository(Users::class);
        $user = $userRepository->find($user->getId());

        // Récupérer l'historique des conversions de l'utilisateur
        $conversions = [];
        try {
            $transactionRepository = $this->entityManager->getRepository(TransactionArgent::class);
            $conversions = $transactionRepository->findBy(
                ['user' => $user],
                ['date' => 'DESC'],
                10
            );
        } catch (\Exception $e) {
            // Si la table n'existe pas encore, on continue avec un tableau vide
            error_log('Erreur lors de la récupération des conversions: ' . $e->getMessage());
        }

        return $this->render('points/convert.html.twig', [
            'user' => $user, // Utilisateur avec points mis à jour
            'conversions' => $conversions
        ]);
    }

    #[Route('/convert/process', name: 'app_process_conversion', methods: ['POST'])]
    public function processConversion(Request $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur connecté depuis la session
            $session = $this->requestStack->getSession();
            $sessionUser = $session->get('user');
            if (!$sessionUser) {
                return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
            }

            // Récupérer l'utilisateur complet depuis la base de données
            $userRepository = $this->entityManager->getRepository(Users::class);
            $user = $userRepository->find($sessionUser->getId());

            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            // Récupérer les données de la requête
            $points = (int)$request->request->get('points');
            $devise = $request->request->get('devise');

            // Valider les données
            if ($points <= 0 || $points % 100 !== 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Le nombre de points doit être un multiple positif de 100.'
                ], 400);
            }

            // Vérifier que l'utilisateur a suffisamment de points
            if ($user->getPoints() < $points) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous n\'avez pas assez de points pour cette conversion.'
                ], 400);
            }

            // Effectuer la conversion
            $result = $this->convertPointsToMoney($user, $points, $devise);

            if (!$result['success']) {
                return new JsonResponse($result, 400);
            }

            // Mettre à jour l'utilisateur en session
            $session->set('user', $user);

            // Ajouter les informations nécessaires pour mettre à jour l'interface
            $responseData = array_merge($result, [
                'newPoints' => $user->getPoints(),
                'newArgent' => $user->getArgent()
            ]);
            
            return new JsonResponse($responseData);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convertit des points en argent
     *
     * @param Users $user L'utilisateur
     * @param int $points Le nombre de points à convertir
     * @param string $devise La devise (TND, EUR, USD)
     * @return array Résultat de la conversion avec statut, message et montant
     */
    private function convertPointsToMoney(Users $user, int $points, string $devise): array
    {
        try {
            // Vérifier que l'utilisateur a au moins 100 points
            $currentPoints = $user->getPoints() ?? 0;
            if ($currentPoints < 100) {
                return [
                    'success' => false,
                    'message' => 'Vous devez avoir au moins 100 points pour effectuer une conversion.'
                ];
            }

            // Vérifier que le nombre de points est un multiple de 100
            if ($points % 100 !== 0) {
                return [
                    'success' => false,
                    'message' => 'Le nombre de points doit être un multiple de 100.'
                ];
            }

            // Vérifier que l'utilisateur a suffisamment de points
            if ($currentPoints < $points) {
                return [
                    'success' => false,
                    'message' => 'Vous n\'avez pas assez de points pour cette conversion.'
                ];
            }

            // Calculer le montant en TND (5 TND pour 100 points)
            $montantTND = $points * 0.325;

            // Convertir en devise demandée
            $montant = $this->convertCurrency($montantTND, $devise);

            // Mettre à jour les points de l'utilisateur
            $user->setPoints($currentPoints - $points);

            // Mettre à jour l'argent de l'utilisateur
            $currentArgent = $user->getArgent() ?? '0.00';
            $newArgent = (float)$currentArgent + $montant;
            $user->setArgent((string)$newArgent);

            // Enregistrer dans historique_points
            $historique = new HistoriquePoints();
            $historique->setUser($user);
            $historique->setType('perte');
            $historique->setPoints(-$points);
            $historique->setRaison('Conversion en ' . $devise);
            $historique->setDate(new \DateTime());

            // Essayer d'enregistrer dans transaction_argent si la table existe
            try {
                $transaction = new TransactionArgent();
                $transaction->setUser($user);
                $transaction->setType('conversion');
                $transaction->setMontant((string)$montant);
                $transaction->setDevise($devise);
                $transaction->setDate(new \DateTime());
                $transaction->setPointConvertis((string)$points);

                $this->entityManager->persist($transaction);
            } catch (\Exception $e) {
                // Si la table n'existe pas, on continue sans erreur
                error_log('Erreur lors de l\'enregistrement de la transaction: ' . $e->getMessage());
            }
            
            // Créer et enregistrer une entrée dans la table Conversion
            try {
                $conversion = new \App\Entity\Conversion();
                $conversion->setUserId($user);
                $conversion->setPointsConvertis($points);
                $conversion->setMontant((string)$montant);
                $conversion->setDevise($devise);
                $conversion->setDate(new \DateTime());
                
                $this->entityManager->persist($conversion);
            } catch (\Exception $e) {
                // Si la table n'existe pas, on continue sans erreur
                error_log('Erreur lors de l\'enregistrement de la conversion: ' . $e->getMessage());
            }

            // Persister les changements
            $this->entityManager->persist($user);
            $this->entityManager->persist($historique);
            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'Conversion réussie',
                'montant' => $montant,
                'devise' => $devise,
                'points' => $points
            ];
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('Erreur lors de la conversion de points: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la conversion: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Convertit un montant de TND vers une autre devise
     *
     * @param float $montantTND Le montant en TND
     * @param string $devise La devise cible (TND, EUR, USD)
     * @return float Le montant converti
     */
    private function convertCurrency(float $montantTND, string $devise): float
    {
        // Taux de conversion (à ajuster selon les taux réels)
        $taux = [
            'TND' => 1.0,
            'EUR' => 0.29, // 1 TND = 0.29 EUR
            'USD' => 0.32  // 1 TND = 0.32 USD
        ];

        // Vérifier que la devise est supportée
        if (!isset($taux[$devise])) {
            return $montantTND; // Par défaut, retourner le montant en TND
        }

        // Convertir le montant
        return $montantTND * $taux[$devise];
    }
}
