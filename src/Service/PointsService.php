<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\HistoriquePoints;
use App\Entity\UserRewards;
use App\Entity\Rewards;
use App\Entity\TransactionArgent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PointsService
{
    private $entityManager;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * Ajoute des points à un utilisateur et met à jour toutes les tables concernées
     *
     * @param Users $user L'utilisateur
     * @param int $points Le nombre de points à ajouter
     * @param string $type Le type d'action (gain ou perte)
     * @param string $raison La raison de l'ajout/retrait de points
     * @param int|null $rewardId L'ID de la récompense associée (si applicable)
     * @param int|null $eventId L'ID de l'événement associé (si applicable)
     * @return bool True si l'opération a réussi, false sinon
     */
    public function addPoints(Users $user, int $points, string $type, string $raison, ?int $rewardId = null, ?int $eventId = null): bool
    {
        try {
            // 1. Mettre à jour les points de l'utilisateur
            $currentPoints = $user->getPoints() ?? 0;
            $user->setPoints($currentPoints + $points);
            // 2. Enregistrer dans historique_points
            $historique = new HistoriquePoints();
            $historique->setUser($user);
            $historique->setType($type);
            $historique->setPoints($points);
            $historique->setRaison($raison);
            $historique->setDate(new \DateTime());
            // DEBUG LOG: Afficher toutes les valeurs de l'entité avant persist/flush
            error_log('DEBUG HistoriquePoints avant persist: ' . print_r([
                'user_id' => $user ? $user->getId() : null,
                'type' => $type,
                'points' => $points,
                'raison' => $raison,
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            ], true));
            // 3. Enregistrer dans user_rewards pour toute action de points
            $userReward = new UserRewards();
            $userReward->setUser($user);
            $userReward->setType($type);
            $userReward->setRaison($raison);
            $userReward->setPointsEarned($points);
            $userReward->setEarnedAt(new \DateTime());
            $userReward->setEventId($eventId ?? 0);
            // Si un reward_id est fourni, associer la récompense
            if ($rewardId !== null) {
                $reward = $this->entityManager->getRepository(Rewards::class)->find($rewardId);
                if ($reward) {
                    $userReward->setReward($reward);
                }
            }
            // Persister les changements
            $this->entityManager->persist($user);
            $this->entityManager->persist($historique);
            $this->entityManager->persist($userReward);
            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                error_log('Erreur persist/flush PointsService::addPoints: ' . $e->getMessage());
                throw $e;
            }
            // Mettre à jour la session si l'utilisateur est connecté
            $session = $this->requestStack->getSession();
            if ($session->has('user')) {
                $session->set('user', $user);
            }
            return true;
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('Erreur lors de l\'ajout de points: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retire des points à un utilisateur et met à jour toutes les tables concernées
     *
     * @param Users $user L'utilisateur
     * @param int $points Le nombre de points à retirer (valeur positive)
     * @param string $raison La raison du retrait de points
     * @return bool True si l'opération a réussi, false sinon
     */
    public function removePoints(Users $user, int $points, string $raison): bool
    {
        // Vérifier que l'utilisateur a suffisamment de points
        $currentPoints = $user->getPoints() ?? 0;
        if ($currentPoints < $points) {
            // Si l'utilisateur n'a pas assez de points, on retire ce qu'il a
            $points = $currentPoints;
        }
        
        // Appeler addPoints avec une valeur négative
        return $this->addPoints($user, -$points, 'perte', $raison);
    }
    
    /**
     * Convertit des points en argent
     *
     * @param Users $user L'utilisateur
     * @param int $points Le nombre de points à convertir
     * @param string $devise La devise (TND, EUR, USD)
     * @return array Résultat de la conversion avec statut, message et montant
     */
    public function convertPointsToMoney(Users $user, int $points, string $devise): array
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
            $montantTND = ($points / 100) * 5;
            
            // Convertir en devise demandée
            $montant = $this->convertCurrency($montantTND, $devise);
            
            // Mettre à jour les points de l'utilisateur
            $user->setPoints($currentPoints - $points);
            
            // Mettre à jour l'argent de l'utilisateur
            $currentArgent = $user->getArgent() ?? '0.00';
            $newArgent = (float)$currentArgent + $montant;
            $user->setArgent((string)$newArgent);
            
            // Enregistrer dans historique_points
            $this->addPoints($user, -$points, 'perte', 'Conversion en ' . $devise);
            
            // Enregistrer dans transaction_argent
            $transaction = new TransactionArgent();
            $transaction->setUser($user);
            $transaction->setType('conversion');
            $transaction->setMontant((string)$montant);
            $transaction->setDevise($devise);
            $transaction->setDate(new \DateTime());
            $transaction->setPointConvertis((string)$points);
            
            // Persister les changements
            $this->entityManager->persist($transaction);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            // Mettre à jour la session si l'utilisateur est connecté
            $session = $this->requestStack->getSession();
            if ($session->has('user')) {
                $session->set('user', $user);
            }
            
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
    
    /**
     * Récupère l'historique des points d'un utilisateur
     *
     * @param Users $user L'utilisateur
     * @param int $limit Limite du nombre d'entrées à récupérer
     * @return array L'historique des points
     */
    public function getPointsHistory(Users $user, int $limit = 10): array
    {
        return $this->entityManager->getRepository(HistoriquePoints::class)
            ->findBy(
                ['user' => $user],
                ['date' => 'DESC'],
                $limit
            );
    }
}
