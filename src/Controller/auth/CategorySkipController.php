<?php

namespace App\Controller\auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Users;

class CategorySkipController extends AbstractController
{
    #[Route('/category-selection/skip', name: 'app_category_selection_skip')]
    public function skip(SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        // Check if user just registered
        $userId = $session->get('registered_user');
        if (!$userId) {
            return $this->redirectToRoute('app_register');
        }
        
        $user = $entityManager->getRepository(Users::class)->find($userId);
        if (!$user) {
            $this->addFlash('error', 'User not found. Please register again.');
            return $this->redirectToRoute('app_register');
        }
        
        // Clear the registration session variable
        $session->remove('registered_user');
        
        // Log the user in
        $session->set('user', $user);
        
        $this->addFlash('info', 'You can select your interests later in your profile settings.');
        return $this->redirectToRoute('app_login'); 
    }
}