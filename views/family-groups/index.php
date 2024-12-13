<?php
requireLogin();
$pageTitle = 'Family Groups';
require_once PROJECT_ROOT . '/views/partials/header.php';

// Get user's groups
$pdo = getDB();
$stmt = $pdo->prepare('
    SELECT fg.*, fgm.role,
           (SELECT COUNT(*) FROM family_group_members WHERE group_id = fg.id) as member_count
    FROM family_groups fg
    JOIN family_group_members fgm ON fg.id = fgm.group_id
    WHERE fgm.user_id = ?
    ORDER BY fg.created_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$groups = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Family Groups</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
            Create New Group
        </button>
    </div>

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

    <?php if (empty($groups)): ?>
        <div class="alert alert-info">
            You haven't joined any family groups yet. Create one to get started!
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($groups as $group): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title"><?= sanitize($group['name']) ?></h5>
                                <?php if ($group['role'] === 'admin'): ?>
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
                            <p class="card-text"><?= sanitize($group['description'] ?? 'No description') ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><?= $group['member_count'] ?> members</small>
                                <a href="/family-groups/view?id=<?= $group['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    View Group
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Family Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/family-groups/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="groupName" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="groupName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="groupDescription" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="groupDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
