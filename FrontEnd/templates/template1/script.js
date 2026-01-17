// Global Toast Notification Helper
window.showNotification = function (message, type = 'success') {
    // Check if a container already exists, if not create one
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'primary' : 'danger'} shadow-lg`;
    toast.style.cssText = 'min-width: 250px; border-radius: 10px; border: none; margin-bottom: 10px; animation: slideIn 0.3s ease-out;';
    toast.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}"></i>
            <div>${message}</div>
        </div>
    `;

    container.appendChild(toast);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = '0.5s';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
};

// Add this CSS animation to your style.css
// @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
function refreshCartDrawer(supplierId) {
    const container = document.getElementById('cartItemsContainer');
    const footer = document.getElementById('cartFooter');
    const badge = document.querySelector('.cart-badge');

    if (!container || !supplierId) return;

    // We use get_cart_data.php because it returns the data we need to build the UI
    fetch(`../utils/get_cart_data.php?supplier_id=${supplierId}`)
        .then(res => res.json())
        .then(data => {
            let html = '';

            if (data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    html += `
                    <div class="cart-item-block mb-4">
                        <div class="d-flex gap-3">
                            <img src="${item.image}" alt="${item.name}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">${item.name}</h6>
                                <div class="text-muted small">Qty: ${item.qty} ${item.size ? '| Size: ' + item.size : ''}</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="small text-muted">Color:</span>
                                    <span class="color-preview" style="background-color: ${item.color_code || '#ccc'}; border: 1px solid #ddd; width: 12px; height: 12px; border-radius: 50%; display: inline-block;"></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="fw-bold">$${parseFloat(item.price * item.qty).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
                                    <button onclick="removeItem(${item.cart_id}, ${supplierId})" class="btn btn-sm text-danger p-0 border-0 bg-transparent">
                                        <i class="bi bi-trash"></i> <small>Remove</small>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });

                container.innerHTML = html;
                // Inside refreshCartDrawer function, find where footer.innerHTML is set:
                footer.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="fs-5">Total:</span>
        <span class="fs-5 fw-bold">$${data.total}</span>
    </div>
    <button class="addtobag_btn w-100" onclick="window.location.href='../utils/accessCheckout.php?supplier_id=${supplierId}'">
    Checkout
    </button>`;

                if (badge) badge.innerText = data.itemCount;
            } else {
                container.innerHTML = '<div class="text-center mt-5 text-muted">Your bag is empty.</div>';
                footer.innerHTML = '';
                if (badge) badge.innerText = '';
            }
        })
        .catch(err => console.error('Error fetching cart:', err));
}
// Variable to store the IDs temporarily while the modal is open
let itemToDelete = null;
let supplierToDelete = null;

window.removeItem = function (cartId, supplierId) {
    itemToDelete = cartId;
    supplierToDelete = supplierId;

    const modalBtn = document.getElementById('confirmDeleteBtn');
    if (modalBtn) {
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        deleteModal.show();
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const supplierId = urlParams.get('supplier_id');

    // --- MOVE THE MODAL LISTENER INSIDE HERE ---
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!itemToDelete) return;

            const formData = new FormData();
            formData.append('cart_id', itemToDelete);

            fetch('../utils/removeFromCart.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const modalElement = document.getElementById('deleteConfirmModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        if (modalInstance) modalInstance.hide();

                        if (window.location.search.includes('page=cart')) {
                            location.reload();
                        } else {
                            refreshCartDrawer(supplierToDelete);
                        }
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(err => console.error('Error:', err));
        });
    }
    // --- YOUR EXISTING DRAWER CODE ---
    const trigger = document.getElementById('cartIconTrigger');
    const closeBtn = document.getElementById('closeCart');
    const drawer = document.getElementById('cartDrawer');
    const overlay = document.getElementById('cartOverlay');

    if (trigger) {
        trigger.addEventListener('click', () => {
            drawer.classList.add('open');
            overlay.classList.add('active');
            refreshCartDrawer(supplierId);
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            drawer.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
    // --- NEW: This handles clicking outside the bag ---
    if (overlay) {
        overlay.addEventListener('click', function () {
            console.log("Overlay clicked - closing drawer"); // This helps you debug
            drawer.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    if (supplierId) refreshCartDrawer(supplierId);
});