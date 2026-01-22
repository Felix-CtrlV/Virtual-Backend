<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../BackEnd/config/dbconfig.php';
require_once __DIR__ . '/../../../utils/Ordered.php';

$customer_id = $_SESSION['customer_id'] ?? 1;
$supplier_id = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;

// 1. LOGIC: Handle Successful Payment Return
if (isset($_GET['payment_status']) && $_GET['payment_status'] === 'success') {
    $is_ordered = placeOrder($conn, $customer_id, $supplier_id);

    if ($is_ordered) {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const Toast = Swal.mixin({
                  toast: true,
                  position: 'top-end',
                  showConfirmButton: false,
                  timer: 3000,
                  timerProgressBar: true
                });

                Toast.fire({
                  icon: 'success',
                  title: 'Ordered successfully!'
                }).then(() => {
                    window.location.href = '?supplier_id=$supplier_id&page=cart';
                });
            });
        </script>";
    }
}

// 2. LOGIC: Fetch Cart Data
$cart_query = "SELECT c.cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id, v.color, v.size, v.quantity AS available_stock 
               FROM cart c 
               JOIN product_variant v ON c.variant_id = v.variant_id 
               JOIN products p ON v.product_id = p.product_id 
               WHERE c.customer_id = ? AND c.supplier_id = ?";

$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "ii", $customer_id, $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cart_count = mysqli_num_rows($result);
$total_price = 0;
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* CSS maintained as per original */
    .qty-control-btn {
        background: transparent;
        border: 1px solid #ddd;
        color: #555;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.2s;
        cursor: pointer;
        font-size: 9px;
    }

    .qty-control-btn:hover:not(:disabled) {
        background-color: #f8f9fa;
        border-color: #bbb;
        color: #000;
    }

    .qty-number {
        font-weight: 600;
        font-size: 15px;
        min-width: 25px;
        text-align: center;
    }

    .custom-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .custom-modal-content {
        background: #f0f2f5;
        padding: 40px;
        border-radius: 30px;
        text-align: center;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .custom-modal-content h2 {
        color: #1a2a47;
        font-weight: 800;
        font-size: 28px;
        margin-bottom: 20px;
        margin-top: 0;
    }

    .custom-modal-content p {
        color: #444;
        font-size: 16px;
        margin-bottom: 30px;
    }

    .modal-btn-group {
        display: flex;
        justify-content: center;
    }

    .btn-cancel {
        background-color: #7d8590;
        color: white;
        border: none;
        margin-right: 20px;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-remove {
        background-color: #98B9D5;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    .order-summary-card {
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
    }

    .continue-shopping-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 10px;
        margin-top: 15px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        color: #333;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.2s;
    }

    .continue-shopping-btn:hover {
        background-color: #f8f9fa;
        color: #000;
    }

    .continue-shopping-btn i {
        margin-right: 8px;
    }
</style>

<div class="container mt-5 mb-5">
    <h2 class="mb-4">Your Shopping Cart</h2>

    <?php if (isset($_GET['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock Out',
                    text: '<?= htmlspecialchars($_GET['error']) ?>',
                    confirmButtonColor: '#98B9D5'
                });
            });
        </script>
    <?php endif; ?>

    <?php if ($cart_count > 0): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table table-hover align-middle" style="text-align: center;">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = mysqli_fetch_assoc($result)):
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total_price += $subtotal;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../uploads/products/<?= $item['product_id'] ?>_<?= $item['image'] ?>"
                                                    alt="<?= $item['product_name'] ?>"
                                                    style="width: 100px; height: 100px; object-fit: contain; margin-right: 15px;">
                                                <span style="text-align: left;">
                                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted d-flex align-items-center">
                                                        Color:
                                                        <span
                                                            style="display: inline-block; width: 20px; height: 20px; background-color: <?= $item['color'] ?>; border-radius: 50%; border: 1px solid #ddd; margin: 0 5px;"></span>
                                                        (Size: <?= htmlspecialchars($item['size']) ?>)
                                                    </small>
                                                </span>
                                            </div>
                                        </td>
                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center bg-transparent"
                                                style="gap: 5px;">
                                                <button type="button" class="qty-control-btn"
                                                    onclick="updateQuantity(<?= $item['cart_id'] ?>, parseInt(document.getElementById('qty-<?= $item['cart_id'] ?>').innerText) - 1, <?= $item['available_stock'] ?>)">
                                                    <i class="fas fa-minus"></i>
                                                </button>

                                                <span id="qty-<?= $item['cart_id'] ?>"
                                                    class="qty-number"><?= $item['quantity'] ?></span>

                                                <button type="button" class="qty-control-btn"
                                                    onclick="updateQuantity(<?= $item['cart_id'] ?>, parseInt(document.getElementById('qty-<?= $item['cart_id'] ?>').innerText) + 1, <?= $item['available_stock'] ?>)">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td id="subtotal-<?= $item['cart_id'] ?>">$<?= number_format($subtotal, 2) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger border-0"
                                                onclick="openRemoveModal(<?= $item['cart_id'] ?>)">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card order-summary-card">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Order Summary</h5>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Total Items:</span>
                            <strong><?= $cart_count ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Grand Total:</span>
                            <strong id="grand-total"
                                class="text-primary fs-4">$<?= number_format($total_price, 2) ?></strong>
                        </div>

                        <a href="../utils/accessCheckout.php?supplier_id=<?= $supplier_id ?>"
                            class="btn btn-primary w-100 py-3 mt-3 fw-bold"
                            style="background: linear-gradient(145deg, rgba(159, 204, 223, 0.8), rgba(71, 78, 111, 0.6)); border: none; border-radius: 10px; color: white; text-decoration: none; display: block; text-align: center;">
                            PROCEED TO CHECKOUT
                        </a>

                        <a href="?supplier_id=<?= $supplier_id ?>&page=collection" class="continue-shopping-btn">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x fs-1 text-muted"></i>
            <p class="mt-3">Your cart is empty.</p>
            <a href="?supplier_id=<?= $supplier_id ?>&page=collection" class="btn btn-primary"
                style="background: linear-gradient(145deg, rgba(159, 204, 223, 0.8), rgba(71, 78, 111, 0.6)); border: none;">
                Shop Now
            </a>
        </div>
    <?php endif; ?>
