<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'] ?? null;
    $wishlist_id = $_POST['wishlist_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ? floatval($_POST['price']) : null;
    $url = $_POST['url'] ?? '';

    if (!$item_id || !$wishlist_id || !$name) {
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
        
        // Update the item
        $stmt = $pdo->prepare('
            UPDATE wishlist_items 
            SET name = ?, description = ?, price = ?, url = ? 
            WHERE id = ?
        ');
        $stmt->execute([$name, $description, $price, $url, $item_id]);
        
        $_SESSION['success'] = 'Item updated successfully';
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }

    header('Location: /wishlists/view?id=' . $wishlist_id);
    exit;
}

// If not POST, show the edit form
$item_id = $_GET['id'] ?? null;
$wishlist_id = $_GET['wishlist_id'] ?? null;

if (!$item_id || !$wishlist_id) {
    $_SESSION['error'] = 'Missing required parameters';
    header('Location: /wishlists');
    exit;
}

try {
    $pdo = getDB();
    
    // Get item details
    $stmt = $pdo->prepare('
        SELECT wi.* 
        FROM wishlist_items wi 
        JOIN wishlists w ON wi.wishlist_id = w.id 
        WHERE wi.id = ? AND w.user_id = ?
    ');
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        $_SESSION['error'] = 'Item not found';
        header('Location: /wishlists/view?id=' . $wishlist_id);
        exit;
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header('Location: /wishlists/view?id=' . $wishlist_id);
    exit;
}

require_once PROJECT_ROOT . '/views/partials/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2>Edit Item</h2>
                </div>
                <div class="card-body">
                    <form action="/wishlists/edit-item" method="POST">
                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                        <input type="hidden" name="wishlist_id" value="<?php echo htmlspecialchars($wishlist_id); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($item['price'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="url" class="form-label">URL</label>
                            <input type="url" class="form-control" id="url" name="url" value="<?php echo htmlspecialchars($item['url'] ?? ''); ?>">
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/wishlists/view?id=<?php echo htmlspecialchars($wishlist_id); ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once PROJECT_ROOT . '/views/partials/footer.php';
?>
