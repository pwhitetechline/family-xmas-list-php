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

$title = trim($_POST['title']);
$groupId = !empty($_POST['group_id']) ? trim($_POST['group_id']) : null;

if (strlen($title) < 1) {
    $_SESSION['error'] = 'Title is required';
    header('Location: /wishlists');
    exit;
}

try {
    $pdo = getDB();

    // If this is a group wishlist, verify user is a member of the group
    if ($groupId) {
        $stmt = $pdo->prepare('
            SELECT 1 
            FROM family_group_members 
            WHERE group_id = ? AND user_id = ?
        ');
        $stmt->execute([$groupId, $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            $_SESSION['error'] = 'You are not a member of this group';
            header('Location: /wishlists');
            exit;
        }
    }
    
    // Create the wishlist
    $stmt = $pdo->prepare('
        INSERT INTO wishlists (id, user_id, group_id, title, description) 
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        generateUUID(),
        $_SESSION['user_id'],
        $groupId,
        $title,
        ''
    ]);
    
    $_SESSION['success'] = 'Wishlist created successfully!';
} catch (Exception $e) {
    error_log("Error creating wishlist: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

header('Location: /wishlists');
exit;
