<?php
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid request';
        header('Location: /family-groups');
        exit;
    }

    // Validate required fields
    if (!isset($_POST['group_id']) || !isset($_POST['email'])) {
        $_SESSION['error'] = 'Missing required fields';
        header('Location: /family-groups');
        exit;
    }

    $groupId = $_POST['group_id'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email address';
        header("Location: /family-groups/view?id=$groupId");
        exit;
    }

    try {
        $pdo = getDB();
        
        // Check if user is admin of the group
        $stmt = $pdo->prepare('
            SELECT role 
            FROM family_group_members 
            WHERE group_id = ? AND user_id = ? AND role = ?
        ');
        $stmt->execute([$groupId, $_SESSION['user_id'], 'admin']);
        
        if (!$stmt->fetch()) {
            $_SESSION['error'] = 'You do not have permission to invite members to this group';
            header("Location: /family-groups/view?id=$groupId");
            exit;
        }
        
        // Check if user is already a member
        $stmt = $pdo->prepare('
            SELECT u.id 
            FROM users u
            JOIN family_group_members fgm ON u.id = fgm.user_id
            WHERE fgm.group_id = ? AND u.email = ?
        ');
        $stmt->execute([$groupId, $email]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'This user is already a member of the group';
            header("Location: /family-groups/view?id=$groupId");
            exit;
        }
        
        // Check if there's already a pending invitation
        $stmt = $pdo->prepare('
            SELECT id 
            FROM group_invitations 
            WHERE group_id = ? AND email = ? AND status = ?
        ');
        $stmt->execute([$groupId, $email, 'pending']);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'An invitation has already been sent to this email';
            header("Location: /family-groups/view?id=$groupId");
            exit;
        }
        
        // Get group details for the email
        $stmt = $pdo->prepare('SELECT name FROM family_groups WHERE id = ?');
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        
        // Generate invitation token and ID
        $token = bin2hex(random_bytes(32));
        $invitationId = generateUUID();
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Create invitation record
            $stmt = $pdo->prepare('
                INSERT INTO group_invitations (id, group_id, email, token, invited_by, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ');
            $stmt->execute([$invitationId, $groupId, $email, $token, $_SESSION['user_id'], 'pending']);
            
            // Prepare email
            $inviteLink = "http://{$_SERVER['HTTP_HOST']}/family-groups/join?token=" . urlencode($token);
            $subject = "Invitation to join " . $group['name'] . " on Family Christmas List";
            $message = "Hello!\n\n";
            $message .= "You have been invited to join the family group '{$group['name']}' on Family Christmas List.\n\n";
            $message .= "To accept this invitation, please click on the following link:\n";
            $message .= $inviteLink . "\n\n";
            $message .= "This invitation link will expire in 7 days.\n\n";
            $message .= "If you did not expect this invitation, you can safely ignore this email.\n\n";
            $message .= "Best regards,\nFamily Christmas List Team";
            
            $headers = "From: noreply@familychristmaslist.com\r\n";
            $headers .= "Reply-To: noreply@familychristmaslist.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Try to send email, but continue even if it fails
            $mailSent = @mail($email, $subject, $message, $headers);
            
            // Commit the transaction
            $pdo->commit();
            
            if ($mailSent) {
                $_SESSION['success'] = 'Invitation sent successfully.';
            } else {
                $_SESSION['success'] = 'Invitation created successfully. However, there was an issue sending the email. The person can still join using this link: ' . $inviteLink;
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Error sending group invitation: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to create invitation. Please try again.';
    }

    header("Location: /family-groups/view?id=$groupId");
    exit;
} else {
    // Redirect GET requests back to family groups
    header('Location: /family-groups');
    exit;
}
