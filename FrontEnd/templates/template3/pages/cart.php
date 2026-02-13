<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


include '../../BackEnd/config/dbconfig.php';
require_once __DIR__ . '/../../../utils/Ordered.php';

$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 0;
$supplier_id = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;
$company_id = isset($supplier['company_id']) ? (int)$supplier['company_id'] : 0;

if ($company_id <= 0 && $supplier_id > 0) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_id FROM companies WHERE supplier_id = $supplier_id LIMIT 1"));
    $company_id = $r ? (int)$r['company_id'] : 0;
}
$current_url = urlencode($_SERVER['REQUEST_URI']);

// 1. Updated Order Success Logic with Glass UI
if ($customer_id > 0 && isset($_GET['payment_status']) && $_GET['payment_status'] === 'success') {
    $is_ordered = placeOrder($conn, $customer_id, $company_id);

    if ($is_ordered) {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Ordered Successfully!',
                    text: 'Your order has been placed and is being processed.',
                    icon: 'success',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'glass-popup',
                        title: 'glass-title',
                        htmlContainer: 'glass-content'
                    }
                }).then(() => {
                    window.location.href = '?supplier_id=$supplier_id&page=cart';
                });
            });
        </script>";
    }
}

// 2. Fetch Cart Data
$cart_count = 0;
$total_price = 0;
$result = null;

