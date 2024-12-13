<?php
$pageTitle = 'Welcome to Family Christmas List';
require_once PROJECT_ROOT . '/views/layouts/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8 text-center">
            <h1>Welcome to Family Christmas List</h1>
            <p class="lead">Create and share your Christmas wishlists with family members!</p>
            
            <?php if (!isLoggedIn()): ?>
                <div class="mt-4">
                    <a href="/login" class="btn btn-primary btn-lg me-3">Login</a>
                    <a href="/register" class="btn btn-outline-primary btn-lg">Register</a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="/wishlists" class="btn btn-primary btn-lg">View My Wishlists</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once PROJECT_ROOT . '/views/layouts/footer.php'; ?>
