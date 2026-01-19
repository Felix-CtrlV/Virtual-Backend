<link rel="stylesheet" href="../templates/template4/style.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.lordicon.com/lordicon.js"></script>
<header class="smart-header">
    <div class="logo-container">
        <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
            alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo"
            class="site-logo">
    </div>
    <ul class="nav-menu">
        <?php
        $base_url = "?supplier_id=" . $supplier_id;
        ?>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'home' ? 'active' : '' ?>" href="<?= $base_url ?>&page=home">Home</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'products' ? 'active' : '' ?>" href="<?= $base_url ?>&page=products">Products</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'about' ? 'active' : '' ?>" href="<?= $base_url ?>&page=about">About</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'contact' ? 'active' : '' ?>" href="<?= $base_url ?>&page=contact">Contact</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'review' ? 'active' : '' ?>" href="<?= $base_url ?>&page=review">Review</a>
        </li>
    </ul>

    <div class="auth-buttons">
        <button class="cart-icon-btn" id="cart-icon-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span class="cart-badge" id="cart-badge">0</span>
        </button>
        </div>

    <div class="cart-popup" id="cart-popup">
        <div class="cart-popup-content">
            <div class="cart-popup-header">
                <h3>Your Cart</h3>
                <button class="cart-close-btn" id="cart-close-btn">&times;</button>
            </div>
            <div class="cart-popup-body" id="cart-items-container">
                <div class="cart-empty">Your cart is empty</div>
            </div>
            <div class="cart-popup-footer" id="cart-footer" style="display: none;">
                <div class="cart-total">
                    <span>Total:</span>
                    <span id="cart-total-amount">$0.00</span>
                </div>
                <button class="cart-checkout-btn" onclick="window.location.href='?supplier_id=<?= $supplier_id ?>&page=cart'">Checkout</button>
            </div>
        </div>
    </div>

    <div class="minimal-alert" id="minimal-alert"></div>

</header>