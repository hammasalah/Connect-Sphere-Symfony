<?php

namespace App\Controller\application;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApplicationFormController extends AbstractController
{
    #[Route('/application/form', name: 'app_application_form')]
    public function index(): Response
    {
        return $this->render('application_form/application_form.html.twig', [
            'controller_name' => 'ApplicationFormController',
        ]);
    }
}
