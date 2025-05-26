<?php

namespace App\Controller\organizer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EditEventController extends AbstractController
{
    #[Route('/edit/event', name: 'app_edit_event')]
    public function index(): Response
    {
        return $this->render('edit_event/editevent.html.twig', [
            'controller_name' => 'EditEventController',
        ]);
    }
}
