<?php

namespace App\Controller\chatbot;

use League\CommonMark\CommonMarkConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface; // Pour le logging

class ChatbotController extends AbstractController
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private string $ollamaApiUrl;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger, string $ollamaApiUrl)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->ollamaApiUrl = $ollamaApiUrl;
    }

    #[Route('/chatbot', name: 'app_chatbot', methods: ['GET'])]
    public function index(): Response
    {
        // Rend la page HTML principale du chatbot
        return $this->render('chatbot/chatbot.html.twig');
    }

    #[Route('/api/chatbot/ask', name: 'api_chatbot_ask', methods: ['POST'])]
    public function ask(Request $request): Response
    {
        // Récupère la question de l'utilisateur depuis la requête AJAX
        $data = json_decode($request->getContent(), true);
        $userInput = $data['prompt'] ?? null;

        // Vérifie si la question est vide
        if (empty($userInput)) {
            return new JsonResponse(['error' => 'Prompt is required.'], Response::HTTP_BAD_REQUEST);
        }

        // Prépare le prompt complet avec les instructions pour le modèle
        $guidance = "Tu es le chatbot officiel de ConnectSphere, une application dédiée à l'organisation et la gestion d'événements. Ta mission est d'aider les utilisateurs en leur présentant clairement les fonctionnalités de l'application et en répondant à leurs questions concernant l'organisation d'événements. Tu dois :" .
                    "- Introduire les principales fonctionnalités de ConnectSphere (consultation des évènements, inscription aux événements, recherche d'événements, création d'événements, gestion de profil, etc.)." .
                    "- Fournir des réponses claires, précises et amicales." .
                    "- Guider l'utilisateur pour trouver les informations dont il a besoin sur l'organisation des événements (localisation, horaires, modalités d'inscription, etc.)." .
                    "- Encourager l'utilisateur à explorer l'application pour découvrir tous les outils disponibles pour faciliter la participation aux événements." .
                    "Reste toujours courtois et utile, et adapte tes réponses selon les questions spécifiques posées par les utilisateurs, et n'oublie pas de donner des reponses très bref et précises";
        $fullPrompt = $userInput . "\n\n" . $guidance; // Combine l'input utilisateur et les instructions

        // Prépare la requête pour l'API Ollama
        $ollamaPayload = [
            'model' => 'llama3.2', // Utilise le modèle que vous avez ('llama3.2:latest' peut aussi marcher)
            'prompt' => $fullPrompt,
            'stream' => true // Demande une réponse en streaming
        ];

        try {
            // Exécute la requête HTTP vers Ollama
            $response = $this->client->request('POST', $this->ollamaApiUrl, [
                'json' => $ollamaPayload,
                'headers' => ['Accept' => 'application/x-ndjson'], // Attend du JSON délimité par des nouvelles lignes
                'timeout' => 120, // Augmente le timeout si nécessaire
            ]);

            // Crée une réponse streamée pour renvoyer les données au fur et à mesure au navigateur
            $streamedResponse = new StreamedResponse(function () use ($response) {
                // Initialise le convertisseur Markdown
                $markdownConverter = new CommonMarkConverter([
                    'html_input' => 'strip', // Sécurité : supprime les tags HTML non désirés du Markdown
                    'allow_unsafe_links' => false, // Sécurité : désactive les liens potentiellement dangereux
                ]);

                $markdownBuffer = ''; // Buffer pour accumuler les morceaux de Markdown

                try {
                    // Itère sur les morceaux (chunks) reçus de l'API Ollama
                    foreach ($this->client->stream($response) as $chunk) {
                        // Gère le cas où le chunk est un timeout
                        if ($chunk->isTimeout()) {
                            $this->logger->warning('Ollama stream timeout occurred.');
                            echo json_encode(['error' => 'Request timed out.'])."\n"; flush(); continue;
                        }

                        // Vérifie si c'est le dernier chunk de la réponse HTTP globale
                        $isLastHttpChunk = $chunk->isLast();

                        // Récupère le contenu du chunk
                        $content = $chunk->getContent();
                        // Sépare le contenu en lignes (Ollama envoie du NDJSON)
                        $lines = explode("\n", trim($content));

                        // Traite chaque ligne JSON reçue
                        foreach ($lines as $line) {
                            if (empty($line)) continue; // Ignore les lignes vides

                            // Tente de décoder la ligne JSON
                            $data = json_decode($line, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $this->logger->error('JSON decode error: ' . json_last_error_msg() . ' for line: ' . $line);
                                continue; // Ignore la ligne malformée
                            }

                            // Si la ligne contient un morceau de réponse Markdown
                            if (isset($data['response']) && !empty($data['response'])) {
                                // Ajoute le morceau Markdown au buffer
                                $markdownBuffer .= $data['response'];

                                // --- Logique d'envoi bufferisé ---
                                // Envoie le HTML converti seulement si le buffer se termine
                                // par une nouvelle ligne (probable fin de paragraphe/bloc)
                                // OU si c'est la fin signalée par Ollama ('done' ou dernier chunk HTTP)
                                $ollamaDone = (isset($data['done']) && $data['done'] === true);
                                if (str_ends_with($markdownBuffer, "\n") || $ollamaDone || $isLastHttpChunk) {
                                    if (!empty($markdownBuffer)) {
                                        $this->logger->debug("Converting Markdown buffer (ends with newline or done): " . $markdownBuffer);
                                        $htmlToSend = $markdownConverter->convert($markdownBuffer)->getContent();
                                        // Envoie le bloc HTML converti
                                        echo json_encode(['html' => $htmlToSend]) . "\n";
                                        flush(); // Force l'envoi au navigateur
                                        // Réinitialise le buffer après envoi
                                        $markdownBuffer = '';
                                    }
                                }
                                // --- Fin Logique d'envoi ---

                            // Si la ligne contient une erreur Ollama
                            } elseif (isset($data['error'])) {
                                $this->logger->error('Ollama API error received: ' . $data['error']);
                                // Envoie d'abord le contenu restant du buffer s'il y en a
                                if (!empty($markdownBuffer)) {
                                    $htmlToSend = $markdownConverter->convert($markdownBuffer)->getContent();
                                    echo json_encode(['html' => $htmlToSend]) . "\n";
                                    $markdownBuffer = '';
                                }
                                // Envoie ensuite l'erreur
                                echo json_encode(['error' => $data['error']]) . "\n";
                                flush();
                            }

                            // Si Ollama signale explicitement la fin ('done: true')
                            if (isset($data['done']) && $data['done'] === true) {
                                $this->logger->info('Ollama signaled done=true.');
                                // Assure l'envoi du dernier contenu du buffer
                                if (!empty($markdownBuffer)) {
                                    $htmlToSend = $markdownConverter->convert($markdownBuffer)->getContent();
                                    echo json_encode(['html' => $htmlToSend]) . "\n";
                                    $markdownBuffer = '';
                                }
                                // Envoie le signal 'done' au frontend
                                echo json_encode(['done' => true]) . "\n";
                                flush();
                                return; // Arrête le traitement du stream côté serveur
                            }
                        } // Fin foreach line

                        // Si c'était le dernier chunk HTTP et qu'il reste du contenu dans le buffer
                        // (Ollama n'a peut-être pas envoyé 'done: true' explicitement)
                        if ($isLastHttpChunk && !empty($markdownBuffer)) {
                            $this->logger->debug("Last HTTP chunk, sending final buffer: " . $markdownBuffer);
                            $htmlToSend = $markdownConverter->convert($markdownBuffer)->getContent();
                            echo json_encode(['html' => $htmlToSend]) . "\n";
                            // Envoie aussi un signal 'done' pour être sûr que le frontend termine
                            echo json_encode(['done' => true]) . "\n";
                            flush();
                        }

                    } // Fin foreach chunk HTTP stream
                } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
                     $this->logger->error('Ollama stream transport error: '.$e->getMessage());
                     echo json_encode(['error' => 'Connection issue with chatbot service.'])."\n"; flush();
                } catch (\Throwable $e) { // Attrape toute autre erreur potentielle
                     $this->logger->error('Error during chatbot stream processing: '.$e->getMessage());
                     echo json_encode(['error' => 'Internal error processing response.'])."\n"; flush();
                }
            });

            // Définit les en-têtes pour la réponse streamée
            $streamedResponse->headers->set('Content-Type', 'application/x-ndjson');
            $streamedResponse->headers->set('X-Accel-Buffering', 'no'); // Important pour Nginx/proxies
            return $streamedResponse;

        // Gère les erreurs de connexion initiale à Ollama
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $this->logger->error('Ollama request transport error (initial connection): '.$e->getMessage());
            return new JsonResponse(['error' => 'Could not connect to the chatbot service.'], Response::HTTP_SERVICE_UNAVAILABLE);
        // Gère d'autres erreurs lors de la préparation de la requête
        } catch (\Throwable $e) {
             $this->logger->error('Ollama request preparation error: '.$e->getMessage());
            return new JsonResponse(['error' => 'Error communicating with the chatbot service.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}