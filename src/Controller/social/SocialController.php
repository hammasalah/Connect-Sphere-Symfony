<?php

namespace App\Controller\social;

use App\Entity\FeedPosts;
use App\Entity\GroupFeedPosts;
use App\Entity\GroupMembers;
use App\Entity\Likes;
use App\Entity\Comments;
use App\Entity\Shares;
use App\Entity\UserFollowers;
use App\Repository\FeedPostsRepository;
use App\Repository\GroupFeedPostsRepository;
use App\Repository\GroupMembersRepository;
use App\Repository\LikesRepository;
use App\Repository\CommentsRepository;
use App\Repository\UsersRepository;
use App\Repository\SharesRepository;
use App\Repository\UserGroupsRepository;
use App\Repository\UserFollowersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Notification;

#[Route('/social')]
class SocialController extends AbstractController
{
    /**
     * Ajoute des variables globales à tous les templates
     */
    private function addGlobalVariables(EntityManagerInterface $entityManager, Request $request): array
    {
        // Récupérer l'utilisateur connecté depuis la session
        $session = $request->getSession();
        $userSession = $session->get('user');
        $currentUser = null;
        
        if ($userSession) {
            $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        }
        
        // Récupérer le nombre de demandes de suivi en attente
        $pendingRequestsCount = 0;
        $unreadNotificationsCount = 0;
        
        if ($currentUser) {
            $pendingRequestsCount = $entityManager->getRepository(\App\Entity\UserFollowers::class)->count([
                'followed' => $currentUser,
                'status' => \App\Entity\UserFollowers::STATUS_PENDING
            ]);
            
            // Récupérer le nombre de notifications non lues
            $notificationRepository = $entityManager->getRepository(Notification::class);
            $unreadNotificationsCount = $notificationRepository->countUnreadByUser($currentUser);
        }
        
        return [
            'pendingRequestsCount' => $pendingRequestsCount,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'currentUser' => $currentUser
        ];
    }
    #[Route('/', name: 'app_social')]
    public function index(
        Request $request,
        FeedPostsRepository $feedPostsRepository,
        LikesRepository $likesRepository,
        CommentsRepository $commentsRepository,
        SharesRepository $sharesRepository,
        UsersRepository $usersRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            // Si l'utilisateur n'existe pas dans la base de données, déconnecter et rediriger
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer tous les posts
        $feedPosts = $feedPostsRepository->findBy(['isDeleted' => 0], ['timeStamp' => 'DESC']);
        $postsData = [];

        foreach ($feedPosts as $post) {
            $likes = $likesRepository->findBy(['postId' => $post]);
            $likeCount = count($likes);
            $userLiked = $likesRepository->findOneBy(['postId' => $post, 'user_id' => $currentUser]) !== null;
            $comments = $commentsRepository->findBy(['postId' => $post, 'isDeleted' => 0], ['timeStamp' => 'DESC']);

            $postsData[] = [
                'post' => $post,
                'user' => $post->getUserId(),
                'likeCount' => $likeCount,
                'comments' => $comments,
                'userLiked' => $userLiked,
            ];
        }

        $globalVars = $this->addGlobalVariables($entityManager, $request);
        
        return $this->render('social/social.html.twig', array_merge([
            'posts' => $postsData,
        ], $globalVars));
    }

    #[Route('/add-post', name: 'app_social_add_post', methods: ['GET', 'POST'])]
    public function addPost(
        Request $request,
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $user = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }
        
