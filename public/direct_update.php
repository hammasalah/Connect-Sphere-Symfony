<?php
// Direct database update script - No framework dependencies

// Database connection parameters - MODIFY THESE TO MATCH YOUR SETUP
$host = 'localhost';
$dbname = 'prointegsy';
$username = 'root';
$password = '';

// Get parameters from URL
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1; // Default to user ID 1
$points = isset($_GET['points']) ? (int)$_GET['points'] : 100; // Default to 100 points
$devise = isset($_GET['devise']) ? $_GET['devise'] : 'TND'; // Default to TND

// Validate devise
if (!in_array($devise, ['TND', 'EUR', 'USD'])) {
    die("Devise invalide. Utilisez TND, EUR ou USD.");
}

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $pdo->beginTransaction();

    // 1. Get current user data
    $stmt = $pdo->prepare("SELECT id, points, argent FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Utilisateur non trouvé.");
    }

    $currentPoints = (int)$user['points'];
    $currentMoney = (float)$user['argent'];

    // 2. Check if user has enough points
    if ($currentPoints < $points) {
        die("Points insuffisants. Vous avez $currentPoints points, mais vous essayez d'en convertir $points.");
    }

    // 3. Calculate conversion amount
    $montantTND = ($points / 100) * 5; // 100 points = 5 TND

    // Convert to requested currency
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

    // 4. Calculate new values
    $newPoints = $currentPoints - $points;
    $newMoney = $currentMoney + $montant;

    // 5. Update user's points and money
    $stmt = $pdo->prepare("UPDATE users SET points = ?, argent = ? WHERE id = ?");
    $stmt->execute([$newPoints, $newMoney, $userId]);

    // 6. Create entry in historique_points
    $stmt = $pdo->prepare("INSERT INTO historique_points (user_id, type, points, raison, date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, 'perte', -$points, "Conversion en $devise"]);

    // 7. Create entry in transaction_argent
    $stmt = $pdo->prepare("INSERT INTO transaction_argent (user_id, type, montant, devise, point_convertis, date) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, 'conversion', $montant, $devise, $points]);

    // Commit transaction
    $pdo->commit();

    // Output success message
    echo "<!DOCTYPE html>
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
            color: #4CAF50;
        }

        .details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .details p {
            margin: 10px 0;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .back-link:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Conversion Réussie!</h1>

        <div class='details'>
            <p><strong>Points convertis:</strong> $points</p>
            <p><strong>Montant reçu:</strong> $montant $devise</p>
            <p><strong>Nouveaux points:</strong> $newPoints</p>
            <p><strong>Nouvel argent:</strong> $newMoney</p>
        </div>

        <a href='/points/convert' class='back-link'>Retour à la page de conversion</a>
    </div>
</body>
</html>";

} catch (PDOException $e) {
    // Rollback transaction on error if PDO connection was established
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Output error message with HTML formatting
    echo "<!DOCTYPE html>
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
        }

        .error-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8d7da;
            border-radius: 5px;
            color: #721c24;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .back-link:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Erreur lors de la conversion</h1>

        <div class='error-details'>
            <p><strong>Message d'erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Veuillez vérifier les paramètres de connexion à la base de données et réessayer.</p>
        </div>

        <a href='/points/convert' class='back-link'>Retour à la page de conversion</a>
    </div>
</body>
</html>";
}
?>
