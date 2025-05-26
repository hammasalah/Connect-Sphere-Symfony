<?php

namespace App\Controller\Points;

use App\Entity\Conversion;
use App\Entity\HistoriquePoints;
use App\Entity\TransactionArgent;   
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour la conversion dynamique des points
 */
class DynamicConversionController extends AbstractController
{
    /**
     * @Route("/points/dynamic-convert", name="app_dynamic_convert_points", methods={"POST", "GET"})
     */
    public function dynamicConvert(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté sans getUser()
        $session = $request->getSession();
        $user = $session->get('user');
        if (!$user) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être connecté pour effectuer cette opération.'
                ]);
            }
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer les paramètres
        $points = (int) $request->request->get('points', $request->query->get('points', 0));
        $devise = $request->request->get('devise', $request->query->get('devise', 'TND'));
        
        // Valider les paramètres
        if ($points <= 0) {
            return $this->handleError('Le nombre de points doit être supérieur à 0.', $request);
        }
        
        // Valider la devise
        if (!in_array($devise, ['TND', 'EUR', 'USD'])) {
            return $this->handleError('Devise non supportée. Veuillez choisir TND, EUR ou USD.', $request);
        }
        
        // Vérifier que l'utilisateur a suffisamment de points
        $currentPoints = $user->getPoints() ?? 0;
        if ($currentPoints < $points) {
            return $this->handleError("Points insuffisants. Vous avez $currentPoints points, mais vous essayez d'en convertir $points.", $request);
        }
        
        // Calculer le montant en TND (1 point = 0.325 TND)
        $montantTND = $points * 0.325;
        
        // Convertir en devise demandée
        switch ($devise) {
            case 'EUR':
                $montant = round($montantTND * 0.29, 2); // 1 TND = 0.29 EUR
                break;
            case 'USD':
                $montant = round($montantTND * 0.32, 2); // 1 TND = 0.32 USD
                break;
            default:
                $montant = $montantTND;
        }
        
        try {
            // Mettre à jour les points de l'utilisateur
            $newPoints = $currentPoints - $points;
            $user->setPoints($newPoints);
            
            // Mettre à jour l'argent de l'utilisateur
            $currentMoney = $user->getArgent() ?? '0.00';
            $newMoney = floatval($currentMoney) + $montant; // Conversion en float pour l'addition
            $user->setArgent((string) $newMoney);
            
            // Créer une entrée dans l'historique des points
            $historiquePoints = new HistoriquePoints();
            $historiquePoints->setUser($user);
            $historiquePoints->setType('perte');
            $historiquePoints->setPoints(-$points);
            $historiquePoints->setRaison("Conversion en $devise");
            $historiquePoints->setDate(new \DateTime());
            
            // Créer une entrée dans les transactions
            $transaction = new TransactionArgent();
            $transaction->setUser($user);
            $transaction->setType('conversion');
            $transaction->setMontant((string) $montant);
            $transaction->setDevise($devise);
            $transaction->setPointConvertis((string) $points);
            $transaction->setDate(new \DateTime());
            
            // Créer une entrée dans les conversions
            $conversion = new Conversion();
            $conversion->setUserId($user);
            $conversion->setPointsConvertis($points);
            $conversion->setMontant((string) $montant);
            $conversion->setDevise($devise);
            $conversion->setDate(new \DateTime());
            
            // Persister les entités
            $entityManager->persist($historiquePoints);
            $entityManager->persist($transaction);
            $entityManager->persist($conversion);
            $entityManager->flush();
            
            // Retourner la réponse
            $responseData = [
                'success' => true,
                'message' => "Conversion réussie! Vous avez converti $points points en $montant $devise.",
                'data' => [
                    'points_convertis' => $points,
                    'montant' => $montant,
                    'devise' => $devise,
                    'new_points' => $newPoints,
                    'new_money' => $newMoney,
                    // Ajouter ces champs pour correspondre à ce que le JavaScript attend
                    'newPoints' => $newPoints,
                    'newArgent' => $newMoney
                ]
            ];
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse($responseData);
            }
            
            // Ajouter un message flash
            $this->addFlash('success', $responseData['message']);
            
            // Rediriger vers la page de conversion
            return $this->redirectToRoute('app_convert_points');
            
        } catch (\Exception $e) {
            return $this->handleError('Erreur lors de la conversion: ' . $e->getMessage(), $request);
        }
    }
    
    /**
     * Gère les erreurs en fonction du type de requête
     */
    private function handleError(string $message, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => $message
            ]);
        }
        
        $this->addFlash('error', $message);
        return $this->redirectToRoute('app_convert_points');
    }
}