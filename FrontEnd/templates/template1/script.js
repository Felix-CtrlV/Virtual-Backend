// This function is now the ONLY source for building the Bag UI
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
                footer.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fs-5">Total:</span>
                        <span class="fs-5 fw-bold">$${data.total}</span>
                    </div>
                    <button class="btn w-100 py-3 fw-bold rounded-3 shadow-sm" style="background-color: #f0f0f0; color: #333; border: none;" onclick="window.location.href='?supplier_id=${supplierId}&page=checkout'">
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

// MAKE REMOVE ITEM GLOBAL SO ONCLICK CAN FIND IT
// Keep this at the very top of script.js (outside any other functions)
window.removeItem = function (cartId, supplierId) {
    if (!confirm('Remove this item?')) return;

    const formData = new FormData();
    formData.append('cart_id', cartId);

    fetch('../utils/removeFromCart.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                refreshCartDrawer(supplierId); // Refresh the UI
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => console.error('Error:', err));
};

document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const supplierId = urlParams.get('supplier_id');

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

    if (supplierId) refreshCartDrawer(supplierId);
});