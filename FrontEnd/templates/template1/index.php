<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();

}


if (!isset($conn)) {

    include '../../../BackEnd/config/dbconfig.php';


}
require_once __DIR__ . '/../../utils/Ordered.php';

require_once __DIR__ . '/../../utils/colorSwitch.php';


// 3. ORDER PROCESSING LOGIC
$customer_id = $_SESSION['customer_id'] ?? 0; // Use session if available, else 1
$supplier_id = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;

$company_query = mysqli_prepare($conn, "select * from companies where supplier_id = ?");
if ($company_query) {
    mysqli_stmt_bind_param($company_query, "i", $supplier_id);
    mysqli_stmt_execute($company_query);
    $company_result = mysqli_stmt_get_result($company_query);
} else {
    $company_result = false;
}

$company_row = mysqli_fetch_assoc($company_result);
$company_id = $company_row['company_id'];


if (isset($_GET['payment_status']) && $_GET['payment_status'] === 'success') {
    $is_ordered = placeOrder($conn, $customer_id, $company_id);

    if (!$is_ordered) {
        echo "<script>alert('Order failed: Stock may have changed or cart is empty.');</script>";
    }
}

// Check for the clean redirect parameter to show the success message
if (isset($_GET['payment_success']) && $_GET['payment_success'] === 'true') {
    echo "<script>alert('Order Placed Successfully!');</script>";
}

// --- (Your existing shop_assets logic) ---

$assets_stmt = mysqli_prepare($conn, "SELECT * FROM shop_assets WHERE company_id = ?");

if ($assets_stmt) {

    mysqli_stmt_bind_param($assets_stmt, "i", $company_id);

    mysqli_stmt_execute($assets_stmt);

    $assets_result = mysqli_stmt_get_result($assets_stmt);

} else {

    $assets_result = false;

}



if ($assets_result && mysqli_num_rows($assets_result) > 0) {

    $shop_assets = mysqli_fetch_assoc($assets_result);

    if (isset($assets_stmt)) {

        mysqli_stmt_close($assets_stmt);

    }

} else {

    $shop_assets = [

        'logo' => 'default_logo.png',

        'banner' => 'default_banner.jpg',

        'primary_color' => '#4a90e2',

        'secondary_color' => '#2c3e50'

    ];

}

$primaryColor = $shop_assets['primary_color'];
$secondaryColor = resolveSecondaryColor(
    $primaryColor,
    $shop_assets['secondary_color']
);



$page = isset($_GET['page']) ? $_GET['page'] : 'home';

$allowed_pages = ['home', 'about', 'products', 'productDetail', 'review', 'contact', 'cart'];

if (!in_array($page, $allowed_pages)) {

    $page = 'home';

}



// ==========================================

// NEW CART COUNT LOGIC START

// ==========================================

function getCartCount($conn, $customer_id)
{

    $count = 0;

    // We sum the 'quantity' column from your cart table for this specific user

    $stmt = mysqli_prepare($conn, "SELECT SUM(quantity) as total_items FROM cart WHERE customer_id = ?");

    mysqli_stmt_bind_param($stmt, "i", $customer_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $count = $row['total_items'] ? $row['total_items'] : 0;

    }

    mysqli_stmt_close($stmt);

    return $count;

}

// 1. Initialize with a default value to prevent "Undefined variable" error
$cart_count = 0;

// 2. Only update it if the user is logged in
if (isset($_SESSION['customer_id'])) {
    $cart_count = getCartCount($conn, $_SESSION['customer_id'], $company_id);
}



// ==========================================

// NEW CART COUNT LOGIC END

// ==========================================



$page_path = __DIR__ . "/pages/$page.php";


?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($company_row['company_name'] ?? 'Shop') ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <link rel="stylesheet" href="../templates/<?= basename(__DIR__) ?>/style.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {

            --primary:
                <?= htmlspecialchars($primaryColor) ?>
            ;
            --secondary:
                <?= htmlspecialchars($secondaryColor) ?>
            ;

        }
    </style>

