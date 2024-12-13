<?php
requireLogin();
$pageTitle = 'View Family Group';
require_once PROJECT_ROOT . '/views/partials/header.php';

if (!isset($_GET['id'])) {
    header('Location: /family-groups');
    exit;
}

$groupId = $_GET['id'];

try {
    $pdo = getDB();
    
    // Get group details and check membership
    $stmt = $pdo->prepare('
        SELECT fg.*, fgm.role as user_role
        FROM family_groups fg
        JOIN family_group_members fgm ON fg.id = fgm.group_id
        WHERE fg.id = ? AND fgm.user_id = ?
    ');
    $stmt->execute([$groupId, $_SESSION['user_id']]);
    $group = $stmt->fetch();
    
    if (!$group) {
        $_SESSION['error'] = 'Group not found or you do not have access';
        header('Location: /family-groups');
        exit;
    }
    
    // Get group members
    $stmt = $pdo->prepare('
        SELECT u.id, u.name, u.email, fgm.role, fgm.joined_at
        FROM family_group_members fgm
        JOIN users u ON fgm.user_id = u.id
        WHERE fgm.group_id = ?
        ORDER BY fgm.role DESC, u.name ASC
    ');
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll();
    
    // Get group wishlists
    $stmt = $pdo->prepare('
        SELECT w.*, u.name as user_name
        FROM wishlists w
        JOIN users u ON w.user_id = u.id
        WHERE w.group_id = ?
        ORDER BY w.created_at DESC
    ');
    $stmt->execute([$groupId]);
    $wishlists = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error viewing family group: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to load group details. Please try again.';
    header('Location: /family-groups');
    exit;
}

// Get pending invitations if user is admin
$pendingInvites = [];
if ($group['user_role'] === 'admin') {
    try {
        $stmt = $pdo->prepare('
            SELECT email, created_at, status
            FROM group_invitations
            WHERE group_id = ? AND status = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$groupId, 'pending']);
        $pendingInvites = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching pending invites: " . $e->getMessage());
    }
}
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/family-groups">Family Groups</a></li>
            <li class="breadcrumb-item active"><?= sanitize($group['name']) ?></li>
        </ol>
    </nav>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= sanitize($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= sanitize($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="card-title h3"><?= sanitize($group['name']) ?></h1>
                            <p class="text-muted mb-3"><?= sanitize($group['description'] ?? 'No description') ?></p>
                        </div>
                        <?php if ($group['user_role'] === 'admin'): ?>
                            <div class="dropdown">
                                <button class="btn btn-link" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="/family-groups/edit?id=<?= $group['id'] ?>">
                                            Edit Group
                                        </a>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-danger" 
                                                onclick="confirmDeleteGroup('<?= $group['id'] ?>', '<?= sanitize($group['name']) ?>')">
                                            Delete Group
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Group Wishlists</h2>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createWishlistModal">
                        Add Wishlist
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($wishlists)): ?>
                        <p class="text-muted">No wishlists have been added to this group yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($wishlists as $wishlist): ?>
                                <a href="/wishlists/view?id=<?= $wishlist['id'] ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= sanitize($wishlist['title']) ?></h6>
                                        <small class="text-muted">by <?= sanitize($wishlist['user_name']) ?></small>
                                    </div>
                                    <?php if ($wishlist['description']): ?>
                                        <p class="mb-1 small text-muted"><?= sanitize($wishlist['description']) ?></p>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Members (<?= count($members) ?>)</h2>
                    <?php if ($group['user_role'] === 'admin'): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteModal">
                            Invite Members
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($members as $member): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?= sanitize($member['name']) ?></h6>
                                        <small class="text-muted"><?= sanitize($member['email']) ?></small>
                                    </div>
                                    <span class="badge bg-<?= $member['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                        <?= ucfirst($member['role']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if ($group['user_role'] === 'admin' && !empty($pendingInvites)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Pending Invitations</h2>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($pendingInvites as $invite): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?= sanitize($invite['email']) ?></h6>
                                        <small class="text-muted">Invited <?= date('M j, Y', strtotime($invite['created_at'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Wishlist Modal -->
<div class="modal fade" id="createWishlistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Wishlist to Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/wishlists/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="wishlistTitle" class="form-label">Wishlist Title</label>
                        <input type="text" class="form-control" id="wishlistTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="wishlistDescription" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="wishlistDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Wishlist</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Invite Members Modal -->
<?php if ($group['user_role'] === 'admin'): ?>
<div class="modal fade" id="inviteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invite Members</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/family-groups/invite" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="inviteEmail" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="inviteEmail" name="email" required>
                        <div class="form-text">
                            Enter the email address of the person you want to invite to this group.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Group Form (Hidden) -->
<form id="deleteGroupForm" action="/family-groups/delete" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="group_id" id="deleteGroupId">
</form>

<script>
function confirmDeleteGroup(groupId, groupName) {
    if (confirm(`Are you sure you want to delete the group "${groupName}"? This action cannot be undone.`)) {
        document.getElementById('deleteGroupId').value = groupId;
        document.getElementById('deleteGroupForm').submit();
    }
}
</script>

<?php require_once PROJECT_ROOT . '/views/partials/footer.php'; ?>