if ($customer_id > 0 && $company_id > 0) {
    $cart_query = "SELECT c.cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id, v.color, v.size, v.quantity AS available_stock 
                   FROM cart c 
                   JOIN product_variant v ON c.variant_id = v.variant_id 
                   JOIN products p ON v.product_id = p.product_id 
                   WHERE c.customer_id = ? AND c.company_id = ?";

    $stmt = mysqli_prepare($conn, $cart_query);
    mysqli_stmt_bind_param($stmt, "ii", $customer_id, $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cart_count = mysqli_num_rows($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>

        .qty-control-btn { background: transparent; border: 1px solid #ddd; color: #555; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: all 0.2s; cursor: pointer; font-size: 9px; }
        .qty-control-btn:hover:not(:disabled) { background-color: #f8f9fa; border-color: #bbb; color: #000; }
        .qty-number { font-weight: 600; font-size: 15px; min-width: 25px; text-align: center; }

        .custom-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .custom-modal-content { background: #f0f2f5; padding: 40px; border-radius: 30px; text-align: center; width: 90%; max-width: 450px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .custom-modal-content h2 { color: #1a2a47; font-weight: 800; font-size: 28px; margin-bottom: 20px; margin-top: 0; }
        .btn-cancel { background-color: #7d8590; color: white; border: none; margin-right: 20px; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-remove { background-color: #98B9D5; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; }        
        .order-summary-card { border: 1px solid #dee2e6; border-radius: 0.25rem; box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075); }
        .continue-shopping-btn { display: inline-flex; align-items: center; justify-content: center; width: 100%; padding: 10px; margin-top: 15px; border: 1px solid #dee2e6; border-radius: 8px; color: #333; text-decoration: none; font-weight: 500; transition: background-color 0.2s; }
        .continue-shopping-btn i { margin-right: 8px; }
        .login-prompt-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4); 
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10001;
            backdrop-filter: blur(15px);
        }
        .login-prompt-card {
            background: rgba(255, 255, 255, 0.1); 
            width: 100%;
            max-width: 400px;
            padding: 45px 35px;
            border-radius: 28px;
            text-align: center;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        .login-prompt-card h2 { 
            color: #ffffff; font-size: 30px; margin-bottom: 12px; font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .login-prompt-card p { 
            color: #ffffff; opacity: 0.9; margin-bottom: 35px; font-size: 16px;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
        }
        .modal-action-btn {
            display: block; width: 100%; padding: 14px; margin-bottom: 15px;
            border-radius: 50px; font-size: 15px; font-weight: 600;
            text-decoration: none; transition: all 0.3s ease; border: none; cursor: pointer;
            text-align: center;
        }
        .btn-login-alt { 
            background: rgba(255, 255, 255, 0.15); color: #ffffff; 
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        .btn-login-alt:hover { 
            background: #ffffff; color: #000000;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.4);
        }
        .btn-create-alt { 
            background: transparent; color: #ffffff; 
            border: 1px solid rgba(255, 255, 255, 0.3); 
        }
        .btn-create-alt:hover { border-color: #ffffff; background: rgba(255, 255, 255, 0.1); }
        .divider-container { 
            color: #ffffff; font-weight: 500; opacity: 0.7; display: flex; align-items: center; margin: 25px 0; 
        }
        .divider-container::before, .divider-container::after { content: ''; flex: 1; border-bottom: 1px solid rgba(255, 255, 255, 0.4); }
        .divider-container:not(:empty)::before { margin-right: 15px; }
        .divider-container:not(:empty)::after { margin-left: 15px; }
        
        .glass-popup {
            background: rgba(255, 255, 255, 0.12) !important;
            backdrop-filter: blur(25px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 28px !important;
            padding: 2em !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4) !important;
            width: 400px !important;
            aspect-ratio: 1 / 1 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }
        .glass-title { color: #ffffff !important; font-weight: 600 !important; font-family: 'Inter', sans-serif !important; }
        .glass-content { color: rgba(255, 255, 255, 0.9) !important; font-family: 'Inter', sans-serif !important; }
        .glass-confirm-btn {
            background: linear-gradient(135deg, #82ccdd, #60a3bc) !important;
            color: #fff !important;
            border-radius: 14px !important;
            padding: 12px 40px !important;
            font-weight: 600 !important;
            border: none !important;
            outline: none !important;
            box-shadow: 0 10px 20px -10px rgba(96, 163, 188, 0.5) !important;
            transition: all 0.3s ease !important;
            cursor: pointer;
        }
        .glass-confirm-btn:hover { transform: translateY(-2px) !important; }
        .swal2-icon.swal2-success {
            border-color: #000000 !important;
        }

        .swal2-icon.swal2-success [class^='swal2-success-line'] {
            background-color: #000000 !important;
        }

        .swal2-icon.swal2-success .swal2-success-ring {
            border: 4px solid rgba(0, 0, 0, 0.2) !important;
        }
    </style>
</head>
<body>

<div class="login-prompt-overlay" id="loginPromptModal">
    <div class="login-prompt-card">
        <h2>Log back in</h2>
        <p>Choose an account to continue.</p>

        <div class="divider-container">OR</div>

        <div class="modal-buttons">
            <a href="../customerLogin.php?return_url=<?= $current_url ?>" class="modal-action-btn btn-login-alt">Log in to another account</a>
            <a href="../customerRegister.php" class="modal-action-btn btn-create-alt">Create account</a>
            <p onclick="window.location.href='../shop/?supplier_id=<?= $supplier_id ?>&page=home'" style="cursor:pointer; margin-top:20px; font-size: 0.9rem; opacity: 0.5; text-decoration: none;">Cancel</p>
        </div>
    </div>
</div>

<div class="container mt-5 mb-5">
    <h2 class="mb-4">Your Shopping Cart</h2>

    <?php if (isset($_GET['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock Out',
                    text: '<?= htmlspecialchars($_GET['error']) ?>',
                    customClass: {
                        popup: 'glass-popup',
                        title: 'glass-title',
                        htmlContainer: 'glass-content',
                        confirmButton: 'glass-confirm-btn'
                    },
                    buttonsStyling: false
                });
            });
        </script>
    <?php endif; ?>

    <?php if ($cart_count > 0): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table table-hover align-middle" style="text-align: center; font-size: 18px;">
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
                                <?php mysqli_data_seek($result, 0); while ($item = mysqli_fetch_assoc($result)): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total_price += $subtotal;
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../uploads/products/<?= $item['product_id'] ?>_<?= $item['image'] ?>" style="width: 100px; height: 100px; object-fit: contain; margin-right: 15px;">
                                            <span style="text-align: left;">
                                                <strong><?= htmlspecialchars($item['product_name']) ?></strong><br>
                                                <small class="text-muted">Color: <span style="display: inline-block; width: 12px; height: 12px; background:<?= $item['color'] ?>; border-radius:50%;"></span> (Size: <?= $item['size'] ?>)</small>
                                            </span>
                                        </div>
                                    </td>
                                    <td>$<?= number_format($item['price'], 2) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-center" style="gap: 5px;">
                                            <button type="button" class="qty-control-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, parseInt(document.getElementById('qty-<?= $item['cart_id'] ?>').innerText) - 1, <?= $item['available_stock'] ?>)"><i class="fas fa-minus"></i></button>
                                            <span id="qty-<?= $item['cart_id'] ?>" class="qty-number"><?= $item['quantity'] ?></span>
                                            <button type="button" class="qty-control-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, parseInt(document.getElementById('qty-<?= $item['cart_id'] ?>').innerText) + 1, <?= $item['available_stock'] ?>)"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </td>
                                    <td id="subtotal-<?= $item['cart_id'] ?>">$<?= number_format($subtotal, 2) ?></td>
                                    <td><button class="btn btn-sm btn-outline-danger border-0" onclick="openRemoveModal(<?= $item['cart_id'] ?>)"><i class="fas fa-trash-alt"></i></button></td>
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
                        <div class="d-flex justify-content-between mb-3"><span>Total Items:</span><strong><?= $cart_count ?></strong></div>
                        <div class="d-flex justify-content-between mb-3"><span>Grand Total:</span><strong id="grand-total" class="text-primary fs-4">$<?= number_format($total_price, 2) ?></strong></div>
                        <a href="../utils/accessCheckout.php?supplier_id=<?= $supplier_id ?>" class="btn btn-primary w-100 py-3 mt-3 fw-bold" style="background: linear-gradient(145deg, rgba(159, 204, 223, 0.8), rgba(71, 78, 111, 0.6)); border: none; border-radius: 10px; color: white; display: block; text-align: center;">PROCEED TO CHECKOUT</a>
                        <a href="?supplier_id=<?= $supplier_id ?>&page=product" class="continue-shopping-btn"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-cart-arrow-down fs-1 text-muted"></i>
            <p class="mt-3">Your cart is empty.</p>
            <a href="?supplier_id=<?= $supplier_id ?>&page=collection" class="btn btn-primary" style="background: linear-gradient(145deg, rgba(159, 204, 223, 0.8), rgba(71, 78, 111, 0.6)); border: none;">Shop Now</a>
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
    // Configuration shared for all Glass Alerts
    const glassConfig = {
        customClass: {
            popup: 'glass-popup',
            title: 'glass-title',
            htmlContainer: 'glass-content',
            confirmButton: 'glass-confirm-btn'
        },
        buttonsStyling: false,
        timer: 2000,
        timerProgressBar: true
    };

    const customerId = <?= $customer_id ?>;
    const loginPromptModal = document.getElementById('loginPromptModal');

    if (customerId === 0 && loginPromptModal) {
        loginPromptModal.style.display = 'flex';
    }
    
    window.addEventListener('click', (e) => { if (e.target === loginPromptModal) window.location.href = 'shop/?supplier_id=<?= $supplier_id ?>&page=home'; });  

    let pendingCartId = null;
    let updateTimer = null;

    function closeModal() { document.getElementById('customDeleteModal').style.display = 'none'; pendingCartId = null; }
    function openRemoveModal(cartId) { pendingCartId = cartId; document.getElementById('customDeleteModal').style.display = 'flex'; }
    document.getElementById('confirmBtn').onclick = function () { if (pendingCartId) removeFromCart(pendingCartId); };

    function updateQuantity(cartId, newQty, availableStock) {
        if (newQty < 1) { openRemoveModal(cartId); return; }
        if (newQty > availableStock) {
            Swal.fire({ ...glassConfig, icon: 'warning', title: 'Out of Stock', text: 'Only ' + availableStock + ' items available.', timer: 3000 });
            return;
        }

        document.getElementById('qty-' + cartId).innerText = newQty;
        recalculateCart();

        clearTimeout(updateTimer);
        updateTimer = setTimeout(() => {
            fetch(window.location.origin + '/malltiverse/frontend/utils/update_cart_qty.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 'cart_id': cartId, 'quantity': newQty })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ ...glassConfig, icon: 'success', title: 'Updated', text: 'Quantity updated successfully', showConfirmButton: false });
                } else { location.reload(); }
            });
        }, 500);
    }

    function removeFromCart(cartId) {
        fetch(window.location.origin + '/malltiverse/frontend/utils/removeFromCart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ 'cart_id': cartId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                closeModal();
                Swal.fire({ ...glassConfig, icon: 'success', title: 'Removed', text: 'Item has been removed.', showConfirmButton: false })
                .then(() => location.reload());
            }
        });
    }

    function recalculateCart() {
        let grandTotal = 0;
        document.querySelectorAll('table tbody tr').forEach(row => {
            const priceCell = row.cells[1]; if(!priceCell) return;
            const price = parseFloat(priceCell.innerText.replace('$', '').replace(',', ''));
            const qty = parseInt(row.querySelector('.qty-number').innerText);
            const subtotal = price * qty;
            row.cells[3].innerText = '$' + subtotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
            grandTotal += subtotal;
        });
        document.getElementById('grand-total').innerText = '$' + grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
    }
</script>
</body>
</html>