        if ($request->isMethod('POST')) {

            $content = $request->request->get('content');
            $imageFile = $request->files->get('image_file');

            if (empty($content)) {
                $this->addFlash('error', 'Le contenu du post ne peut pas être vide');
                return $this->redirectToRoute('app_social');
            }

            $post = new FeedPosts();
            $post->setUserId($user);
            $post->setContent($content);
            $post->setTimeStamp((new \DateTime())->format('Y-m-d H:i:s'));       
            $post->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            $post->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            $post->setScorePopularite(0);
            $post->setIsDeleted(0);

            if ($imageFile) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/images';
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadsDirectory, $newFilename);
                $post->setImagePath('/uploads/images/' . $newFilename);
            }

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post ajouté avec succès');
            return $this->redirectToRoute('app_social');
        }

        return $this->render('social/add_post.html.twig');
    }

    #[Route('/like/{id}', name: 'app_social_like_post', methods: ['POST'])]
    public function likePost(
        Request $request,
        FeedPostsRepository $feedPostsRepository,
        LikesRepository $likesRepository,
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
        
        $postId = $request->attributes->get('id');
        $post = $feedPostsRepository->find($postId);
        
        if (!$post) {
            $this->addFlash('error', 'Post non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }

        $existingLike = $likesRepository->findOneBy(['postId' => $post, 'user_id' => $currentUser]);
        if ($existingLike) {
            $entityManager->remove($existingLike);
            $this->addFlash('success', 'Vous avez retiré votre like');
        } else {
            $like = new Likes();
            $like->setPostId($post);
            $like->setUserId($currentUser);
            $like->setTimeStamp((new \DateTime())->format('Y-m-d H:i:s'));
            $entityManager->persist($like);
            $this->addFlash('success', 'Vous avez aimé ce post');
        }

        $entityManager->flush();
        return $this->redirectToRoute('app_social');
    }

    #[Route('/comment/{id}', name: 'app_social_add_comment', methods: ['POST'])]
    public function addComment(
        Request $request,
        FeedPostsRepository $feedPostsRepository,
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
        
        $postId = $request->attributes->get('id');
        $post = $feedPostsRepository->find($postId);
        
        if (!$post) {
            $this->addFlash('error', 'Post non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }

        $content = $request->request->get('content');
        if (empty($content)) {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide');
            return $this->redirectToRoute('app_social');
        }

        $comment = new Comments();
        $comment->setPostId($post);
        $comment->setUserId($currentUser);
        $comment->setContent($content);
        $comment->setTimeStamp((new \DateTime())->format('Y-m-d H:i:s'));
        $comment->setIsDeleted(0);

        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Commentaire ajouté avec succès');
        return $this->redirectToRoute('app_social');
    }
    
   

    /**
     * Recherche des utilisateurs et des groupes
     */
    #[Route('/search', name: 'app_social_search', methods: ['GET'])]
    public function search(
        Request $request,
        UsersRepository $usersRepository,
        UserGroupsRepository $userGroupsRepository
    ): Response {
        $searchTerm = $request->query->get('search', '');
        
        if (empty($searchTerm)) {
            return $this->render('social/search_results.html.twig', [
                'results' => ['searchTerm' => $searchTerm]
            ]);
        }
        
        $users = $usersRepository->searchUsers($searchTerm);
        $groups = $userGroupsRepository->searchGroups($searchTerm);
        
        return $this->render('social/search_results.html.twig', [
            'results' => [
                'searchTerm' => $searchTerm,
                'users' => $users,
                'groups' => $groups
            ]
        ]);
    }
    
    /**
     * Recherche AJAX pour l'autocomplétion (sans API)
     */
    #[Route('/search-ajax', name: 'app_social_search_ajax', methods: ['GET'])]
    public function searchAjax(
        Request $request,
        UsersRepository $usersRepository,
        UserGroupsRepository $userGroupsRepository
    ): JsonResponse {
        $searchTerm = $request->query->get('search', '');
        $results = [];
        
        if (strlen($searchTerm) >= 2) {
            $users = $usersRepository->searchUsers($searchTerm);
            $groups = $userGroupsRepository->searchGroups($searchTerm);
            
            foreach ($users as $user) {
                $results['users'][] = [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'type' => 'user'
                ];
            }
            
            foreach ($groups as $group) {
                $results['groups'][] = [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'description' => $group->getDescription() ?? '',
                    'type' => 'group'
                ];
            }
        }
        
        return new JsonResponse($results);
    }

    /**
     * Affiche un post individuel (nécessaire pour le partage)
     */
    #[Route('/post/{id}', name: 'app_social_view_post')]
    public function viewPost(
        Request $request,
        int $id,
        FeedPostsRepository $feedPostsRepository,
        LikesRepository $likesRepository,
        CommentsRepository $commentsRepository,
        SharesRepository $sharesRepository,
        UsersRepository $usersRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
        
        $post = $feedPostsRepository->find($id);
        
        if (!$post || $post->getIsDeleted()) {
            $this->addFlash('error', 'Post non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        $likes = $likesRepository->findBy(['postId' => $post]);
        $likeCount = count($likes);
        $userLiked = $likesRepository->findOneBy(['postId' => $post, 'user_id' => $currentUser]) !== null;
        $comments = $commentsRepository->findBy(['postId' => $post, 'isDeleted' => 0], ['timeStamp' => 'DESC']);
        $shares = $sharesRepository->findBy(['postId' => $post]);
        $shareCount = count($shares);
        $userShared = $sharesRepository->findOneBy(['postId' => $post, 'user_id' => $currentUser]) !== null;
        
        $postData = [
            'post' => $post,
            'user' => $post->getUserId(),
            'likeCount' => $likeCount,
            'comments' => $comments,
            'userLiked' => $userLiked,
            'shareCount' => $shareCount,
            'userShared' => $userShared,
        ];
        
        // Ajouter les variables globales
        $globalVars = $this->addGlobalVariables($entityManager, $request);
        
        return $this->render('social/view_post.html.twig', array_merge([
            'posts' => [$postData],
        ], $globalVars));
    }
    
    // La méthode de partage interne a été supprimée
    // Seul le partage externe sur les réseaux sociaux est maintenant disponible
    
    /**
     * Permet à l'utilisateur de modifier son propre post
     */
    #[Route('/edit-post/{id}', name: 'app_social_edit_post', methods: ['GET', 'POST'])]
    public function editPost(
        Request $request,
        int $id,
        FeedPostsRepository $feedPostsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }
        
        $post = $feedPostsRepository->find($id);
        
        if (!$post) {
            $this->addFlash('error', 'Post non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Vérifier si l'utilisateur est l'auteur du post
        if ($post->getUserId()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce post car vous n\'en êtes pas l\'auteur');
            return $this->redirectToRoute('app_social');
        }
        
        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            $imageFile = $request->files->get('image_file');
            
            if (empty($content)) {
                $this->addFlash('error', 'Le contenu du post ne peut pas être vide');
                return $this->render('social/edit_post.html.twig', [
                    'post' => $post,
                ]);
            }
            
            $post->setContent($content);
            $post->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            
            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe
                if ($post->getImagePath()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $post->getImagePath();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/images';
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadsDirectory, $newFilename);
                $post->setImagePath('/uploads/images/' . $newFilename);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Post modifié avec succès');
            return $this->redirectToRoute('app_social');
        }
        
        // Ajouter les variables globales
        $globalVars = $this->addGlobalVariables($entityManager, $request);
        
        return $this->render('social/edit_post.html.twig', array_merge([
            'post' => $post,
        ], $globalVars));
    }
    
    /**
     * Permet à l'utilisateur de supprimer son propre post
     */
    #[Route('/delete-post/{id}', name: 'app_social_delete_post', methods: ['POST'])]
    public function deletePost(
        Request $request,
        int $id,
        FeedPostsRepository $feedPostsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }
        
        $post = $feedPostsRepository->find($id);
        
        if (!$post) {
            $this->addFlash('error', 'Post non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Vérifier si l'utilisateur est l'auteur du post
        if ($post->getUserId()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce post car vous n\'en êtes pas l\'auteur');
            return $this->redirectToRoute('app_social');
        }
        
        // Marquer le post comme supprimé au lieu de le supprimer physiquement
        $post->setIsDeleted(1);
        $entityManager->flush();
        
        $this->addFlash('success', 'Post supprimé avec succès');
        return $this->redirectToRoute('app_social');
    }
    
    /**
     * Affiche le profil d'un utilisateur avec ses publications
     */
    #[Route('/user/{id}', name: 'app_social_user_profile')]
    public function userProfile(
        Request $request,
        int $id,
        UsersRepository $usersRepository,
        FeedPostsRepository $feedPostsRepository,
        LikesRepository $likesRepository,
        CommentsRepository $commentsRepository,
        EntityManagerInterface $entityManager,
        UserFollowersRepository $userFollowersRepository
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        $currentUser = null;
        $followStatus = null;
        
        if ($userSession) {
            $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        }
        
        $user = $usersRepository->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Récupérer le statut de suivi si l'utilisateur est connecté
        if ($currentUser) {
            $followStatus = $userFollowersRepository->findOneBy([
                'follower' => $currentUser,
                'followed' => $user
            ]);
        }
        
        // Récupérer les publications de l'utilisateur
        $userPosts = $feedPostsRepository->findBy(['userId' => $user, 'isDeleted' => 0], ['timeStamp' => 'DESC']);
        $postsData = [];
        
        foreach ($userPosts as $post) {
            $likes = $likesRepository->findBy(['postId' => $post]);
            $likeCount = count($likes);
            $userLiked = $currentUser ? $likesRepository->findOneBy(['postId' => $post, 'user_id' => $currentUser]) !== null : false;
            $comments = $commentsRepository->findBy(['postId' => $post, 'isDeleted' => 0], ['timeStamp' => 'DESC']);
            
            $postsData[] = [
                'post' => $post,
                'user' => $post->getUserId(),
                'likeCount' => $likeCount,
                'comments' => $comments,
                'userLiked' => $userLiked,
            ];
        }
        
        // Ajouter les variables globales
        $globalVars = $this->addGlobalVariables($entityManager, $request);
        
        return $this->render('social/user_profile.html.twig', array_merge([
            'user' => $user,
            'posts' => $postsData,
            'currentUser' => $currentUser,
            'followStatus' => $followStatus
        ], $globalVars));
    }
    
    /**
     * Affiche le profil d'un groupe avec ses publications
     */
    #[Route('/group/{id}', name: 'app_social_group_profile')]
    public function groupProfile(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        UserGroupsRepository $userGroupsRepository,
        GroupFeedPostsRepository $groupFeedPostsRepository,
        UsersRepository $usersRepository,
        LikesRepository $likesRepository,
        CommentsRepository $commentsRepository,
        GroupMembersRepository $groupMembersRepository
    ): Response {
        $group = $userGroupsRepository->find($id);
        
        if (!$group) {
            $this->addFlash('error', 'Groupe non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Récupérer l'utilisateur connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        $currentUser = null;
        $isMember = false;
        $isPending = false;
        
        if ($userSession) {
            $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
            
            // Vérifier si l'utilisateur est membre du groupe ou a une demande en attente
            if ($currentUser) {
                $membership = $groupMembersRepository->findOneBy(['group_it' => $group, 'user_id' => $currentUser]);
                if ($membership) {
                    if ($membership->isAccepted()) {
                        $isMember = true;
                    } elseif ($membership->isPending()) {
                        $isPending = true;
                    }
                }
            }
        } else {
            // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer les publications du groupe
        $groupPosts = $groupFeedPostsRepository->findBy(['group' => $group, 'is_deleted' => 0], ['timestamp' => 'DESC']);
        $postsData = [];
        
        foreach ($groupPosts as $post) {
            $likes = $likesRepository->findBy(['postId' => $post]);
            $likeCount = count($likes);
            $userLiked = $likesRepository->findOneBy(['postId' => $post, 'user_id' => $currentUser]) !== null;
            $comments = $commentsRepository->findBy(['postId' => $post, 'isDeleted' => 0], ['timeStamp' => 'DESC']);
            
            $postsData[] = [
                'post' => $post,
                'user' => $post->getUserId(),
                'likeCount' => $likeCount,
                'comments' => $comments,
                'userLiked' => $userLiked,
            ];
        }
        
        return $this->render('social/group_profile.html.twig', [
            'group' => $group,
            'posts' => $postsData,
            'currentUser' => $currentUser,
            'isMember' => $isMember,
            'isPending' => $isPending
        ]);
    }
    
    /**
     * Ajoute un post dans un groupe
     */
    #[Route('/group/{id}/add-post', name: 'app_social_group_add_post', methods: ['POST'])]
    public function addGroupPost(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        UserGroupsRepository $userGroupsRepository,
        UsersRepository $usersRepository
    ): Response {
        $group = $userGroupsRepository->find($id);
        
        if (!$group) {
            $this->addFlash('error', 'Groupe non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            $this->addFlash('error', 'Utilisateur non connecté');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $user = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }

        $content = $request->request->get('content');
        $imageFile = $request->files->get('image_file');

        if (empty($content)) {
            $this->addFlash('error', 'Le contenu du post ne peut pas être vide');
            return $this->redirectToRoute('app_social_group_profile', ['id' => $id]);
        }

        $post = new GroupFeedPosts();
        $post->setGroupId($group);
        $post->setUserId($user);
        $post->setContent($content);
        $post->setTimestamp((new \DateTime())->format('Y-m-d H:i:s'));
        $post->setIsDeleted(0);

        if ($imageFile) {
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/images';
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($uploadsDirectory, $newFilename);
            $post->setMediaUrl('/uploads/images/' . $newFilename);
        } else {
            $post->setMediaUrl('');
        }

        $entityManager->persist($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post ajouté au groupe avec succès');
        return $this->redirectToRoute('app_social_group_profile', ['id' => $id]);
    }
    
    /**
     * Permet à un utilisateur de rejoindre un groupe
     */
    #[Route('/group/{id}/join', name: 'app_social_join_group', methods: ['POST'])]
    public function joinGroup(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        UserGroupsRepository $userGroupsRepository,
        UsersRepository $usersRepository,
        GroupMembersRepository $groupMembersRepository
    ): Response {
        $group = $userGroupsRepository->find($id);
        
        if (!$group) {
            $this->addFlash('error', 'Groupe non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            $this->addFlash('error', 'Utilisateur non connecté');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $user = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est déjà membre du groupe ou a une demande en attente
        $existingMembership = $groupMembersRepository->findOneBy(['group_it' => $group, 'user_id' => $user]);
        if ($existingMembership) {
            if ($existingMembership->isPending()) {
                $this->addFlash('info', 'Votre demande d\'adhésion à ce groupe est en attente d\'approbation');
            } else if ($existingMembership->isAccepted()) {
                $this->addFlash('info', 'Vous êtes déjà membre de ce groupe');
            } else {
                $this->addFlash('info', 'Votre demande précédente a été refusée');
            }
            return $this->redirectToRoute('app_social_group_profile', ['id' => $id]);
        }

        // Créer une demande d'adhésion au groupe
        $membership = new GroupMembers();
        $membership->setGroupIt($group);
        $membership->setUserId($user);
        $membership->setRole('member'); // Rôle par défaut
        $membership->setStatus(GroupMembers::STATUS_PENDING); // Définir le statut comme en attente
        $membership->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));

        $entityManager->persist($membership);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande d\'adhésion au groupe a été envoyée avec succès');
        return $this->redirectToRoute('app_social_group_profile', ['id' => $id]);
    }
    
    /**
     * Permet à un utilisateur de quitter un groupe
     */
    #[Route('/group/{id}/leave', name: 'app_social_leave_group', methods: ['POST'])]
    public function leaveGroup(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        UserGroupsRepository $userGroupsRepository,
        GroupMembersRepository $groupMembersRepository
    ): Response {
        $group = $userGroupsRepository->find($id);
        
        if (!$group) {
            $this->addFlash('error', 'Groupe non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            $this->addFlash('error', 'Utilisateur non connecté');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $user = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est le créateur du groupe
        if ($group->getCreatorId()->getId() === $user->getId()) {
            $this->addFlash('error', 'Le créateur du groupe ne peut pas quitter le groupe');
            return $this->redirectToRoute('app_social_group_profile', ['id' => $id]);
        }
        
        // Vérifier si l'utilisateur est membre du groupe
        $membership = $groupMembersRepository->findOneBy(['group_it' => $group, 'user_id' => $user]);
        
        if (!$membership || !$membership->isAccepted()) {
            $this->addFlash('error', 'Vous n\'êtes pas membre de ce groupe');
            return $this->redirectToRoute('app_social_group_profile', ['id' => $id]);
        }
        
        // Supprimer l'adhésion au groupe
        $entityManager->remove($membership);
        $entityManager->flush();
        
        $this->addFlash('success', 'Vous avez quitté le groupe avec succès');
        return $this->redirectToRoute('app_social_group_profile', ['id' => $id]);
    }
    
    /**
     * Permet à un utilisateur d'annuler sa demande d'adhésion à un groupe
     */
    #[Route('/group/{id}/cancel-join', name: 'app_social_cancel_join_group', methods: ['POST'])]
    public function cancelJoinGroup(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        UserGroupsRepository $userGroupsRepository,
        GroupMembersRepository $groupMembersRepository
    ): Response {
        $group = $userGroupsRepository->find($id);
        
        if (!$group) {
            $this->addFlash('error', 'Groupe non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            $this->addFlash('error', 'Utilisateur non connecté');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $user = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier si l'utilisateur a une demande en attente
        $membership = $groupMembersRepository->findOneBy(['group_it' => $group, 'user_id' => $user]);
        
        if (!$membership || !$membership->isPending()) {
            $this->addFlash('error', 'Vous n\'avez pas de demande d\'adhésion en attente pour ce groupe');
            return $this->redirectToRoute('app_social_group_profile', ['id' => $id]);
        }
        
        // Supprimer la demande d'adhésion
        $entityManager->remove($membership);
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre demande d\'adhésion a été annulée avec succès');
        return $this->redirectToRoute('app_social_group_profile', ['id' => $id]);
    }

    /**
     * Permet à un utilisateur d'envoyer une demande pour suivre un autre utilisateur
     */
    #[Route('/user/{id}/follow', name: 'app_social_follow_user', methods: ['POST'])]
    public function followUser(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository,
        UserFollowersRepository $userFollowersRepository
    ): Response {
        $userToFollow = $usersRepository->find($id);
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        
        if (!$userToFollow) {
            if ($isAjax) {
                return $this->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
            }
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_social');
        }
        
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            if ($isAjax) {
                return $this->json(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
            }
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            $this->addFlash('error', 'Utilisateur non connecté');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            if ($isAjax) {
                return $this->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
            }
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur se suit lui-même
        if ($currentUser->getId() === $userToFollow->getId()) {
            if ($isAjax) {
                return $this->json(['success' => false, 'message' => 'Vous ne pouvez pas vous suivre vous-même'], 400);
            }
            $this->addFlash('error', 'Vous ne pouvez pas vous suivre vous-même');
            return $this->redirectToRoute('app_social_user_profile', ['id' => $id]);
        }

        // Vérifier si l'utilisateur suit déjà cet utilisateur ou a une demande en cours
        $existingFollow = $userFollowersRepository->findOneBy(['follower' => $currentUser, 'followed' => $userToFollow]);
        $message = '';
        $status = '';
        
        if ($existingFollow) {
            // Si déjà suivi ou demande en cours, on annule
            $entityManager->remove($existingFollow);
            $entityManager->flush();
            
            if ($existingFollow->isAccepted()) {
                $message = 'Vous ne suivez plus cet utilisateur';
                $status = 'unfollowed';
            } else if ($existingFollow->isPending()) {
                $message = 'Votre demande a été annulée';
                $status = 'cancelled';
            }
        } else {
            // Sinon, on crée une demande en attente
            $follow = new UserFollowers();
            $follow->setFollower($currentUser);
            $follow->setFollowed($userToFollow);
            $follow->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            $follow->setStatus(UserFollowers::STATUS_PENDING);
            
            $entityManager->persist($follow);
            $entityManager->flush();
            $message = 'Votre demande pour suivre cet utilisateur a été envoyée';
            $status = 'pending';
        }
        
        if ($isAjax) {
            return $this->json([
                'success' => true, 
                'message' => $message,
                'status' => $status
            ]);
        }
        
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_social_user_profile', ['id' => $id]);
    }
    
    /**
     * Permet à un utilisateur d'accepter ou de refuser une demande de suivi
     */
    #[Route('/follow-request/{id}/{action}', name: 'app_social_follow_request', methods: ['GET', 'POST'])]
    public function handleFollowRequest(
        int $id,
        string $action,
        Request $request,
        EntityManagerInterface $entityManager,
        UserFollowersRepository $userFollowersRepository,
        UsersRepository $usersRepository
    ): Response {
        $followRequest = $userFollowersRepository->find($id);
        
        if (!$followRequest) {
            $this->addFlash('error', 'Demande non trouvée');
            return $this->redirectToRoute('app_social');
        }
        
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            $this->addFlash('error', 'Utilisateur non connecté');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier que la demande concerne bien l'utilisateur connecté
        if ($followRequest->getFollowed()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à gérer cette demande');
            return $this->redirectToRoute('app_social');
        }
        
        // Vérifier que la demande est en attente
        if (!$followRequest->isPending()) {
            $this->addFlash('error', 'Cette demande a déjà été traitée');
            return $this->redirectToRoute('app_social');
        }
        
        $follower = $followRequest->getFollower();
        $followed = $followRequest->getFollowed();
        
        if ($action === 'accept') {
            $followRequest->setStatus(UserFollowers::STATUS_ACCEPTED);
            $entityManager->flush();
            
            // Créer une notification pour l'utilisateur
            $notification = new Notification();
            $notification->setUser($follower);
            $notification->setType(Notification::TYPE_FOLLOW_REQUEST_ACCEPTED);
            $notification->setMessage("Votre demande pour suivre {$followed->getUsername()} a été acceptée");
            $notification->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            $notification->setLink('/social/user/' . $followed->getId());
            
            $entityManager->persist($notification);
            $entityManager->flush();
            
            $this->addFlash('success', 'Vous avez accepté la demande de suivi');
        } else if ($action === 'reject') {
            $followRequest->setStatus(UserFollowers::STATUS_REJECTED);
            $entityManager->flush();
            
            // Créer une notification pour l'utilisateur
            $notification = new Notification();
            $notification->setUser($follower);
            $notification->setType(Notification::TYPE_FOLLOW_REQUEST_REJECTED);
            $notification->setMessage("Votre demande pour suivre {$followed->getUsername()} a été refusée");
            $notification->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            
            $entityManager->persist($notification);
            $entityManager->flush();
            
            $this->addFlash('success', 'Vous avez refusé la demande de suivi');
        } else {
            $this->addFlash('error', 'Action non reconnue');
        }
        
        return $this->redirectToRoute('app_social');
    }
    
    /**
     * Affiche les demandes de suivi en attente pour l'utilisateur connecté
     */
    #[Route('/follow-requests', name: 'app_social_follow_requests')]
    public function followRequests(
        Request $request,
        UserFollowersRepository $userFollowersRepository,
        UsersRepository $usersRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            $this->addFlash('error', 'Utilisateur non connecté');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer les demandes en attente
        $pendingRequests = $userFollowersRepository->findBy([
            'followed' => $currentUser,
            'status' => \App\Entity\UserFollowers::STATUS_PENDING
        ]);
        
        $globalVars = $this->addGlobalVariables($entityManager, $request);
        
        return $this->render('social/follow_requests.html.twig', array_merge([
            'pendingRequests' => $pendingRequests
        ], $globalVars));
    }
    
    /**
     * Affiche les notifications de l'utilisateur connecté
     */
    #[Route('/notifications', name: 'app_social_notifications')]
    public function notifications(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
            $this->addFlash('error', 'Utilisateur non connecté');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(\App\Entity\Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer les notifications de l'utilisateur
        $notificationRepository = $entityManager->getRepository(Notification::class);
        $notifications = $notificationRepository->findBy(
            ['user' => $currentUser],
            ['created_at' => 'DESC']
        );
        
        // Marquer toutes les notifications non lues comme lues
        $unreadNotifications = $notificationRepository->findUnreadByUser($currentUser);
        foreach ($unreadNotifications as $notification) {
            $notification->setIsRead(true);
        }
        $entityManager->flush();
        
        $globalVars = $this->addGlobalVariables($entityManager, $request);
        
        return $this->render('social/notifications.html.twig', array_merge([
            'notifications' => $notifications
        ], $globalVars));
    }
}