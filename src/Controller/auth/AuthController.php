<?php

namespace App\Controller\auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\RegistrationFormType;
use App\Entity\Users;
use App\Entity\Category;
use App\Entity\UserIntrests;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        // If user is already logged in
        if ($session->get('user')) {
            //return $this->redirectToRoute('');
        }
    
        $error = null;
        $lastEmail = $request->request->get('email', '');
    
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
    
            $user = $entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);
    
            if (!$user || $user->getPassword() !== $password) {
                $error = 'Invalid credentials';
            } else {
                $session->set('user', $user);
                return $this->redirectToRoute('app_home');
            }
        }
    
        return $this->render('auth/login.html.twig', [
            'error' => $error,
            'last_username' => $lastEmail
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->remove('user');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Set basic user data
                $user->setCreatedAt(date('Y-m-d H:i:s'));
                $user->setPoints(0);
                $user->setArgent(0);
                
                // Store plain password (not recommended for production)
                $plainPassword = $form->get('plainPassword')->getData();
                $user->setPassword($plainPassword);

                // Save the user
                $entityManager->persist($user);
                $entityManager->flush();
                
                // Store user in session for the category selection step
                $session->set('registered_user', $user->getId());
                
                $this->addFlash('success', 'Registration successful! Please select your interests.');
                return $this->redirectToRoute('app_category_selection');
            } else {
                // Collect all form errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', implode('<br>', $errors));
            }
        }

        return $this->render('auth/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
    
    #[Route('/category-selection', name: 'app_category_selection')]
    public function categorySelection(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        // Check if user just registered
        $userId = $session->get('registered_user');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        $user = $entityManager->getRepository(Users::class)->find($userId);
        if (!$user) {
            $this->addFlash('error', 'User not found. Please register again.');
            return $this->redirectToRoute('app_login');
        }
        
        // Get all categories or create default ones if none exist
        $categories = $entityManager->getRepository(Category::class)->findAll();
        
        if (empty($categories)) {
            $this->createDefaultCategories($entityManager);
            $categories = $entityManager->getRepository(Category::class)->findAll();
        }
        
        if ($request->isMethod('POST')) {
            $selectedCategories = $request->request->all()['categories'] ?? [];
            
            if (!empty($selectedCategories)) {
                // First, remove any existing interests for this user
                $existingInterests = $entityManager->getRepository(UserIntrests::class)->findBy(['user_id' => $user]);
                foreach ($existingInterests as $interest) {
                    $entityManager->remove($interest);
                }
                
                // Add new selected categories
                foreach ($selectedCategories as $categoryId) {
                    $category = $entityManager->getRepository(Category::class)->find($categoryId);
                    if ($category) {
                        $userInterest = new UserIntrests();
                        $userInterest->setUserId($user);
                        $userInterest->setCategoryId($category);
                        $entityManager->persist($userInterest);
                    }
                }
                
                $entityManager->flush();
                
                // Clear the registration session variable
                $session->remove('registered_user');
                
                // Log the user in
                $session->set('user', $user);
                
                $this->addFlash('success', 'Thank you for selecting your interests!');
                return $this->redirectToRoute('app_dashboard'); // Redirect to dashboard instead of login
            } else {
                $this->addFlash('error', 'Please select at least one category.');
            }
        }
        
        return $this->render('auth/category_selection.html.twig', [
            'categories' => $categories,
        ]);
    }
    
    private function createDefaultCategories(EntityManagerInterface $entityManager): void
    {
        $defaultCategories = [
            'Sport' => '🏀',
            'Music' => '🎵',
            'Art' => '🎨',
            'Technology' => '💻',
            'Science' => '🔬',
            'Food' => '🍔',
            'Travel' => '✈️',
            'Fashion' => '👗',
            'Books' => '📚',
            'Gaming' => '🎮',
            'Social' => '👥',
            'Culture' => '🎭',
            'Education' => '🎓'
        ];
        
        foreach ($defaultCategories as $name => $icon) {
            $category = new Category();
            $category->setName($name);
            
            // If your Category entity has an icon property, uncomment this:
            // $category->setIcon($icon);
            
            $entityManager->persist($category);
        }
        
        $entityManager->flush();
    }
    

}