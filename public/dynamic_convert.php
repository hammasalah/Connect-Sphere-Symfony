<?php
// Script de conversion dynamique de points en argent

// Démarrer la session
session_start();

// Vérifier si la requête est en AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Paramètres de connexion à la base de données
$host = 'localhost';
$dbname = 'prointegsy';
$username = 'root';
$password = '';

// Récupérer les paramètres
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1);
$points = isset($_POST['points']) ? (int)$_POST['points'] : (isset($_GET['points']) ? (int)$_GET['points'] : 0);
$devise = isset($_POST['devise']) ? $_POST['devise'] : (isset($_GET['devise']) ? $_GET['devise'] : 'TND');

// Valider les paramètres
if ($points <= 0) {
    $response = [
        'success' => false,
        'message' => 'Le nombre de points doit être supérieur à 0.'
    ];
    outputResponse($response, $isAjax);
    exit;
}

// Valider la devise
if (!in_array($devise, ['TND', 'EUR', 'USD'])) {
    $response = [
        'success' => false,
        'message' => 'Devise non supportée. Veuillez choisir TND, EUR ou USD.'
    ];
    outputResponse($response, $isAjax);
    exit;
}

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les données de l'utilisateur
    $stmt = $pdo->prepare("SELECT id, points, argent FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $response = [
            'success' => false,
            'message' => 'Utilisateur non trouvé.'
        ];
        outputResponse($response, $isAjax);
        exit;
    }
    
    $currentPoints = (int)$user['points'];
    $currentMoney = (float)$user['argent'];
    
    // Vérifier que l'utilisateur a suffisamment de points
    if ($currentPoints < $points) {
        $response = [
            'success' => false,
            'message' => "Points insuffisants. Vous avez $currentPoints points, mais vous essayez d'en convertir $points."
        ];
        outputResponse($response, $isAjax);
        exit;
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
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    try {
        // Mettre à jour les points et l'argent de l'utilisateur
        $newPoints = $currentPoints - $points;
        $newMoney = $currentMoney + $montant;
        
        $stmt = $pdo->prepare("UPDATE users SET points = ?, argent = ? WHERE id = ?");
        $stmt->execute([$newPoints, $newMoney, $userId]);
        
        // Créer une entrée dans l'historique des points
        $stmt = $pdo->prepare("INSERT INTO historique_points (user_id, type, points, raison, date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, 'perte', -$points, "Conversion en $devise"]);
        
        // Créer une entrée dans les transactions
        $stmt = $pdo->prepare("INSERT INTO transaction_argent (user_id, type, montant, devise, point_convertis, date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, 'conversion', $montant, $devise, $points]);
        
        // Valider la transaction
        $pdo->commit();
        
        $response = [
            'success' => true,
            'message' => "Conversion réussie! Vous avez converti $points points en $montant $devise.",
            'data' => [
                'points_convertis' => $points,
                'montant' => $montant,
                'devise' => $devise,
                'new_points' => $newPoints,
                'new_money' => $newMoney
            ]
        ];
        
        outputResponse($response, $isAjax);
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollBack();
        
        $response = [
            'success' => false,
            'message' => 'Erreur lors de la conversion: ' . $e->getMessage()
        ];
        
        outputResponse($response, $isAjax);
    }
    
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()
    ];
    
    outputResponse($response, $isAjax);
}

/**
 * Affiche la réponse au format approprié
 * 
 * @param array $response Les données de réponse
 * @param bool $isAjax Si la requête est en AJAX
 */
function outputResponse($response, $isAjax) {
    if ($isAjax) {
        // Réponse JSON pour les requêtes AJAX
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Réponse HTML pour les requêtes normales
        if ($response['success']) {
            // Afficher un message de succès
            echo '<!DOCTYPE html>
<html>
<head>
    <title>Conversion Réussie</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #28a745;
            margin-bottom: 20px;
        }

        .success-details {
            padding: 15px;
            background-color: #d4edda;
            border-radius: 5px;
            color: #155724;
            margin-bottom: 20px;
            text-align: left;
        }

        .success-details p {
            margin: 10px 0;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Conversion Réussie!</h1>

        <div class="success-details">
            <p><strong>Points convertis:</strong> ' . $response['data']['points_convertis'] . '</p>
            <p><strong>Montant reçu:</strong> ' . $response['data']['montant'] . ' ' . $response['data']['devise'] . '</p>
            <p><strong>Nouveaux points:</strong> ' . $response['data']['new_points'] . '</p>
            <p><strong>Nouvel argent:</strong> ' . $response['data']['new_money'] . '</p>
        </div>

        <a href="/points/convert" class="back-button">Retour à la page de conversion</a>
    </div>
</body>
</html>';
        } else {
            // Afficher un message d'erreur
            echo '<!DOCTYPE html>
<html>
<head>
    <title>Erreur de Conversion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }

        .error-message {
            padding: 15px;
            background-color: #f8d7da;
            border-radius: 5px;
            color: #721c24;
            margin-bottom: 20px;
            text-align: left;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Erreur de Conversion</h1>

        <div class="error-message">
            <p><strong>Message d\'erreur:</strong> ' . htmlspecialchars($response['message']) . '</p>
        </div>

        <a href="/points/convert" class="back-button">Retour à la page de conversion</a>
    </div>
</body>
</html>';
        }
    }
}