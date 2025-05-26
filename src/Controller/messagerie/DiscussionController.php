<?php
// src/Controller/messagerie/DiscussionController.php

namespace App\Controller\messagerie;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscussionController extends AbstractController
{
    #[Route('/discussion', name: 'app_discussion')]
    public function index(UsersRepository $userRepository): Response
    {
        $currentUser = $this->getUser();
        $currentUserId = null;

        if ($currentUser instanceof Users) {
            $currentUserId = $currentUser->getId();
        }

        $qb = $userRepository->createQueryBuilder('u');

        // If there is a logged in user, exclude them from list
        if ($currentUserId !== null) {
            $qb->where('u.id != :currentUserId')
               ->setParameter('currentUserId', $currentUserId);
        }

        $users = $qb->getQuery()->getResult();

        return $this->render('messagerie/message/index.html.twig', [
            'users' => $users,
            'currentUserId' => $currentUserId,
        ]);
    }
}
