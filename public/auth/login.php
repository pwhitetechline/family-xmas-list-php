<?php
session_start();
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login');
    exit;
}

if (!validateToken($_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid token';
    header('Location: /login');
    exit;
}

$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$password = $_POST['password'];

if (!$email) {
    $_SESSION['error'] = 'Invalid email format';
    header('Location: /login');
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['csrf_token'] = generateToken();
        header('Location: /wishlists');
    } else {
        $_SESSION['error'] = 'Invalid email or password';
        header('Location: /login');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: /login');
}
exit;
