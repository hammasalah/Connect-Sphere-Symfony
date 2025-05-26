<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\VisiteUtilisateur;
use Doctrine\ORM\EntityManagerInterface;

class VisitTrackerService
{
    private $entityManager;
    private $pointsService;

    public function __construct(EntityManagerInterface $entityManager, PointsService $pointsService)
    {
        $this->entityManager = $entityManager;
        $this->pointsService = $pointsService;
    }

    /**
     * Enregistre une visite pour un utilisateur et attribue des points si nécessaire
     *
     * @param Users $user L'utilisateur qui visite le site
     * @return array Résultat de l'opération avec statut et message
     */
    public function trackVisit(Users $user): array
    {
        error_log('trackVisit appelé pour user id: ' . $user->getId());
        // Pour debug : décommenter la ligne suivante pour stopper l'exécution et voir le user
        // dd($user);
        try {
            // Récupérer la dernière visite de l'utilisateur
            $visiteRepository = $this->entityManager->getRepository(VisiteUtilisateur::class);
            $derniereVisite = $visiteRepository->findOneBy(['user' => $user], ['dernier_visite' => 'DESC']);
            
            $today = new \DateTime();
            $today->setTime(0, 0, 0); // Minuit aujourd'hui
            
            // Si c'est la première visite de l'utilisateur
            if (!$derniereVisite) {
                $visite = new VisiteUtilisateur();
                $visite->setUser($user);
                $visite->setDernierVisite($today);
                $visite->setSerie(1);
                
                $this->entityManager->persist($visite);
                $this->entityManager->flush();
                
                // Ajouter des points pour la première visite
                $this->pointsService->addPoints($user, 5, 'gain', 'Première visite');
                
                return [
                    'success' => true,
                    'message' => 'Première visite enregistrée. +5 points!',
                    'serie' => 1
                ];
            }
            
            // Récupérer la date de la dernière visite
            $derniereDateVisite = $derniereVisite->getDernierVisite();
            $derniereDateVisite->setTime(0, 0, 0); // Minuit le jour de la dernière visite
            
            // Calculer la différence en jours
            $diffJours = $today->diff($derniereDateVisite)->days;
            
            // Si l'utilisateur a déjà visité aujourd'hui
            if ($diffJours == 0) {
                return [
                    'success' => true,
                    'message' => 'Vous avez déjà visité le site aujourd\'hui.',
                    'serie' => $derniereVisite->getSerie()
                ];
            }
            
            // Si l'utilisateur visite le jour suivant sa dernière visite
            if ($diffJours == 1) {
                $nouvelleSerie = $derniereVisite->getSerie() + 1;
                
                $visite = new VisiteUtilisateur();
                $visite->setUser($user);
                $visite->setDernierVisite($today);
                $visite->setSerie($nouvelleSerie);
                
                $this->entityManager->persist($visite);
                
                // Si l'utilisateur atteint 7 visites consécutives, ajouter un bonus
                if ($nouvelleSerie % 7 == 0) {
                    $this->pointsService->addPoints($user, 100, 'gain', '7 Days Visit');
                    $message = 'Visite enregistrée. Félicitations pour vos ' . $nouvelleSerie . ' visites consécutives! +100 points bonus!';
                } else {
                    $this->pointsService->addPoints($user, 5, 'gain', '5 Days Visit');
                    $message = 'Visite enregistrée. +5 points! Série actuelle: ' . $nouvelleSerie . ' jours.';
                }
                
                $this->entityManager->flush();
                
                return [
                    'success' => true,
                    'message' => $message,
                    'serie' => $nouvelleSerie
                ];
            } 
            // Si la série est interrompue
            else {
                $visite = new VisiteUtilisateur();
                $visite->setUser($user);
                $visite->setDernierVisite($today);
                $visite->setSerie(1);
                
                $this->entityManager->persist($visite);
                $this->entityManager->flush();
                
                // Ajouter des points pour la visite
                $this->pointsService->addPoints($user, 5, 'gain', '5 Days Visit');
                
                return [
                    'success' => true,
                    'message' => 'Visite enregistrée. +5 points! Nouvelle série commencée.',
                    'serie' => 1
                ];
            }
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('Erreur lors du suivi de visite: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement de votre visite.',
                'serie' => 0
            ];
        }
    }
    
