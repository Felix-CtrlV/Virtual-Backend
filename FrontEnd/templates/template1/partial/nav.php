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

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=products">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=review">Review</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=about">About</a></li>
                
                <li class="nav-item ms-lg-3">
                    <a href="javascript:void(0)" id="cartIconTrigger" class="nav-link position-relative cart-link">
                        <i class="bi bi-cart fs-4"></i>
                        <span class="badge rounded-pill bg-danger cart-badge" id="nav-cart-count" style="font-size: 0.7rem;"></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

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