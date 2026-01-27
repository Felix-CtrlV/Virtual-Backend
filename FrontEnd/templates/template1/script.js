// ==========================================
// 1. GLOBAL HELPERS & AUTH FUNCTIONS
// ==========================================

// Global Toast Notification Helper
window.showNotification = function (message, type = 'success') {
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

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = '0.5s';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
};
// Global Login Popup Trigger
window.showLoginPopup = function () {
    const modal = document.getElementById('auth-modal-overlay');
    if (modal) {
        modal.style.display = 'flex';
    } else {
        // This fixes the 404 error by navigating to the parent directory
        alert("Please login to proceed.");
        window.location.href = "../customerLogin.php";
    }
};

// Global Auth Check
window.checkLoginStatus = function () {
    return typeof USER_LOGGED_IN !== 'undefined' && USER_LOGGED_IN === true;
};

// ==========================================
// 2. CART LOGIC
// ==========================================

let debounceTimer;

window.changeQty = function (cartId, currentQty, delta, supplierId, availableStock) {
    let newQty = parseInt(currentQty) + delta;
    if (newQty < 1) return;

    let maxStock = availableStock;
    if (!maxStock || maxStock === 999) {
        const stockDisplay = document.getElementById('stock-display');
        if (stockDisplay && stockDisplay.textContent.includes('In Stock:')) {
            maxStock = parseInt(stockDisplay.textContent.replace('In Stock: ', ''));
        }
    }

    if (delta > 0 && newQty > maxStock) {
        window.showNotification(`Only ${maxStock} items available in stock.`, "danger");
        newQty = maxStock;
        if (parseInt(currentQty) === maxStock) return;
    }

    const btn = event.currentTarget;
    const container = btn.closest('.qty-selector-container');
    const display = container.querySelector('.qty-display');

    if (display) {
        display.innerText = newQty;
        const buttons = container.querySelectorAll('.qty-button');
        buttons[0].setAttribute('onclick', `changeQty(${cartId}, ${newQty}, -1, ${supplierId}, ${availableStock})`);
        buttons[1].setAttribute('onclick', `changeQty(${cartId}, ${newQty}, 1, ${supplierId}, ${availableStock})`);
    }

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const formData = new FormData();
        formData.append('cart_id', cartId);
        formData.append('quantity', newQty);

        fetch('../utils/update_cart_qty.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    refreshCartDrawer(supplierId);
                } else {
                    window.showNotification(data.message, "danger");
                    refreshCartDrawer(supplierId);
                }
            })
            .catch(err => console.error('Error updating quantity:', err));
    }, 300);
};

function refreshCartDrawer(supplierId) {
    const container = document.getElementById('cartItemsContainer');
    const footer = document.getElementById('cartFooter');
    const badge = document.querySelector('.cart-badge');

    if (!container || !supplierId) return;

    fetch(`../utils/get_cart_data.php?supplier_id=${supplierId}`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            if (data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    const availableStock = item.availableStock !== undefined ? item.availableStock : 999;
                    html += `
                    <div class="cart-item-block mb-4">
                        <div class="d-flex gap-3">
                            <img src="${item.image}" alt="${item.name}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">${item.name}</h6>
                                <div class="text-muted small d-flex align-items-center gap-2">
                                    <span>Color:</span>
                                    <span class="color-preview" style="background-color: ${item.color_code || '#ccc'}; border: 1px solid #ddd; width: 12px; height: 12px; border-radius: 50%; display: inline-block;"></span>
                                    <span>${item.size ? ' | Size: ' + item.size : ''}</span>
                                    <span> | Qty: ${item.qty}</span>
                                </div>
                                <div class="qty-selector-container d-flex align-items-center gap-3 mt-2">
                                    <button class="qty-button" onclick="changeQty(${item.cart_id}, ${item.qty}, -1, ${supplierId}, ${availableStock})">−</button>
                                    <span class="qty-display">${item.qty}</span>
                                    <button class="qty-button" onclick="changeQty(${item.cart_id}, ${item.qty}, 1, ${supplierId}, ${availableStock})">+</button>
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
                footer.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fs-5">Total:</span>
                        <span class="fs-5 fw-bold">$${data.total}</span>
                    </div>
                    <button class="addtobag_btn w-100" onclick="window.checkLoginStatus() ? window.location.href='../utils/accessCheckout.php?supplier_id=${supplierId}' : window.showLoginPopup()">
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

// ==========================================
// 3. EVENT LISTENERS
// ==========================================

let itemToDelete = null;
let supplierToDelete = null;

window.removeItem = function (cartId, supplierId) {
    itemToDelete = cartId;
    supplierToDelete = supplierId;
    const modalElement = document.getElementById('deleteConfirmModal');
    if (modalElement) {
        const deleteModal = new bootstrap.Modal(modalElement);
        deleteModal.show();
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const supplierId = urlParams.get('supplier_id');

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

    const trigger = document.getElementById('cartIconTrigger');
    const closeBtn = document.getElementById('closeCart');
    const drawer = document.getElementById('cartDrawer');
    const overlay = document.getElementById('cartOverlay');

    if (trigger) {
        trigger.addEventListener('click', () => {
            // Only open drawer if logged in (The nav.php onclick handles the guest side)
            if (window.checkLoginStatus()) {
                drawer.classList.add('open');
                overlay.classList.add('active');
                refreshCartDrawer(supplierId);
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            drawer.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            drawer.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    if (supplierId && window.checkLoginStatus()) refreshCartDrawer(supplierId);

    const errorMsg = urlParams.get('error');
    if (errorMsg) {
        window.showNotification(errorMsg, "danger");
        const currentSupplierId = urlParams.get('supplier_id');
        const newUrl = window.location.pathname + (currentSupplierId ? '?supplier_id=' + currentSupplierId : '');
        window.history.replaceState({}, document.title, newUrl);
    }
});