// Function to handle wishlist deletion
function deleteWishlist(wishlistId) {
    if (confirm('Are you sure you want to delete this wishlist?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/wishlists/delete';
        
        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
        
        // Add wishlist ID
        const wishlistInput = document.createElement('input');
        wishlistInput.type = 'hidden';
        wishlistInput.name = 'wishlist_id';
        wishlistInput.value = wishlistId;
        form.appendChild(wishlistInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Show success messages for 3 seconds then fade out
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });
});
