<?php
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /wishlists');
    exit;
}

if (!validateToken($_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid token';
    header('Location: /wishlists');
    exit;
}

$wishlist_id = $_POST['wishlist_id'];

try {
    $pdo = getDB();
    
    // Verify ownership
    $stmt = $pdo->prepare('SELECT user_id FROM wishlists WHERE id = ?');
    $stmt->execute([$wishlist_id]);
    $wishlist = $stmt->fetch();
    
    if (!$wishlist || $wishlist['user_id'] !== $_SESSION['user_id']) {
        $_SESSION['error'] = 'Wishlist not found';
        header('Location: /wishlists');
        exit;
    }
    
    // Delete wishlist (items will be deleted by foreign key cascade)
    $stmt = $pdo->prepare('DELETE FROM wishlists WHERE id = ? AND user_id = ?');
    $stmt->execute([$wishlist_id, $_SESSION['user_id']]);
    
    $_SESSION['success'] = 'Wishlist deleted successfully!';
} catch (Exception $e) {
    error_log("Error deleting wishlist: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: /wishlists');
exit;
