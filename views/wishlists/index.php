<?php
requireLogin();
$pageTitle = 'Wishlists';
require_once PROJECT_ROOT . '/views/layouts/header.php';

$pdo = getDB();

// Get user's personal wishlists
$stmt = $pdo->prepare('
    SELECT w.*, NULL as group_name, NULL as group_id 
    FROM wishlists w 
    WHERE w.user_id = ? AND w.group_id IS NULL 
    ORDER BY w.created_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$personalWishlists = $stmt->fetchAll();

// Get wishlists from groups where user is a member
$stmt = $pdo->prepare('
    SELECT w.*, fg.name as group_name, fg.id as group_id, u.name as creator_name
    FROM wishlists w
    JOIN family_groups fg ON w.group_id = fg.id
    JOIN users u ON w.user_id = u.id
    JOIN family_group_members fgm ON fg.id = fgm.group_id
    WHERE fgm.user_id = ?
    ORDER BY fg.name ASC, w.created_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$groupWishlists = [];
while ($row = $stmt->fetch()) {
    $groupWishlists[$row['group_id']][] = $row;
}

// Get user's groups for the create wishlist modal
$stmt = $pdo->prepare('
    SELECT fg.id, fg.name
    FROM family_groups fg
    JOIN family_group_members fgm ON fg.id = fgm.group_id
    WHERE fgm.user_id = ?
    ORDER BY fg.name ASC
');
$stmt->execute([$_SESSION['user_id']]);
$userGroups = $stmt->fetchAll();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Wishlists</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWishlistModal">
            Create New Wishlist
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

    <!-- Personal Wishlists -->
    <section class="mb-5">
        <h2 class="h4 mb-3">My Personal Wishlists</h2>
        <?php if (empty($personalWishlists)): ?>
            <div class="alert alert-info mb-4">
                You haven't created any personal wishlists yet.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($personalWishlists as $wishlist): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= sanitize($wishlist['title']) ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">Created: <?= date('M j, Y', strtotime($wishlist['created_at'])) ?></small>
                                </p>
                                <div class="d-flex gap-2">
                                    <a href="/wishlists/view?id=<?= $wishlist['id'] ?>" class="btn btn-primary">View Items</a>
                                    <button class="btn btn-danger" onclick="deleteWishlist('<?= $wishlist['id'] ?>')">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Group Wishlists -->
    <section>
        <h2 class="h4 mb-3">Family Group Wishlists</h2>
        <?php if (empty($groupWishlists)): ?>
            <div class="alert alert-info">
                You don't have access to any family group wishlists yet. Join a family group to see wishlists from other members!
            </div>
        <?php else: ?>
            <?php foreach ($groupWishlists as $groupId => $wishlists): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="h5 mb-0"><?= sanitize($wishlists[0]['group_name']) ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($wishlists as $wishlist): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= sanitize($wishlist['title']) ?></h5>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    By: <?= sanitize($wishlist['creator_name']) ?><br>
                                                    Created: <?= date('M j, Y', strtotime($wishlist['created_at'])) ?>
                                                </small>
                                            </p>
                                            <div class="mt-auto">
                                                <div class="d-flex gap-2">
                                                    <a href="/wishlists/view?id=<?= $wishlist['id'] ?>" class="btn btn-primary">View Items</a>
                                                    <?php if ($wishlist['user_id'] === $_SESSION['user_id']): ?>
                                                        <button class="btn btn-danger" onclick="deleteWishlist('<?= $wishlist['id'] ?>')">Delete</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Create Wishlist Modal -->
    <div class="modal fade" id="createWishlistModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Wishlist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="/wishlists/create" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="title" class="form-label">Wishlist Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="group_id" class="form-label">Add to Family Group (Optional)</label>
                            <select class="form-select" id="group_id" name="group_id">
                                <option value="">Personal Wishlist</option>
                                <?php foreach ($userGroups as $group): ?>
                                    <option value="<?= $group['id'] ?>"><?= sanitize($group['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Select a family group to share this wishlist with its members.
                            </div>
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
</div>

<script>
function deleteWishlist(id) {
    if (confirm('Are you sure you want to delete this wishlist?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/wishlists/delete';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = '<?= $_SESSION['csrf_token'] ?>';
        form.appendChild(csrf);
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'wishlist_id';
        input.value = id;
        form.appendChild(input);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once PROJECT_ROOT . '/views/layouts/footer.php'; ?>
