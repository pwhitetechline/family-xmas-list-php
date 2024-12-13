<?php
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['token'])) {
        $_SESSION['error'] = 'Invalid invitation link';
        header('Location: /family-groups');
        exit;
    }

    $token = $_GET['token'];

    try {
        $pdo = getDB();
        
        // Get invitation details
        $stmt = $pdo->prepare('
            SELECT gi.*, fg.name as group_name 
            FROM group_invitations gi
            JOIN family_groups fg ON gi.group_id = fg.id
            WHERE gi.token = ? AND gi.status = ? AND gi.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ');
        $stmt->execute([$token, 'pending']);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            $_SESSION['error'] = 'Invalid or expired invitation link';
            header('Location: /family-groups');
            exit;
        }
        
        // Check if user's email matches invitation email
        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user['email'] !== $invitation['email']) {
            $_SESSION['error'] = 'This invitation was sent to a different email address';
            header('Location: /family-groups');
            exit;
        }
        
        // Check if user is already a member
        $stmt = $pdo->prepare('
            SELECT id 
            FROM family_group_members 
            WHERE group_id = ? AND user_id = ?
        ');
        $stmt->execute([$invitation['group_id'], $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'You are already a member of this group';
            header('Location: /family-groups');
            exit;
        }
        
        // Add user to group
        $stmt = $pdo->prepare('
            INSERT INTO family_group_members (group_id, user_id, role, joined_at)
            VALUES (?, ?, ?, NOW())
        ');
        $stmt->execute([$invitation['group_id'], $_SESSION['user_id'], 'member']);
        
        // Mark invitation as accepted
        $stmt = $pdo->prepare('
            UPDATE group_invitations 
            SET status = ?, accepted_at = NOW() 
            WHERE id = ?
        ');
        $stmt->execute(['accepted', $invitation['id']]);
        
        $_SESSION['success'] = "You have successfully joined the family group '{$invitation['group_name']}'";
        header("Location: /family-groups/view?id={$invitation['group_id']}");
        exit;
        
    } catch (Exception $e) {
        error_log("Error processing group invitation: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to process invitation. Please try again.';
        header('Location: /family-groups');
        exit;
    }
} else {
    // Redirect non-GET requests back to family groups
    header('Location: /family-groups');
    exit;
}
