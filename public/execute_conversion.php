<?php
/**
 * Direct SQL Execution Script for Points Conversion
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

// Get conversion parameters
$points = isset($_GET['points']) ? (int)$_GET['points'] : 100;
$devise = isset($_GET['devise']) ? $_GET['devise'] : 'TND';

// Database connection parameters
$host = 'localhost';
$dbname = 'prointegsy';
$username = 'root';
$password = '';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create SQL query
    $sql = "
    -- Paramètres
    SET @user_id = :user_id;
    SET @points_to_convert = :points;
    SET @devise = :devise;
    
    -- Récupérer les points actuels de l'utilisateur
    SELECT @current_points := points, @current_argent := argent FROM users WHERE id = @user_id;
    
    -- Calculer le montant en TND (5 TND pour 100 points)
    SET @montant_tnd = (@points_to_convert / 100) * 5;
    
    -- Convertir en devise demandée
    SET @taux_eur = 0.29;
    SET @taux_usd = 0.32;
    
    IF @devise = 'EUR' THEN
        SET @montant = ROUND(@montant_tnd * @taux_eur, 2);
    ELSEIF @devise = 'USD' THEN
        SET @montant = ROUND(@montant_tnd * @taux_usd, 2);
    ELSE
        SET @montant = @montant_tnd;
    END IF;
    
    -- Calculer les nouveaux points et argent
    SET @new_points = @current_points - @points_to_convert;
    SET @new_argent = @current_argent + @montant;
    
    -- Mettre à jour les points et l'argent de l'utilisateur
    UPDATE users SET points = @new_points, argent = @new_argent WHERE id = @user_id;
    
    -- Créer une entrée dans l'historique des points
    INSERT INTO historique_points (user_id, type, points, raison, date)
    VALUES (@user_id, 'perte', -@points_to_convert, CONCAT('Conversion en ', @devise), NOW());
    
    -- Créer une entrée dans les transactions
    INSERT INTO transaction_argent (user_id, type, montant, devise, point_convertis, date)
    VALUES (@user_id, 'conversion', @montant, @devise, @points_to_convert, NOW());
    ";
    
    // Execute the SQL
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':points', $points, PDO::PARAM_INT);
    $stmt->bindParam(':devise', $devise, PDO::PARAM_STR);
    $stmt->execute();
    
    // Set success message
    $_SESSION['success'] = "Conversion réussie! Vous avez converti $points points en $devise.";
    
    // Redirect back to conversion page
    header('Location: /points/convert');
    exit;
} catch (PDOException $e) {
    // Set error message
    $_SESSION['error'] = 'Erreur lors de la conversion: ' . $e->getMessage();
    
    // Redirect back to conversion page
    header('Location: /points/convert');
    exit;
}
