<?php
// src/Controller/jobapplications/JobApplicationsController.php

namespace App\Controller\jobapplications;

use App\Repository\ApplicationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JobApplicationsController extends AbstractController
{
    #[Route('/job/applications', name: 'app_job_applications')]
    public function index(ApplicationsRepository $applicationsRepository): Response
    {
        $applications = $applicationsRepository->findAllApplications();

        return $this->render('jobapplications/jobapplications.html.twig', [
            'applications' => $applications,
        ]);
    }
}
