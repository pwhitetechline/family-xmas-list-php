<?php
session_start();

// Set the project root directory
define('PROJECT_ROOT', dirname(__DIR__));

// Require composer autoloader
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT);
$dotenv->load();

// Include functions
require_once PROJECT_ROOT . '/includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the request URI and method
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove leading and trailing slashes
$request_path = trim($request_uri, '/');

// Define routes and their corresponding views
$routes = [
    '' => ['file' => 'home.php', 'auth' => false],
    'login' => ['file' => 'auth/login.php', 'auth' => false],
    'register' => ['file' => 'auth/register.php', 'auth' => false],
    'auth/register' => ['file' => 'auth/register.php', 'auth' => false],
    'auth/logout' => ['file' => 'auth/logout.php', 'auth' => true],
    'wishlists' => ['file' => 'wishlists/index.php', 'auth' => true],
    'wishlists/create' => ['file' => 'wishlists/create.php', 'auth' => true],
    'wishlists/delete' => ['file' => 'wishlists/delete.php', 'auth' => true],
    'wishlists/view' => ['file' => 'wishlists/view.php', 'auth' => true],
    'wishlists/add-item' => ['file' => 'wishlists/add-item.php', 'auth' => true],
    'wishlists/edit-item' => ['file' => 'wishlists/edit-item.php', 'auth' => true],
    'wishlists/delete-item' => ['file' => 'wishlists/delete-item.php', 'auth' => true],
    'family-groups' => ['file' => 'family-groups/index.php', 'auth' => true],
    'family-groups/create' => ['file' => 'family-groups/create.php', 'auth' => true],
    'family-groups/delete' => ['file' => 'family-groups/delete.php', 'auth' => true],
    'family-groups/view' => ['file' => 'family-groups/view.php', 'auth' => true],
    'family-groups/edit' => ['file' => 'family-groups/edit.php', 'auth' => true],
    'family-groups/invite' => ['file' => 'family-groups/invite.php', 'auth' => true],
    'family-groups/join' => ['file' => 'family-groups/join.php', 'auth' => true],
];

// Check if the route exists
if (isset($routes[$request_path])) {
    $route = $routes[$request_path];
    
    // Check if authentication is required
    if ($route['auth'] && !isLoggedIn()) {
        $_SESSION['error'] = 'Please login to access this page.';
        header('Location: /login');
        exit;
    }
    
    // Load the view
    require_once PROJECT_ROOT . '/views/' . $route['file'];
    exit;
}

// 404 Not Found
header("HTTP/1.0 404 Not Found");
require_once PROJECT_ROOT . '/views/404.php';