</head>

<body>

    <?php include(__DIR__ . '/partial/nav.php'); ?>



    <main class="main-content">

        <?php

        if (file_exists($page_path)) {

            include($page_path);

        } else {

            echo "<div class='container'><p>Page not found.</p></div>";

        }

        ?>

    </main>



    <?php include(__DIR__ . '/partial/footer.php'); ?>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../templates/<?= basename(__DIR__) ?>/script.js"></script>



    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-muted">
                    Are you sure you want to remove this product from your bag?
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary px-4" id="confirmDeleteBtn">Yes, Remove</button>
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

<!-- Auth popup -->
<div id="authModal" class="auth-modal">
  <div class="auth-box">
    <h3>Login Required</h3>
    <p>Please login or create an account to continue.</p>
    <div class="auth-actions">
      <button id="authLoginBtn">Login</button>
      <button id="authRegisterBtn">Create Account</button>
    </div>

    <style>
        /* Backdrop - darker and blurred */
        .auth-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            /* Adds a premium blurred background */
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            padding: 20px;
        }

        .auth-modal.show {
            display: flex;
        }

        /* The White Box */
        .auth-box {
            position: relative;
            background: #fff;
            padding: 40px 30px;
            border-radius: 24px;
            /* Very rounded corners */
            width: 100%;
            max-width: 380px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .auth-box h3 {
            font-weight: 800;
            font-size: 1.6rem;
            margin-bottom: 12px;
            color: #1a1a1a;
        }

        .auth-box p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        /* Buttons Layout */
        .auth-actions {
            display: flex;
            flex-direction: column;
            /* Stacked buttons like the image */
            gap: 12px;
        }

        /* Login Button (Black/Dark) */
        #authLoginBtn {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            width: 100%;
        }

        #authLoginBtn:hover {
            background: #000;
            transform: translateY(-2px);
        }

        /* Register Button (Outline) */
        #authRegisterBtn {
            background: transparent;
            color: #1a1a1a;
            border: 2px solid #e0e0e0;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            width: 100%;
        }

        #authRegisterBtn:hover {
            border-color: #1a1a1a;
            background: #f8f8f8;
        }

        /* Close Button Style */
        .auth-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: #f0f0f0;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            transition: 0.2s;
        }

        .auth-close:hover {
            background: #e0e0e0;
            color: #000;
        }
    </style>

    <script>
        function openAuthModal() {
            document.getElementById("authModal")?.classList.add("show");
        }
        document.querySelector(".auth-close")?.addEventListener("click", () => {
            document.getElementById("authModal").classList.remove("show");
        });
        document.getElementById("authLoginBtn")?.addEventListener("click", () => {
            window.location.href = "/Malltiverse/FrontEnd/customerLogin.php";
        });
        document.getElementById("authRegisterBtn")?.addEventListener("click", () => {
            window.location.href = "/Malltiverse/FrontEnd/customerRegister.php";
        });
    </script>

    <script>
        /* ================================
           CART DRAWER CORE LOGIC
        ================================ */

        const cartDrawer = document.getElementById("cartDrawer");
        const cartOverlay = document.getElementById("cartOverlay");
        const cartItemsContainer = document.getElementById("cartItemsContainer");
        const closeCartBtn = document.getElementById("closeCart");
        const cartTrigger = document.getElementById("cartIconTrigger");

        /* ---------- OPEN / CLOSE ---------- */
        function openCart() {
            cartDrawer.classList.add("open");
            cartOverlay.classList.add("active");
        }

        function closeCart() {
            cartDrawer.classList.remove("open");
            cartOverlay.classList.remove("active");
        }

        closeCartBtn?.addEventListener("click", closeCart);
        cartOverlay?.addEventListener("click", closeCart);

        /* ---------- GUEST VIEW ---------- */
        function renderGuestCart() {
            cartItemsContainer.innerHTML = `
        <div style="padding:24px; text-align:center">
            <p>Please login or create an account to view your cart.</p>
            <button onclick="location.href='/Malltiverse/FrontEnd/customerLogin.php'" class="btn btn-dark me-2">Login</button>
            <button onclick="location.href='/Malltiverse/FrontEnd/customerRegister.php'" class="btn btn-outline-dark">Register</button>
        </div>
    `;
        }

        /* ---------- EMPTY CART ---------- */
        function renderEmptyCart() {
            cartItemsContainer.innerHTML = `
        <p class="text-center text-muted mt-4">Your bag is empty.</p>
    `;
        }
        /* ---------- LOAD CART ---------- */
        function refreshCartDrawer(supplierId) {
            openCart();

            if (!window.IS_LOGGED_IN) {
                renderGuestCart();
                return;
            }

            fetch(`../utils/get_cart_data.php?supplier_id=${supplierId}`)
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.items || data.items.length === 0) {
                        renderEmptyCart();
                        document.getElementById('cartFooter').innerHTML = ''; // Clear footer if empty

                        const badge = document.getElementById('nav-cart-count');
                        if (badge) { badge.style.display = 'none'; badge.innerText = '0'; }
                        return;
                    }

                    let html = '';
                    data.items.forEach(item => {
                        const availableStock = item.availableStock !== undefined ? item.availableStock : 999;

                        // Matches the layout of the first image
                        html += `
                <div class="cart-item-block mb-4" style="padding: 10px; border-bottom: 1px solid #eee;">
                    <div class="d-flex gap-3">
                        <img src="${item.image}" alt="${item.name}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold">${item.name}</h6>
                            <div class="text-muted small d-flex align-items-center gap-2 mb-2">
                                <span>Color:</span>
                                <span style="background-color: ${item.color_code || '#000'}; width: 12px; height: 12px; border-radius: 50%; display: inline-block; border: 1px solid #ddd;"></span>
                                <span>| Size: ${item.size || 'N/A'}</span>
                                <span>| Qty: ${item.qty}</span>
                            </div>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="qty-selector-container d-flex align-items-center gap-2">
                                    <button class="btn btn-sm btn-light border qty-button" onclick="changeQty(${item.cart_id}, ${item.qty}, -1, ${supplierId}, ${availableStock})">−</button>
                                    <span class="qty-display fw-bold" style="min-width: 20px; text-align: center;">${item.qty}</span>
                                    <button class="btn btn-sm btn-light border qty-button" onclick="changeQty(${item.cart_id}, ${item.qty}, 1, ${supplierId}, ${availableStock})">+</button>
                                </div>
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

                    cartItemsContainer.innerHTML = html;

                    const badge = document.getElementById('nav-cart-count');
                    if (badge) {
                        badge.innerText = data.items.length; // This makes it "2" instead of "3"
                        badge.style.display = 'flex';
                    }

                    // Update Total and Checkout Button
                    const footer = document.getElementById('cartFooter');
                    if (footer) {
                        footer.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-3 mt-3 pt-3 border-top">
                        <span class="fs-5">Total:</span>
                        <span class="fs-4 fw-bold">$${data.total}</span>
                    </div>
                    <button class="btn btn-dark w-100 py-3 rounded-pill fw-bold" onclick="window.location.href='../utils/accessCheckout.php?supplier_id=${supplierId}'">
                        Checkout
                    </button>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    renderEmptyCart();
                });
        }

        /* ---------- CLICK BIND ---------- */
        cartTrigger?.addEventListener("click", () => {
            refreshCartDrawer(<?= (int) $supplier_id ?>);
        });
    </script>

    <?php // Re-open PHP to echo the variables safely ?>
    <script>
        window.IS_LOGGED_IN = <?= isset($_SESSION['customer_id']) ? 'true' : 'false' ?>;
        window.INITIAL_CART_COUNT = <?= (int) $cart_count ?>;
    </script>

</body>

</html>