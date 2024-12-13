<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register');
    exit;
}

// Debug: Log POST data
error_log("Register POST data: " . print_r($_POST, true));

// Check if CSRF token exists
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
    $_SESSION['error'] = 'Invalid token';
    error_log("CSRF token missing - POST token: " . (isset($_POST['csrf_token']) ? 'yes' : 'no') . 
              ", Session token: " . (isset($_SESSION['csrf_token']) ? 'yes' : 'no'));
    header('Location: /register');
    exit;
}

// Validate CSRF token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid token';
    error_log("CSRF token mismatch - POST: {$_POST['csrf_token']}, Session: {$_SESSION['csrf_token']}");
    header('Location: /register');
    exit;
}

$name = trim($_POST['name']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];

// Debug: Log sanitized data
error_log("Sanitized registration data - Name: $name, Email: $email");

// Validation
if (strlen($name) < 2) {
    $_SESSION['error'] = 'Name must be at least 2 characters';
    header('Location: /register');
    exit;
}

if (!$email) {
    $_SESSION['error'] = 'Invalid email format';
    header('Location: /register');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['error'] = 'Password must be at least 8 characters';
    header('Location: /register');
    exit;
}

if ($password !== $password_confirm) {
    $_SESSION['error'] = 'Passwords do not match';
    header('Location: /register');
    exit;
}

try {
    error_log("Attempting database connection");
    
    // Use getDB() function
    $pdo = getDB();
    
    error_log("Database connection successful");
    
    // Check if email exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Email already exists';
        error_log("Registration failed: Email already exists - $email");
        header('Location: /register');
        exit;
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate UUID
    $uuid = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    error_log("Attempting to insert user - UUID: $uuid, Name: $name, Email: $email");
    
    // Insert user
    $stmt = $pdo->prepare('INSERT INTO users (id, name, email, password, created_at) VALUES (?, ?, ?, ?, NOW())');
    $result = $stmt->execute([$uuid, $name, $email, $password_hash]);
    
    if (!$result) {
        $error = $stmt->errorInfo();
        error_log("Insert failed: " . print_r($error, true));
        throw new PDOException("Failed to insert user: " . $error[2]);
    }
    
    error_log("User inserted successfully - ID: $uuid");
    
    // Set session data for the newly registered user
    $_SESSION['user_id'] = $uuid;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    
    // Redirect to wishlists page
    $_SESSION['success'] = 'Registration successful! Welcome to Family Christmas List!';
    header('Location: /wishlists');
    exit;
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['error'] = 'Registration failed. Please try again.';
    header('Location: /register');
    exit;
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['error'] = 'An unexpected error occurred. Please try again.';
    header('Location: /register');
    exit;
}
