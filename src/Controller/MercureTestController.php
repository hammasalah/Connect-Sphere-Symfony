<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureTestController extends AbstractController
{
    #[Route('/mercure/test', name: 'mercure_test')]
    public function test(): Response
    {
        return $this->render('messagerie/mercure_test.html.twig');
    }

    #[Route('/mercure/publish', name: 'mercure_publish')]
    public function publish(HubInterface $hub): Response
    {
        $update = new Update(
            'https://example.com/chat/1',
            json_encode(value: [
                'message' => 'Hello! Test message at ' . (new \DateTime())->format('H:i:s')
            ])
        );

        $hub->publish($update);

        return new Response('Message published!');
    }
}