    /**
     * Vérifie les utilisateurs inactifs et retire des points si nécessaire
     * Cette méthode est destinée à être exécutée par une commande cron
     *
     * @return int Nombre d'utilisateurs pénalisés
     */
    public function checkInactiveUsers(): int
    {
        try {
            $usersRepository = $this->entityManager->getRepository(Users::class);
            $visiteRepository = $this->entityManager->getRepository(VisiteUtilisateur::class);
            
            // Récupérer tous les utilisateurs
            $users = $usersRepository->findAll();
            $today = new \DateTime();
            $penalizedCount = 0;
            
            foreach ($users as $user) {
                // Récupérer la dernière visite de l'utilisateur
                $derniereVisite = $visiteRepository->findOneBy(['user' => $user], ['dernier_visite' => 'DESC']);
                
                // Si l'utilisateur n'a jamais visité le site, on passe au suivant
                if (!$derniereVisite) {
                    continue;
                }
                
                // Récupérer la date de la dernière visite
                $derniereDateVisite = $derniereVisite->getDernierVisite();
                
                // Calculer la différence en jours
                $diffJours = $today->diff($derniereDateVisite)->days;
                
                // Si l'utilisateur est inactif depuis 14 jours ou plus
                if ($diffJours >= 14) {
                    // Vérifier si l'utilisateur a déjà été pénalisé pour cette période d'inactivité
                    $historiqueRepository = $this->entityManager->getRepository(HistoriquePoints::class);
                    $dejapenalise = $historiqueRepository->findOneBy([
                        'user' => $user,
                        'type' => 'perte',
                        'raison' => '14 Days Absence'
                    ]);
                    
                    // Si l'utilisateur n'a pas encore été pénalisé pour cette période
                    if (!$dejapenalise) {
                        // Retirer 50 points pour inactivité
                        $this->pointsService->removePoints($user, 50, '14 Days Absence');
                        $penalizedCount++;
                    }
                }
            }
            
            return $penalizedCount;
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('Erreur lors de la vérification des utilisateurs inactifs: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Logique avancée de gestion de visite utilisateur (alignée avec la version Java)
     */
    public function trackVisitAdvanced(Users $user): array
    {
        $visiteRepository = $this->entityManager->getRepository(VisiteUtilisateur::class);
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $lastVisite = $visiteRepository->findOneBy(['user' => $user], ['dernier_visite' => 'DESC']);
        $currentStreak = $lastVisite ? $lastVisite->getSerie() : 0;
        $lastVisitDate = $lastVisite ? $lastVisite->getDernierVisite() : null;
        $newStreak = $currentStreak;
        $message = null;

        if (!$lastVisite) {
            // Première visite
            $newStreak = 1;
        } else {
            $lastVisit = (clone $lastVisitDate)->setTime(0, 0, 0);
            $yesterday = (clone $today)->modify('-1 day');
            $daysSinceLastVisit = $lastVisit->diff($today)->days;
            if ($daysSinceLastVisit >= 14) {
                // Calculer le nombre d'intervalles de 14 jours
                $intervals = (int) floor($daysSinceLastVisit / 14);
                $pointsToDeduct = $intervals * 5;
                if ($pointsToDeduct > 0) {
                    $this->pointsService->removePoints($user, $pointsToDeduct, 'absence de ' . $daysSinceLastVisit . ' jours');
                    // Historique pour chaque intervalle
                    for ($i = 1; $i <= $intervals; $i++) {
                        $intervalDays = $i * 14;
                        $this->pointsService->addPoints($user, -5, 'perte', 'absence de ' . $intervalDays . ' jours');
                    }
                    $message = 'Inactivité détectée : tu as perdu ' . $pointsToDeduct . ' points pour ' . $daysSinceLastVisit . ' jours d\'absence.';
                }
                $newStreak = 1;
            } elseif ($lastVisit == $yesterday) {
                $newStreak = $currentStreak + 1;
            } elseif ($lastVisit != $today) {
                $newStreak = 1;
            }
        }

        // Bonus pour 7 jours consécutifs
        if ($newStreak == 7) {
            $this->pointsService->addPoints($user, 50, 'gain', 'visite 7 jours');
            $message = 'Félicitations ! Tu as gagné 50 points pour 7 jours consécutifs.';
            $newStreak = 0;
        }

        // Enregistrer la visite du jour
        try {
            $visite = new VisiteUtilisateur();
            $visite->setUser($user);
            $visite->setDernierVisite($today);
            $visite->setSerie($newStreak);
            $this->entityManager->persist($visite);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            error_log('Erreur persist/flush VisiteUtilisateur dans trackVisitAdvanced: ' . $e->getMessage());
            throw $e;
        }

        return [
            'success' => true,
            'message' => $message,
            'serie' => $newStreak
        ];
    }
}
