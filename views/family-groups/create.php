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

$name = trim($_POST['name']);
$description = trim($_POST['description'] ?? '');

// Validate input
if (strlen($name) < 2) {
    $_SESSION['error'] = 'Group name must be at least 2 characters';
    header('Location: /family-groups');
    exit;
}

try {
    $pdo = getDB();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Create the group
    $groupId = generateUUID();
    $stmt = $pdo->prepare('INSERT INTO family_groups (id, name, description, created_by) VALUES (?, ?, ?, ?)');
    $stmt->execute([$groupId, $name, $description, $_SESSION['user_id']]);
    
    // Add creator as admin member
    $memberId = generateUUID();
    $stmt = $pdo->prepare('INSERT INTO family_group_members (id, group_id, user_id, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$memberId, $groupId, $_SESSION['user_id'], 'admin']);
    
    $pdo->commit();
    
    $_SESSION['success'] = 'Family group created successfully!';
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error creating family group: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to create family group. Please try again.';
}

header('Location: /family-groups');
exit;
