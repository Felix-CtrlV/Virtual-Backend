<nav class="main-nav navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="?supplier_id=<?= $supplier_id ?>&page=home">
            <?php if (!empty($shop_assets['logo'])): ?>
                <div class="logo-container">
                    <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                         alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo" class="site-logo">
                </div>
            <?php endif; ?>
            <div class="header-text">
                <h1 class="site-title mb-0"><?= htmlspecialchars($supplier['company_name']) ?></h1>
            </div>
        </a>

        <div class="d-flex align-items-center gap-4 ms-auto order-lg-last" style="margin-right: 35px;">
            <a href="javascript:void(0)" id="cartIconTrigger" class="position-relative cart-link"
                onclick="<?= $isLoggedIn ? '' : 'showLoginPopup(); event.stopPropagation();' ?>">
                <i class="bi bi-cart fs-4"></i>
                <span class="badge rounded-pill bg-danger cart-badge" id="nav-cart-count"
                    style="font-size: 0.7rem;"></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="color: var(--primary);">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=products">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=review">Review</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="/malltiverse/frontend/customer">Exit</a></li>
               
            </ul>
        </div>
    </div>
</nav>
<style>
    .cart-sidebar {
    position: fixed;
    top: 0;
    right: -400px; /* hidden off-screen */
    width: 350px;
    height: 100%;
    background: white;
    box-shadow: -5px 0 15px rgba(0,0,0,0.2);
    display: flex;
    z-index: 9999;
    flex-direction: column;
    visibility: hidden;
    transition: right 0.3s ease, visibility 0.3s;
}

.cart-sidebar.open {
   right: 0 !important;
    visibility: visible !important;
}

.cart-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease;
    z-index: 9998;
}

.cart-overlay.active {
    opacity: 1;
    visibility: visible;
}

 
</style>



<div id="cartDrawer" class="cart-sidebar">
    <div class="cart-sidebar-header d-flex justify-content-between align-items-center">
        <h2 class="h4 mb-0 fw-bold">Your Bag</h2>
        <button id="closeCart" class="btn-close close-btn shadow-none" style="font-size: 0.8rem;"></button>
    </div>
    <hr class="my-3 opacity-25">
    <div id="cartItemsContainer" style="flex: 1; overflow-y: auto; padding-right: 5px;"></div>
    <div id="cartFooter" class="pt-3 border-top mt-auto"></div>
</div>

<div id="cartOverlay" class="cart-overlay"></div>