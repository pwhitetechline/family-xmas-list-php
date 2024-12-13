<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /wishlists');
    exit;
}

$wishlist_id = $_POST['wishlist_id'] ?? null;
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ? floatval($_POST['price']) : null;
$url = $_POST['url'] ?? '';

if (!$wishlist_id || !$name) {
    $_SESSION['error'] = 'Missing required fields';
    header('Location: /wishlists/view?id=' . $wishlist_id);
    exit;
}

try {
    $pdo = getDB();
    
    // Verify wishlist exists and belongs to user
    $stmt = $pdo->prepare('SELECT user_id FROM wishlists WHERE id = ?');
    $stmt->execute([$wishlist_id]);
    $wishlist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$wishlist || $wishlist['user_id'] !== $_SESSION['user_id']) {
        $_SESSION['error'] = 'Invalid wishlist';
        header('Location: /wishlists');
        exit;
    }
    
    // Generate UUID for the item
    $item_id = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    // Add the item
    $stmt = $pdo->prepare('INSERT INTO wishlist_items (id, wishlist_id, name, description, price, url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$item_id, $wishlist_id, $name, $description, $price, $url]);
    
    $_SESSION['success'] = 'Item added successfully';
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: /wishlists/view?id=' . $wishlist_id);
exit;
