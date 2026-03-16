<?php
session_start();
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        $pdo = getDBConnection();
        
        if ($pdo === null) {
            $errors[] = 'Database connection failed. Please ensure MySQL is running.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['restaurant_id'] = $user['restaurant_id'];
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $errors[] = 'Invalid email or password';
                }
            } catch (PDOException $e) {
                $errors[] = 'Login failed. Please try again.';
            }
        }
    }
    
    $_SESSION['errors'] = $errors;
    header('Location: login.php');
    exit;
}
?>