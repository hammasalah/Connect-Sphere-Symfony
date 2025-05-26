<?php
namespace App\Controller\jobfeed;

use App\Entity\Users;
use App\Entity\Applications;
use App\Entity\Jobs;
use App\Repository\JobsRepository;
use App\Repository\ApplicationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class JobFeedController extends AbstractController
{
    #[Route('/job/feed', name: 'app_job_feed')]
    public function index(SessionInterface $session, JobsRepository $jobsRepository): Response
    {
        $currentUser = $session->get('user');
        $jobs = $jobsRepository->findAllExceptUser($currentUser);

        return $this->render('jobfeed/jobfeed.html.twig', [
            'jobs' => $jobs,
            'user' => $currentUser,
        ]);
    }

    #[Route('/apply/{jobId}', name: 'apply_job', methods: ['POST'])]
    public function apply(
        int $jobId,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Get currently logged-in user
        /** @var Users $user */
        $user = $this->getUser();
        // if (!$user) {
        //     return new Response('User not logged in.', 403);
        // }

        // Find the job
        $job = $em->getRepository(Jobs::class)->find($jobId);
        if (!$job) {
            return new Response('Job not found.', 404);
        }

        // Get form data (adjust field names based on your form)
        $coverLetter = $request->request->get('cover_letter');
        $resumePath = $request->files->get('resume');

        // Handle file upload
        if ($resumePath) {
            $originalFilename = pathinfo($resumePath->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = uniqid() . '-' . $originalFilename . '.' . $resumePath->guessExtension();

            $resumePath->move(
                $this->getParameter('uploads_directory'), // define this in services.yaml or .env
                $newFilename
            );
        } else {
            $newFilename = 'default.pdf'; // fallback or return error
        }

        // Create and save the application
        $application = new Applications();
        $application->setUserId($user);
        $application->setJobId($job);
        $application->setCoverLetter($coverLetter);
        $application->setResumePath($newFilename);
        $application->setStatus('pending');
        $application->setAppliedAt(new \DateTime());

        $em->persist($application);
        $em->flush();

        return new Response('Application submitted successfully.');
    }
}
