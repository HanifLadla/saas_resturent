<?php
session_start();
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_name = trim($_POST['restaurant_name'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirmation = $_POST['password_confirmation'] ?? '';
    
    if (empty($restaurant_name)) $errors[] = 'Restaurant name is required';
    if (empty($owner_name)) $errors[] = 'Owner name is required';
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    if ($password !== $password_confirmation) $errors[] = 'Passwords do not match';
    
    if (empty($errors)) {
        $pdo = getDBConnection();
        
        if ($pdo === null) {
            $errors[] = 'Database connection failed. Please ensure MySQL is running.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email already registered';
                } else {
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("INSERT INTO restaurants (name, email, phone, address, status, subscription_status, subscription_expires_at) VALUES (?, ?, ?, ?, 'active', 'trial', DATE_ADD(NOW(), INTERVAL 30 DAY))");
                    $stmt->execute([$restaurant_name, $email, $phone, $address]);
                    $restaurant_id = $pdo->lastInsertId();
                    
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (restaurant_id, name, email, password, role, status) VALUES (?, ?, ?, ?, 'restaurant_admin', 'active')");
                    $stmt->execute([$restaurant_id, $owner_name, $email, $hashed_password]);
                    
                    $pdo->commit();
                    
                    $_SESSION['success'] = 'Registration successful! Please login with your credentials.';
                    header('Location: login.php');
                    exit;
                }
            } catch (PDOException $e) {
                if (isset($pdo)) $pdo->rollBack();
                $errors[] = 'Registration failed. Please try again later.';
            }
        }
    }
    
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $_POST;
    header('Location: register.php');
    exit;
}
?>