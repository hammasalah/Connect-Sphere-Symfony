<?php

namespace App\Service;

use App\Entity\VisiteUtilisateur;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

class VisitService
{
    private $entityManager;
    private $pointsService;

    public function __construct(EntityManagerInterface $entityManager, PointsService $pointsService)
    {
        $this->entityManager = $entityManager;
        $this->pointsService = $pointsService;
    }

    public function updateUserVisit(Users $user): void
    {
        $repository = $this->entityManager->getRepository(VisiteUtilisateur::class);
        $visit = $repository->findOneBy(['user' => $user]);

        $today = new \DateTime();
        $streak = 0;

        if (!$visit) {
            $visit = new VisiteUtilisateur();
            $visit->setUser($user);
            $visit->setDernierVisite($today);
            $visit->setSerie(1);
            $this->entityManager->persist($visit);
        } else {
            $lastVisit = $visit->getDernierVisite();
            $daysDifference = $lastVisit->diff($today)->days;

            if ($daysDifference === 1) {
                $streak = $visit->getSerie() + 1;
                $visit->setSerie($streak);
            } elseif ($daysDifference > 1) {
                $streak = 1;
                $visit->setSerie($streak);
            }

            $visit->setDernierVisite($today);
        }

        if ($streak === 7) {
            $this->pointsService->addPoints($user, 5); // Add 5 points for 7-day streak
            $visit->setSerie(0); // Reset streak
        }

        $this->entityManager->flush();
    }
}