</div>

<div id="customDeleteModal" class="custom-modal-overlay">
    <div class="custom-modal-content">
        <h2>Remove Item?</h2>
        <p>This item will be removed from your cart.</p>
        <div class="modal-btn-group">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button id="confirmBtn" class="btn-remove">Remove</button>
        </div>
    </div>
</div>

<script>
    let pendingCartId = null;
    let updateTimer = null; // Timer to wait before showing the alert

    function closeModal() {
        document.getElementById('customDeleteModal').style.display = 'none';
        pendingCartId = null;
    }

    function openRemoveModal(cartId) {
        pendingCartId = cartId;
        document.getElementById('customDeleteModal').style.display = 'flex';
    }

    document.getElementById('confirmBtn').onclick = function () {
        if (pendingCartId) {
            removeFromCart(pendingCartId);
        }
    };
    function updateQuantity(cartId, newQty, availableStock) {
        if (newQty < 1) {
            openRemoveModal(cartId);
            return;
        }

        if (newQty > availableStock) {
            Swal.fire({
                icon: 'warning',
                title: 'Out of Stock',
                text: 'Only ' + availableStock + ' items available in stock.',
                confirmButtonColor: '#98B9D5'
            });
            return;
        }

        // 1. Update UI Immediately
        document.getElementById('qty-' + cartId).innerText = newQty;
        recalculateCart();

        // 2. Clear existing timer
        clearTimeout(updateTimer);

        // 3. Set a timer to update Database and show the Large Alert
        updateTimer = setTimeout(() => {
            const rootPath = window.location.origin + '/malltiverse/frontend/utils/update_cart_qty.php';
            fetch(rootPath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 'cart_id': cartId, 'quantity': newQty })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Reverted design to match First Image (Large centered modal)
                        Swal.fire({
                            title: 'Updated',
                            text: 'Quantity updated successfully',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500, // Closes automatically after 1.5s
                            borderRadius: '30px',
                            customClass: {
                                popup: 'custom-swal-popup'
                            }
                        });
                    } else {
                        alert('Error: ' + data.message);
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 500);
    }
    function removeFromCart(cartId) {
        const rootPath = window.location.origin + '/malltiverse/frontend/utils/removeFromCart.php';

        fetch(rootPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ 'cart_id': cartId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // 1. Close the modal
                    closeModal();

                    // 2. Show a success alert (matching your large centered design)
                    Swal.fire({
                        title: 'Removed',
                        text: 'Item has been removed from your cart.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500,
                        borderRadius: '30px'
                    }).then(() => {
                        // 3. Reload the page to refresh the cart list and totals
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing the item.');
            });
    }
    function recalculateCart() {
        let grandTotal = 0;
        document.querySelectorAll('table tbody tr').forEach(row => {
            const priceText = row.cells[1].innerText.replace('$', '').replace(',', '');
            const price = parseFloat(priceText);
            const qtyElement = row.querySelector('.qty-number');
            if (qtyElement) {
                const qty = parseInt(qtyElement.innerText);
                const subtotal = price * qty;
                row.cells[3].innerText = '$' + subtotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
                grandTotal += subtotal;
            }
        });
        document.getElementById('grand-total').innerText = '$' + grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
    }
</script>