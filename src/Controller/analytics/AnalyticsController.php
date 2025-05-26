<?php

namespace App\Controller\analytics;

use App\Repository\EventsRepository;
use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnalyticsController extends AbstractController
{
    #[Route('/analytics', name: 'app_analytics')]
    public function index(
        Request $request,
        EventsRepository $eventRepository,
        ParticipationRepository $participationRepo
    ): Response {
        $user = $request->getSession()->get('user');

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non connecté.');
            return $this->redirectToRoute('app_login');
        }

        // 1. Get events created by this user
        $events = $eventRepository->findBy(['organizerId' => $user]);

        // 2. Calculate average age per event
        $eventAverages = [];
        foreach ($events as $event) {
            $participants = $participationRepo->findBy(['event' => $event]);
            $ages = [];

            foreach ($participants as $p) {
                $participant = $p->getParticipant();
                $age = $participant?->getAge();
                if ($age !== null) {
                    $ages[] = $age;
                }
            }

            if (!empty($ages)) {
                $eventAverages[] = [
                    'event' => $event,
                    'average_age' => round(array_sum($ages) / count($ages), 2)
                ];
            }
        }

        // 3. Get selected event for gender chart
        $defaultEventId = !empty($events) ? $events[0]->getId() : 0;
        $selectedEventId = $request->query->getInt('event_id', $defaultEventId);
        $selectedEvent = $eventRepository->find($selectedEventId);

        // 4. Count gender
        $genderData = ['male' => 0, 'female' => 0];

        if ($selectedEvent) {
            $participations = $participationRepo->findBy(['event' => $selectedEvent]);

            foreach ($participations as $p) {
                $participant = $p->getParticipant();
                $gender = strtolower(trim($participant?->getGender() ?? ''));

                if ($gender === 'male') {
                    $genderData['male']++;
                } elseif ($gender === 'female') {
                    $genderData['female']++;
                }
            }
        }

        return $this->render('analytics/analytics.html.twig', [
            'eventAverages' => $eventAverages,
            'events' => $events,
            'selectedEventId' => $selectedEventId,
            'genderData' => $genderData
        ]);
    }
}
