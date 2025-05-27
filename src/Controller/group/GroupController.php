<?php


namespace App\Controller\group;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\UserGroups;
use App\Entity\GroupMembers;
use App\Entity\Users;
use App\Entity\Notification;
use App\Repository\GroupMembersRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GroupController extends AbstractController
{
    #[Route('/groups', name: 'app_groups')]
    public function index(SessionInterface $session, EntityManagerInterface $entityManager, GroupMembersRepository $groupMembersRepository): Response
    {
        // Check if user is logged in
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Get all groups
        $groups = $entityManager->getRepository(UserGroups::class)->findAll();
        
        // Get groups where user is a member (not necessarily creator)
        $userMemberships = $entityManager->getRepository(GroupMembers::class)->findBy(['user_id' => $user]);
        
        // Create an array of group IDs where the user is a member for easy lookup
        $userGroups = [];
        foreach ($userMemberships as $membership) {
            $userGroups[$membership->getGroupIt()->getId()] = $membership;
        }
        
        // Récupérer le nombre de demandes d'adhésion en attente pour les groupes de l'utilisateur
        $pendingRequests = $groupMembersRepository->findPendingRequestsForUserGroups($user->getId());
        $pendingRequestsCount = count($pendingRequests);
        
        // Stocker le nombre dans la session pour l'affichage du badge
        $session->set('pendingGroupRequestsCount', $pendingRequestsCount);
    
        return $this->render('group/index.html.twig', [
            'groups' => $groups,
            'userGroups' => $userGroups,
            'currentUser' => $user,
            'pendingRequestsCount' => $pendingRequestsCount
        ]);
    }

#[Route('/group/{groupId}/invite', name: 'app_group_invite')]
public function invite(
    Request $request,
    EntityManagerInterface $entityManager,
    SessionInterface $session,
    int $groupId
): Response {
    $user = $session->get('user');
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $group = $entityManager->getRepository(UserGroups::class)->find($groupId);
    if (!$group) {
        throw $this->createNotFoundException('Group not found');
    }

    // Check if current user can invite (creator or member)
    $isMember = $entityManager->getRepository(GroupMembers::class)->findOneBy([
        'group_it' => $group,
        'user_id' => $user
    ]);

    if (!$isMember && $group->getCreatorId()->getId() !== $user->getId()) {
        $this->addFlash('error', 'You need to be a member to invite friends');
        return $this->redirectToRoute('app_group_view', ['id' => $groupId]);
    }

    $searchTerm = $request->query->get('search');
    $nonMembers = [];

    if ($searchTerm) {
        // Get all users who are not members
        $qb = $entityManager->createQueryBuilder();
        $nonMembers = $qb->select('u')
            ->from(Users::class, 'u')
            ->where($qb->expr()->like('u.username', ':search'))
            ->andWhere($qb->expr()->notIn(
                'u.id',
                $entityManager->createQueryBuilder()
                    ->select('IDENTITY(gm.user_id)')
                    ->from(GroupMembers::class, 'gm')
                    ->where('gm.group_it = :group')
                    ->getDQL()
            ))
            ->setParameter('search', '%'.$searchTerm.'%')
            ->setParameter('group', $group)
            ->getQuery()
            ->getResult();
    }

    // Get all members for the view
    $members = $entityManager->getRepository(GroupMembers::class)->findBy([
        'group_it' => $group,
        'status' => GroupMembers::STATUS_ACCEPTED
    ]);
    $isMember = $entityManager->getRepository(GroupMembers::class)->findOneBy([
        'group_it' => $group,
        'user_id' => $user
    ]);

    return $this->render('group/view.html.twig', [
        'group' => $group,
        'members' => $members,
        'isMember' => $isMember,
        'currentUser' => $user,
        'nonMembers' => $nonMembers
    ]);
}

 #[Route('/group/create', name: 'app_group_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $group = new UserGroups();
            $group->setName($request->request->get('name'));
            $group->setDescription($request->request->get('description'));
            $group->setRules($request->request->get('rules'));
            $group->setCreatedAt(date('Y-m-d H:i:s'));

        // Handle image as base64
// Handle image as base64 (.jpg)
$uploadedFile = $request->files->get('profile_picture');
if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
    $base64Image = base64_encode(file_get_contents($uploadedFile->getPathname()));
    $group->setProfilePicture($base64Image);
} else {
    $group->setProfilePicture(''); // Default empty if no image
}

            // Récupération de l'utilisateur depuis la base
            $user = $entityManager->getRepository(Users::class)->find($user->getId());
            $group->setCreatorId($user);

            // Sauvegarder le groupe
            $entityManager->persist($group);
            $entityManager->flush();

            // Ajouter le créateur comme membre admin
            $member = new GroupMembers();
            $member->setGroupIt($group);
            $member->setUserId($user);
            $member->setRole('admin');

            $entityManager->persist($member);
            $entityManager->flush();

            $this->addFlash('success', 'Group created successfully!');
            return $this->redirectToRoute('app_groups');
        }

        return $this->render('group/create.html.twig');
    }

    #[Route('/group/{id}/edit', name: 'app_group_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, SessionInterface $session, int $id): Response
    {
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $group = $entityManager->getRepository(UserGroups::class)->find($id);
        if (!$group) {
            throw $this->createNotFoundException('Groupe non trouvé');
        }

        // Vérifier si l'utilisateur est le créateur du groupe
        if ($group->getCreatorId()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier ce groupe');
            return $this->redirectToRoute('app_groups');
        }

        if ($request->isMethod('POST')) {
            $group->setName($request->request->get('name'));
            $group->setDescription($request->request->get('description'));
            $group->setRules($request->request->get('rules'));
            $group->setProfilePicture($request->request->get('profile_picture') ??  $group->getProfilePicture());

            $entityManager->flush();

            $this->addFlash('success', 'Groupe mis à jour avec succès!');
            return $this->redirectToRoute('app_groups');
        }

        return $this->render('group/edit.html.twig', [
            'group' => $group
        ]);
    }

    #[Route('/group/{id}/delete', name: 'app_group_delete')]
    public function delete(EntityManagerInterface $entityManager, SessionInterface $session, int $id): Response
    {
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $group = $entityManager->getRepository(UserGroups::class)->find($id);
        if (!$group) {
            throw $this->createNotFoundException('Groupe non trouvé');
        }

        // Vérifier si l'utilisateur est le créateur du groupe
        if ($group->getCreatorId()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour supprimer ce groupe');
            return $this->redirectToRoute('app_groups');
        }

        // Supprimer d'abord tous les membres du groupe
        $members = $entityManager->getRepository(GroupMembers::class)->findBy(['group_it' => $group]);
        foreach ($members as $member) {
            $entityManager->remove($member);
        }

        $entityManager->remove($group);
        $entityManager->flush();

        $this->addFlash('success', 'Groupe supprimé avec succès!');
        return $this->redirectToRoute('app_groups');
    }

    #[Route('/group/{id}/view', name: 'app_group_view')]
    public function view(EntityManagerInterface $entityManager, SessionInterface $session, int $id): Response
    {
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $group = $entityManager->getRepository(UserGroups::class)->find($id);
        if (!$group) {
            throw $this->createNotFoundException('Groupe non trouvé');
        }

        // Vérifier si l'utilisateur est membre du groupe
        $isMember = $entityManager->getRepository(GroupMembers::class)->findOneBy([
            'group_it' => $group,
            'user_id' => $user
        ]);

        // Récupérer tous les membres du groupe
        // Get all members for the view
        $members = $entityManager->getRepository(GroupMembers::class)->findBy([
            'group_it' => $group,
            'status' => GroupMembers::STATUS_ACCEPTED
        ]);

        return $this->render('group/view.html.twig', [
            'group' => $group,
            'members' => $members,
            'isMember' => $isMember,
            'currentUser' => $user
        ]);
    }

    #[Route('/group/{id}/leave', name: 'app_group_leave')]
    public function leave(EntityManagerInterface $entityManager, SessionInterface $session, int $id): Response
    {
        // Get user from session
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Get the managed user entity
        $managedUser = $entityManager->getRepository(Users::class)->find($user->getId());
        if (!$managedUser) {
            throw $this->createNotFoundException('User not found');
        }
    
        // Get the group
        $group = $entityManager->getRepository(UserGroups::class)->find($id);
        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }
    
        // Verify if user is the group creator
        if ($group->getCreatorId()->getId() === $managedUser->getId()) {
            $this->addFlash('error', 'The group creator cannot leave the group');
            return $this->redirectToRoute('app_group_view', ['id' => $id]);
        }
    
        // Find the membership record
        $member = $entityManager->getRepository(GroupMembers::class)->findOneBy([
            'group_it' => $group,
            'user_id' => $managedUser  // Use the managed user entity
        ]);
    
        if (!$member) {
            $this->addFlash('error', 'You are not a member of this group');
            return $this->redirectToRoute('app_group_view', ['id' => $id]);
        }
    
        // Remove the membership
        $entityManager->remove($member);
        $entityManager->flush();
    
        $this->addFlash('success', 'You have successfully left the group');
        return $this->redirectToRoute('app_groups');
    }

        #[Route('/group/{groupId}/add-member/{userId}', name: 'app_group_add_member')]
    public function addMember(
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        int $groupId,
        int $userId
    ): Response {
        $currentUser = $session->get('user');
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }
    
        $group = $entityManager->getRepository(UserGroups::class)->find($groupId);
        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }
    
        // Check if current user can invite (creator or member)
        $isMember = $entityManager->getRepository(GroupMembers::class)->findOneBy([
            'group_it' => $group,
            'user_id' => $currentUser
        ]);
    
        if (!$isMember && $group->getCreatorId()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'You need to be a member to invite friends');
            return $this->redirectToRoute('app_group_view', ['id' => $groupId]);
        }
    
        $userToAdd = $entityManager->getRepository(Users::class)->find($userId);
        if (!$userToAdd) {
            throw $this->createNotFoundException('User not found');
        }
    
        // Check if user is already a member or has a pending request
        $existingMember = $entityManager->getRepository(GroupMembers::class)->findOneBy([
            'group_it' => $group,
            'user_id' => $userToAdd
        ]);
    
        if ($existingMember) {
                    //// star zedetou hadil


            if ($existingMember->isPending()) {
                $this->addFlash('warning', 'This user already has a pending request to join the group');
            } else {
                $this->addFlash('warning', 'This user is already a member of the group');
            }
            ///star wfee lena 
            return $this->redirectToRoute('app_group_view', ['id' => $groupId]);
        }
    
        // Add user as pending group member
        $member = new GroupMembers();
        $member->setGroupIt($group);
        $member->setUserId($userToAdd);
        $member->setRole('member');
        //star zedetou hadil
        $member->setStatus(GroupMembers::STATUS_PENDING);
        $member->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        //star wfee
    
        $entityManager->persist($member);
        $entityManager->flush();
    
        $this->addFlash('success', 'User invitation sent successfully!');
        return $this->redirectToRoute('app_group_view', ['id' => $groupId]);
    }

     #[Route('/group/{id}/join', name: 'app_group_join')]
    public function join(EntityManagerInterface $entityManager, SessionInterface $session, int $id): Response
    {
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Get the managed user entity
        $managedUser = $entityManager->getRepository(Users::class)->find($user->getId());
        if (!$managedUser) {
            throw $this->createNotFoundException('User not found');
        }
    
        $group = $entityManager->getRepository(UserGroups::class)->find($id);
        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }
    
        // Check if user is already a member
        $existingMember = $entityManager->getRepository(GroupMembers::class)->findOneBy([
            'group_it' => $group,
            'user_id' => $managedUser
        ]);
    
        if ($existingMember) {
            if ($existingMember->getStatus() === GroupMembers::STATUS_PENDING) {
                $this->addFlash('warning', 'Votre demande d\'adhésion est en attente d\'approbation');
            } else {
                $this->addFlash('warning', 'You are already a member of this group');
            }
            return $this->redirectToRoute('app_group_view', ['id' => $id]);
        }
    
        // Add user as pending group member
        $member = new GroupMembers();
        $member->setGroupIt($group);
        $member->setUserId($managedUser); // Use the managed user entity
        $member->setRole('member');
        $member->setStatus(GroupMembers::STATUS_PENDING);
        $member->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
    
        $entityManager->persist($member);
        $entityManager->flush();
    
        $this->addFlash('success', 'Your membership request has been sent and is pending approval');
        return $this->redirectToRoute('app_group_view', ['id' => $id]);
    }

      /**
     * Permet d'accepter ou de refuser une demande d'adhésion à un groupe
     */
    #[Route('/group-request/{id}/{action}', name: 'app_group_request')]
    public function handleGroupRequest(
        int $id,
        string $action,
        Request $request,
        EntityManagerInterface $entityManager,
        GroupMembersRepository $groupMembersRepository
    ): Response {
        $groupRequest = $groupMembersRepository->find($id);
        
        if (!$groupRequest) {
            $this->addFlash('error', 'Demande non trouvée');
            return $this->redirectToRoute('app_groups');
        }
        
        // Vérifier si l'utilisateur est connecté
        $session = $request->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            $this->addFlash('error', 'Utilisateur non connecté');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $currentUser = $entityManager->getRepository(Users::class)->find($userSession->getId());
        
        if (!$currentUser) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            $session->remove('user');
            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier que l'utilisateur est le créateur du groupe
        $group = $groupRequest->getGroupIt();
        if ($group->getCreatorId()->getId() !== $currentUser->getId()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à gérer cette demande');
            return $this->redirectToRoute('app_groups');
        }
        
        // Vérifier que la demande est en attente
        if (!$groupRequest->isPending()) {
            $this->addFlash('error', 'Cette demande a déjà été traitée');
            return $this->redirectToRoute('app_groups');
        }
        
        $userRequesting = $groupRequest->getUserId();
        $groupName = $group->getName();
        
        if ($action === 'accept') {
            $groupRequest->setStatus(GroupMembers::STATUS_ACCEPTED);
            $entityManager->flush();
            
            // Créer une notification pour l'utilisateur
            $notification = new Notification();
            $notification->setUser($userRequesting);
            $notification->setType(Notification::TYPE_GROUP_REQUEST_ACCEPTED);
            $notification->setMessage("Your request to join the group \"$groupName\" has been accepted");
            $notification->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            $notification->setLink('/group/' . $group->getId());
            
            $entityManager->persist($notification);
            $entityManager->flush();

            $this->addFlash('success', 'You have accepted the membership request');
        } else if ($action === 'reject') {
            $groupRequest->setStatus(GroupMembers::STATUS_REJECTED);
            $entityManager->flush();
            
            // Créer une notification pour l'utilisateur
            $notification = new Notification();
            $notification->setUser($userRequesting);
            $notification->setType(Notification::TYPE_GROUP_REQUEST_REJECTED);
            $notification->setMessage("Your request to join the group \"$groupName\" has been rejected");
            $notification->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            
            $entityManager->persist($notification);
            $entityManager->flush();
            
            $this->addFlash('success', 'You have declined the membership request.');
        } else {
            $this->addFlash('error', 'Action non reconnue');
        }
        
        return $this->redirectToRoute('app_groups');
    }

        /**
     * Affiche les demandes d'adhésion en attente pour les groupes de l'utilisateur
     */
    #[Route('/group/requests', name: 'app_group_requests')]
    public function viewPendingRequests(
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        GroupMembersRepository $groupMembersRepository,
        \App\Repository\UserProfileRepository $userProfileRepository
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la base de données
        $managedUser = $entityManager->getRepository(Users::class)->find($user->getId());
        if (!$managedUser) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        // Récupérer toutes les demandes d'adhésion en attente pour les groupes de l'utilisateur
        $pendingRequests = $groupMembersRepository->findPendingRequestsForUserGroups($managedUser->getId());
        
        // Récupérer les profils utilisateurs pour tous les utilisateurs qui ont fait une demande
        $userProfiles = [];
        foreach ($pendingRequests as $request) {
            $userId = $request->getUserId()->getId();
            $profile = $userProfileRepository->findOneBy(['user' => $request->getUserId()]);
            if ($profile) {
                $userProfiles[$userId] = $profile;
            }
        }
        
        // Stocker les profils dans la session pour y accéder dans le template
        $session->set('userProfiles', $userProfiles);
        
        return $this->render('group/requests.html.twig', [
            'pendingRequests' => $pendingRequests,
            'currentUser' => $managedUser,
            'userProfiles' => $userProfiles
        ]);
    }
}

