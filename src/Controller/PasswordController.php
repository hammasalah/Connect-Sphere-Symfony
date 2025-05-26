<?php

namespace App\Controller;

use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, UsersRepository $usersRepository): Response
    {
        $email = $request->request->get('email');
        $error = null;
        $success = null;

        if ($request->isMethod('POST')) {
            $user = $usersRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $error = "❌ No user found with this email.";
            } else {
                $plainPassword = $user->getPassword();
                $displayName = $user->getUsername() ?? 'User';

                try {
                    // Solution avec mot de passe encodé URL
                    $dsn = 'gmail+smtp://ayaabdelhamid628@gmail.com:pvzr%20pgba%20kzvi%20vaet@default';
                    $transport = Transport::fromDsn($dsn);
                    $mailer = new Mailer($transport);

                    $message = (new Email())
                        ->from('ayaabdelhamid628@gmail.com')
                        ->to($email)
                        ->subject('🔐 Your GestCom password')
                        ->text("Hello {$displayName},\n\nHere is your password: {$plainPassword}\n\n— ConnectSphere");

                    $mailer->send($message);
                    $success = "✅ Email sent successfully !";
                } catch (TransportExceptionInterface $e) {
                    $error = "❌ Sending error : " . $e->getMessage();
                }
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'email' => $email,
            'error' => $error,
            'success' => $success,
        ]);
    }
}