<?php

namespace App\Controller\explore;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExploreController extends AbstractController
{
    #[Route('/explore', name: 'app_explore')]
    public function index(): Response
    {
        return $this->render('explore/explore.html.twig', [
            'controller_name' => 'ExploreController',
        ]);
    }
}
