<?php

namespace App\Controller\profile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\Users;
use App\Entity\UserProfile;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(SessionInterface $session, EntityManagerInterface $em): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$session->has('user')) {
            return $this->redirectToRoute('app_login');
        }

        /** @var Users $user */
        $user = $session->get('user');

        // On peut récupérer un utilisateur complet depuis la BDD si besoin :
        $user = $em->getRepository(Users::class)->find($user->getId());

        $profile = $em->getRepository(UserProfile::class)->findOneBy(['user' => $user]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        if (!$session->has('user')) {
            return $this->redirectToRoute('app_login');
        }

        /** @var Users $user */
        $user = $session->get('user');

        // On recharge depuis la BDD si besoin de persistance complète
        $user = $em->getRepository(Users::class)->find($user->getId());

        $profile = $em->getRepository(UserProfile::class)->findOneBy(['user' => $user]);
        if (!$profile) {
            $profile = new UserProfile();
            $profile->setUser($user);
        }

        if ($request->isMethod('POST')) {
            $user->setUsername($request->request->get('username'));
            $user->setEmail($request->request->get('email'));
            $user->setAge((int) $request->request->get('age'));
            $user->setGender($request->request->get('gender'));
            $user->setUpdatedAt(new \DateTime());

            $password = $request->request->get('password');
            if (!empty($password)) {
                // Encoder le mot de passe si nécessaire avec le hasher (à intégrer si besoin)
                $user->setPassword($password);
            }

            $profile->setBio($request->request->get('bio'));

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('profilePicture');
            if ($uploadedFile) {
                $newFilename = uniqid() . '.' . $uploadedFile->guessExtension();
                $uploadedFile->move(
                    $this->getParameter('upload_directory'),
                    $newFilename
                );
                $profile->setProfilePicture($newFilename);

            }

            $em->persist($user);
            $em->persist($profile);
            $em->flush();

            // Mise à jour de la session avec l'utilisateur modifié
            $session->set('user', $user);

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }
}
