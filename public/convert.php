<?php
/**
 * Direct Points Conversion Script
 * This script directly updates the database to convert points to money
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

// Get user from session
$user = $_SESSION['user'];
$userId = $user->getId();

// Database connection parameters
$host = 'localhost';
$dbname = 'prointegsy';
$username = 'root';
$password = '';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get conversion parameters
    $points = isset($_GET['points']) ? (int)$_GET['points'] : 100;
    $devise = isset($_GET['devise']) ? $_GET['devise'] : 'TND';
    
    // Validate points
    if ($points <= 0 || $points % 100 !== 0) {
        $_SESSION['error'] = 'Le nombre de points doit être un multiple positif de 100.';
        header('Location: /points/convert');
        exit;
    }
    
    // Validate devise
    if (!in_array($devise, ['TND', 'EUR', 'USD'])) {
        $_SESSION['error'] = 'Devise non supportée. Veuillez choisir TND, EUR ou USD.';
        header('Location: /points/convert');
        exit;
    }
    
    // Get user's current points
    $stmt = $pdo->prepare("SELECT points, argent FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        $_SESSION['error'] = 'Utilisateur non trouvé.';
        header('Location: /points/convert');
        exit;
    }
    
    $currentPoints = $userData['points'];
    $currentArgent = $userData['argent'];
    
    // Check if user has enough points
    if ($currentPoints < $points) {
        $_SESSION['error'] = 'Vous n\'avez pas assez de points pour cette conversion.';
        header('Location: /points/convert');
        exit;
    }
    
    // Calculate amount in TND (5 TND for 100 points)
    $montantTND = ($points / 100) * 5;
    
    // Convert to requested currency
    $rates = [
        'TND' => 1.0,
        'EUR' => 0.29, // 1 TND = 0.29 EUR
        'USD' => 0.32  // 1 TND = 0.32 USD
    ];
    
    $montant = round($montantTND * $rates[$devise], 2);
    
    // Update user's points and money
    $newPoints = $currentPoints - $points;
    $newArgent = $currentArgent + $montant;
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Update user's points and money
        $stmt = $pdo->prepare("UPDATE users SET points = ?, argent = ? WHERE id = ?");
        $stmt->execute([$newPoints, $newArgent, $userId]);
        
        // Create points history entry
        $stmt = $pdo->prepare("INSERT INTO historique_points (user_id, type, points, raison, date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, 'perte', -$points, 'Conversion en ' . $devise]);
        
        // Create transaction entry
        $stmt = $pdo->prepare("INSERT INTO transaction_argent (user_id, type, montant, devise, point_convertis, date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, 'conversion', $montant, $devise, $points]);
        
        // Commit transaction
        $pdo->commit();
        
        // Update session user
        $user->setPoints($newPoints);
        $user->setArgent($newArgent);
        $_SESSION['user'] = $user;
        
        // Set success message
        $_SESSION['success'] = 'Conversion réussie! Vous avez reçu ' . $montant . ' ' . $devise;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = 'Une erreur est survenue lors de la conversion: ' . $e->getMessage();
    }
    
    // Redirect back to conversion page
    header('Location: /points/convert');
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = 'Erreur de connexion à la base de données: ' . $e->getMessage();
    header('Location: /points/convert');
    exit;
}
