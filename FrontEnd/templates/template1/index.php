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
if($company_query){
    mysqli_stmt_bind_param($company_query, "i", $supplier_id);
    mysqli_stmt_execute($company_query);
    $company_result = mysqli_stmt_get_result($company_query);
}else{
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



$supplier_id = (int) $supplier['supplier_id'];



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



$cart_count = 0;

if (isset($_SESSION['customer_id'])) {

    $cart_count = getCartCount($conn, $_SESSION['customer_id']);

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

    <title><?= htmlspecialchars($supplier['company_name']) ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <link rel="stylesheet" href="../templates/<?= basename(__DIR__) ?>/style.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {

        --primary: <?= htmlspecialchars($primaryColor) ?>;
        --secondary: <?= htmlspecialchars($secondaryColor) ?>;

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
\
<!-- Auth popup -->
<div id="authModal" class="auth-modal">
  <div class="auth-box">
    <h3>Login Required</h3>
    <p>Please login or create an account to continue.</p>
    <div class="auth-actions">
      <button id="authLoginBtn">Login</button>
      <button id="authRegisterBtn">Create Account</button>
    </div>
    <button class="auth-close">&times;</button>
  </div>
</div>

<style>
.auth-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 99999999; }
.auth-modal.show { display: flex; }
.auth-box {
    position: relative; /* make positioning for child absolute elements work */
    background: #fff;
    padding: 24px;
    border-radius: 14px;
    width: 320px;
    text-align: center;
}

/* Style the close button */
.auth-close {
    position: absolute;
    top: 2px;
    right: 12px;
    background: transparent;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #333;
}

.auth-actions button { margin: 10px; padding: 10px 16px; cursor: pointer; }
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
                return;
            }

            cartItemsContainer.innerHTML = "";

            data.items.forEach(item => {
                cartItemsContainer.innerHTML += `
                    <div class="cart-item mb-3">
                        <strong>${item.name}</strong><br>
                        Qty: ${item.qty}<br>
                        $${parseFloat(item.price).toFixed(2)}
                    </div>
                `;
            });
        })
        .catch(err => {
            console.error(err);
            renderEmptyCart();
        });
}

/* ---------- CLICK BIND ---------- */
cartTrigger?.addEventListener("click", () => {
    refreshCartDrawer(<?= (int)$supplier_id ?>);
});
</script>


</body>

</html>