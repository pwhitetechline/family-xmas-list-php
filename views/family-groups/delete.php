<?php
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /family-groups');
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid token';
    header('Location: /family-groups');
    exit;
}

$groupId = $_POST['group_id'];

try {
    $pdo = getDB();
    
    // Verify user is admin of the group
    $stmt = $pdo->prepare('
        SELECT role 
        FROM family_group_members 
        WHERE group_id = ? AND user_id = ? AND role = ?
    ');
    $stmt->execute([$groupId, $_SESSION['user_id'], 'admin']);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'You do not have permission to delete this group';
        header('Location: /family-groups');
        exit;
    }
    
    // Delete group (members and associated wishlists will be deleted by CASCADE)
    $stmt = $pdo->prepare('DELETE FROM family_groups WHERE id = ?');
    $stmt->execute([$groupId]);
    
    $_SESSION['success'] = 'Family group deleted successfully';
} catch (Exception $e) {
    error_log("Error deleting family group: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete family group. Please try again.';
}

header('Location: /family-groups');
exit;
