<?php

namespace App\Controller\organizer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EditJobController extends AbstractController
{
    #[Route('/edit/job', name: 'app_edit_job')]
    public function index(): Response
    {
        return $this->render('edit_job/editjob.html.twig', [
            'controller_name' => 'EditJobController',
        ]);
    }
}
