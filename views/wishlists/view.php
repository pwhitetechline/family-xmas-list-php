<?php
require_once PROJECT_ROOT . '/views/partials/header.php';

// Get the wishlist ID from the URL
$wishlist_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$wishlist_id) {
    $_SESSION['error'] = 'No wishlist ID provided';
    header('Location: /wishlists');
    exit;
}

try {
    $pdo = getDB();
    
    // Get wishlist details
    $stmt = $pdo->prepare('SELECT w.*, u.name as creator_name 
                          FROM wishlists w 
                          JOIN users u ON w.user_id = u.id 
                          WHERE w.id = ?');
    $stmt->execute([$wishlist_id]);
    $wishlist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$wishlist) {
        $_SESSION['error'] = 'Wishlist not found';
        header('Location: /wishlists');
        exit;
    }
    
    // Get wishlist items
    $stmt = $pdo->prepare('SELECT * FROM wishlist_items WHERE wishlist_id = ? ORDER BY created_at DESC');
    $stmt->execute([$wishlist_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header('Location: /wishlists');
    exit;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo htmlspecialchars($wishlist['title']); ?></h1>
        <a href="/wishlists" class="btn btn-secondary">Back to Wishlists</a>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Wishlist Details</h5>
            <p class="card-text">Created by: <?php echo htmlspecialchars($wishlist['creator_name']); ?></p>
            <?php if (isset($wishlist['description']) && $wishlist['description']): ?>
                <p class="card-text"><?php echo htmlspecialchars($wishlist['description']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($wishlist['user_id'] === $_SESSION['user_id']): ?>
    <div class="mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            Add Item
        </button>
    </div>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($items)): ?>
            <div class="col">
                <div class="alert alert-info">
                    No items in this wishlist yet.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <?php if ($item['description']): ?>
                                <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($item['price']): ?>
                                <p class="card-text">Price: $<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></p>
                            <?php endif; ?>
                            <?php if ($item['url']): ?>
                                <a href="<?php echo htmlspecialchars($item['url']); ?>" class="btn btn-primary" target="_blank">View Item</a>
                            <?php endif; ?>
                            
                            <?php if ($wishlist['user_id'] === $_SESSION['user_id']): ?>
                                <div class="mt-2">
                                    <a href="/wishlists/edit-item?id=<?php echo $item['id']; ?>&wishlist_id=<?php echo $wishlist_id; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <form action="/wishlists/delete-item" method="POST" class="d-inline">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="wishlist_id" value="<?php echo $wishlist_id; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Item Modal -->
<?php if ($wishlist['user_id'] === $_SESSION['user_id']): ?>
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="/wishlists/add-item" method="POST">
                    <input type="hidden" name="wishlist_id" value="<?php echo $wishlist_id; ?>">
                    
                    <div class="mb-3">
                        <label for="itemName" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="itemName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="itemDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="itemDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="itemPrice" class="form-label">Price</label>
                        <input type="number" class="form-control" id="itemPrice" name="price" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label for="itemUrl" class="form-label">URL</label>
                        <input type="url" class="form-control" id="itemUrl" name="url">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
require_once PROJECT_ROOT . '/views/partials/footer.php';
?>
