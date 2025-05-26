<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(SessionInterface $session): Response
    {
        // Check if user is logged in
        if (!$session->get('user')) {
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('dashboard/indexx.html.twig');
    }
}