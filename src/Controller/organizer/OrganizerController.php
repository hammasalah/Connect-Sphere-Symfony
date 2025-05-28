<?php

namespace App\Controller\organizer;


use App\Repository\JobsRepository;
use App\Repository\EventsRepository;
use App\Repository\UsersRepository;
use App\Repository\ApplicationsRepository;
use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\EditJobsType;
use App\Form\EditEventType;
use App\Entity\Jobs;
use App\Entity\Events;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class OrganizerController extends AbstractController
{
    public function __construct(
        private JobsRepository $jobsRepository,
        private EventsRepository $eventsRepository,
        private UsersRepository $usersRepository,
        private ParticipationRepository $participationRepository
    ) {}

    #[Route('/organizer', name: 'app_organizer')]
    public function index(
        SessionInterface $session,
        ApplicationsRepository $applicationsRepo,
        ParticipationRepository $participationRepo
    ): Response {
        $user = $session->get('user');

        if (!$user) {
            $this->addFlash('error', 'User not found in session. Please log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('organizer/organizer.html.twig', [
            'jobs' => $this->jobsRepository->findByUser($user),
            'events' => $this->eventsRepository->findByOrganizer($user),
            'my_applications' => $applicationsRepo->findApplicationsByUser($user),
            'applications_to_my_jobs' => $applicationsRepo->findApplicationsToOrganizerJobs($user),
            'participants_to_my_events' => $participationRepo->findParticipantsOfMyEvents($user),
            'my_participations' => $participationRepo->findBy(['participant' => $user]), // Add this line
            'user' => $user,
            'job_forms' => array_reduce(
                $this->jobsRepository->findByUser($user),
                fn($carry, $job) => $carry + [$job->getId() => $this->createForm(\App\Form\EditJobsType::class, $job)->createView()],
                []
            ),
            'event_forms' => array_reduce(
                $this->eventsRepository->findByOrganizer($user),
                fn($carry, $event) => $carry + [$event->getId() => $this->createForm(\App\Form\EditEventsType::class, $event)->createView()],
                []
            )
        ]);
    }

    // Route to approve the application
    #[Route('/organizer/approve-application/{id}', name: 'app_approve_application')]
    public function approveApplication(int $id, ApplicationsRepository $applicationsRepo, EntityManagerInterface $em): Response
    {
        $application = $applicationsRepo->find($id);

        if (!$application) {
            throw $this->createNotFoundException('Application not found');
        }

        $application->setStatus('approved');
        $em->flush();

        $this->addFlash('success', 'Application approved');

        return $this->redirectToRoute('app_organizer');
    }

    // Route to reject the application
    #[Route('/organizer/reject-application/{id}', name: 'app_reject_application')]
    public function rejectApplication(int $id, ApplicationsRepository $applicationsRepo, EntityManagerInterface $em): Response
    {
        $application = $applicationsRepo->find($id);

        if (!$application) {
            throw $this->createNotFoundException('Application not found');
        }

        $application->setStatus('rejected');
        $em->flush();

        $this->addFlash('success', 'Application rejected');

        return $this->redirectToRoute('app_organizer');
    }

    #[Route('/job/{id}/delete', name: 'organizer_delete_job', methods: ['POST'])]
    public function deleteJob(
        int $id,
        Request $request,
        JobsRepository $jobsRepository,
        EntityManagerInterface $em
    ): Response {
        // Fetch the job by ID
        $job = $jobsRepository->find($id);

        if (!$job) {
            $this->addFlash('error', 'Job not found.');
            return $this->redirectToRoute('app_organizer');
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('delete_job_' . $job->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_organizer');
        }

        // Remove the job
        $em->remove($job);
        $em->flush();

        $this->addFlash('success', 'Job deleted successfully.');
        return $this->redirectToRoute('app_organizer');
    }


    #[Route('/event/{id}/delete', name: 'organizer_delete_event', methods: ['POST'])]
    public function deleteEvent(
        int $id,
        Request $request,
        EventsRepository $eventsRepository,
        ParticipationRepository $participationRepository,
        EntityManagerInterface $em
    ): Response {
        // Fetch the event by ID
        $event = $eventsRepository->find($id);

        if (!$event) {
            $this->addFlash('error', 'Event not found.');
            return $this->redirectToRoute('app_organizer');
        }

        // Verify CSRF token
        if ($this->isCsrfTokenValid('delete_event_' . $event->getId(), $request->request->get('_token'))) {
            // Remove related participations
            $participations = $participationRepository->findBy(['event' => $event]);
            foreach ($participations as $participation) {
                $em->remove($participation);
            }

            // Remove the event
            $em->remove($event);
            $em->flush();

            $this->addFlash('success', 'Event and related participations deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_organizer');
    }




    #[Route('/job/{id}/edit', name: 'job_edit')]
    public function editJob(
        int $id,
        Request $request,
        JobsRepository $jobsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Fetch the job by ID
        $job = $jobsRepository->find($id);

        if (!$job) {
            $this->addFlash('error', 'Job not found.');
            return $this->redirectToRoute('app_organizer');
        }

        $form = $this->createForm(EditJobsType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($job);
            $entityManager->flush();

            $this->addFlash('success', 'Job updated successfully.');
            return $this->redirectToRoute('app_organizer');
        }

        return $this->render('organizer/edit_job.html.twig', [
            'form' => $form->createView(),
            'job' => $job,
        ]);
    }






    #[Route('/event/{id}/edit', name: 'event_edit')]
    public function editEvent(
        int $id,
        Request $request,
        EventsRepository $eventsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Fetch the event by ID
        $event = $eventsRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('The event does not exist.');
        }

        $form = $this->createForm(\App\Form\EditEventsType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('event_images_directory'), // Ensure this parameter is defined
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                    return $this->redirectToRoute('app_organizer');
                }

                $event->setImage($newFilename);
            }

            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Event updated successfully.');
            return $this->redirectToRoute('app_organizer');
        }

        return $this->render('organizer/edit_event.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }


    // this method to handle the cancellation

    #[Route('/participation/cancel/{id}', name: 'cancel_participation', methods: ['POST'])]
public function cancelParticipation(
    int $id,
    Request $request,
    ParticipationRepository $participationRepo,
    EntityManagerInterface $em
): Response {
    // Fetch the participation by ID
    $participation = $participationRepo->find($id);

    if (!$participation) {
        $this->addFlash('error', 'Participation not found.');
        return $this->redirectToRoute('app_organizer');
    }

    // Verify CSRF token
    $submittedToken = $request->request->get('_token');
    if (!$this->isCsrfTokenValid('cancel_participation_' . $participation->getId(), $submittedToken)) {
        $this->addFlash('error', 'Invalid CSRF token.');
        return $this->redirectToRoute('app_organizer');
    }

    // Remove the participation
    $em->remove($participation);
    $em->flush();

    $this->addFlash('success', 'You have successfully cancelled your participation.');
    return $this->redirectToRoute('app_organizer');
}
    // #[Route('/participation/cancel/{id}', name: 'cancel_participation', methods: ['POST'])]
    // public function cancelParticipation(
    //     int $id,
    //     Request $request,
    //     ParticipationRepository $participationRepo,
    //     EntityManagerInterface $em
    // ): Response {
    //     $participation = $participationRepo->find($id);

    //     if (!$participation) {
    //         $this->addFlash('error', 'Participation not found.');
    //         return $this->redirectToRoute('app_organizer');
    //     }

    //     $submittedToken = $request->request->get('_token');
    //     if (!$this->isCsrfTokenValid('cancel_participation_' . $participation->getId(), $submittedToken)) {
    //         $this->addFlash('error', 'Invalid CSRF token.');
    //         return $this->redirectToRoute('app_organizer');
    //     }

    //     $em->remove($participation);
    //     $em->flush();

    //     $this->addFlash('success', 'You have successfully cancelled your participation.');
    //     return $this->redirectToRoute('app_organizer');
    // }
}
