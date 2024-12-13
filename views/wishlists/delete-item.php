<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /wishlists');
    exit;
}

$item_id = $_POST['item_id'] ?? null;
$wishlist_id = $_POST['wishlist_id'] ?? null;

if (!$item_id || !$wishlist_id) {
    $_SESSION['error'] = 'Missing required fields';
    header('Location: /wishlists/view?id=' . $wishlist_id);
    exit;
}

try {
    $pdo = getDB();
    
    // Verify item exists and belongs to user's wishlist
    $stmt = $pdo->prepare('
        SELECT wi.* 
        FROM wishlist_items wi 
        JOIN wishlists w ON wi.wishlist_id = w.id 
        WHERE wi.id = ? AND w.user_id = ?
    ');
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        $_SESSION['error'] = 'Invalid item';
        header('Location: /wishlists/view?id=' . $wishlist_id);
        exit;
    }
    
    // Delete the item
    $stmt = $pdo->prepare('DELETE FROM wishlist_items WHERE id = ?');
    $stmt->execute([$item_id]);
    
    $_SESSION['success'] = 'Item deleted successfully';
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: /wishlists/view?id=' . $wishlist_id);
exit;
