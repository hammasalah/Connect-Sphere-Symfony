<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\VisiteUtilisateur;
use App\Entity\HistoriquePoints;
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
        try {
            $visiteRepository = $this->entityManager->getRepository(VisiteUtilisateur::class);
            $derniereVisite = $visiteRepository->findOneBy(['user' => $user], ['dernier_visite' => 'DESC']);
            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            if (!$derniereVisite) {
                $visite = new VisiteUtilisateur();
                $visite->setUser($user);
                $visite->setDernierVisite($today);
                $visite->setSerie(1);
                $this->entityManager->persist($visite);
                $this->entityManager->flush();
                $this->pointsService->addPoints($user, 5, 'gain', 'Première visite');
                return [
                    'success' => true,
                    'message' => 'Première visite enregistrée. +5 points!',
                    'serie' => 1
                ];
            }
            $derniereDateVisite = $derniereVisite->getDernierVisite();
            $derniereDateVisite->setTime(0, 0, 0);
            $diffJours = $today->diff($derniereDateVisite)->days;
            if ($diffJours == 0) {
                return [
                    'success' => true,
                    'message' => 'Vous avez déjà visité le site aujourd\'hui.',
                    'serie' => $derniereVisite->getSerie()
                ];
            }
            if ($diffJours == 1) {
                $nouvelleSerie = $derniereVisite->getSerie() + 1;
                $visite = new VisiteUtilisateur();
                $visite->setUser($user);
                $visite->setDernierVisite($today);
                $visite->setSerie($nouvelleSerie);
                $this->entityManager->persist($visite);
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
            } else {
                $visite = new VisiteUtilisateur();
                $visite->setUser($user);
                $visite->setDernierVisite($today);
                $visite->setSerie(1);
                $this->entityManager->persist($visite);
                $this->entityManager->flush();
                $this->pointsService->addPoints($user, 5, 'gain', '5 Days Visit');
                return [
                    'success' => true,
                    'message' => 'Visite enregistrée. +5 points! Nouvelle série commencée.',
                    'serie' => 1
                ];
            }
        } catch (\Exception $e) {
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
            $historiqueRepository = $this->entityManager->getRepository(HistoriquePoints::class);
            $users = $usersRepository->findAll();
            $today = new \DateTime();
            $penalizedCount = 0;
            foreach ($users as $user) {
                $derniereVisite = $visiteRepository->findOneBy(['user' => $user], ['dernier_visite' => 'DESC']);
                if (!$derniereVisite) {
                    continue;
                }
                $derniereDateVisite = $derniereVisite->getDernierVisite();
                $derniereDateVisite->setTime(0, 0, 0);
                $diffJours = $today->diff($derniereDateVisite)->days;
                if ($diffJours >= 14) {
                    // Calculer le nombre de périodes de 14 jours d'inactivité depuis la dernière visite
                    $nbPeriodes = intdiv($diffJours, 14);
                    // Vérifier combien de pénalités ont déjà été appliquées
                    $qb = $historiqueRepository->createQueryBuilder('h');
                    $qb->select('COUNT(h.id) as nbPenalites')
                        ->where('h.user = :user')
                        ->andWhere('h.type = :type')
                        ->andWhere('h.raison LIKE :raison')
                        ->setParameter('user', $user)
                        ->setParameter('type', 'perte')
                        ->setParameter('raison', 'absence de 14 jours%');
                    $nbPenalites = (int) $qb->getQuery()->getSingleScalarResult();
                    $nbAPenaliser = $nbPeriodes - $nbPenalites;
                    if ($nbAPenaliser > 0) {
                        for ($i = 0; $i < $nbAPenaliser; $i++) {
                            $this->pointsService->removePoints($user, 5, 'absence de 14 jours');
                            $penalizedCount++;
                        }
                    }
                }
            }
            return $penalizedCount;
        } catch (\Exception $e) {
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
            $newStreak = 1;
        } else {
            $lastVisit = (clone $lastVisitDate)->setTime(0, 0, 0);
            $yesterday = (clone $today)->modify('-1 day');
            $daysSinceLastVisit = $lastVisit->diff($today)->days;
            if ($daysSinceLastVisit >= 14) {
                $this->pointsService->removePoints($user, 5, 'absence de 14 jours');
                $message = 'Inactivité détectée : tu as perdu 5 points pour 14 jours d\'absence.';
                $newStreak = 1;
            } elseif ($lastVisit == $yesterday) {
                $newStreak = $currentStreak + 1;
            } elseif ($lastVisit != $today) {
                $newStreak = 1;
            }
        }
        if ($newStreak == 7) {
            $this->pointsService->addPoints($user, 50, 'gain', 'visite 7 jours');
            $message = 'Félicitations ! Tu as gagné 50 points pour 7 jours consécutifs.';
            $newStreak = 0;
        }
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
