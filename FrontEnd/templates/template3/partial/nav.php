<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<nav class="main-nav navbar navbar-expand-lg">
    <div class="container">
        <div class="header-wrapper">
            <?php if (!empty($shop_assets['logo'])): ?>
                <div class="logo-container">
                    <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                        alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo" class="NFlogo">
                </div>
            <?php endif; ?>
            <div class="header-text">
                <h1 class="site-title"><?= htmlspecialchars($supplier['company_name']) ?></h1>
                <?php if (!empty($supplier['tagline'])): ?>
                    <p class="site-tagline"><?= htmlspecialchars($supplier['tagline']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <ul class="navbar-nav me-auto">
            <?php
            $base_url = "?supplier_id=" . $supplier_id;
            ?>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'home' ? 'active' : '' ?>" href="<?= $base_url ?>&page=home">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'products' ? 'active' : '' ?>"
                    href="<?= $base_url ?>&page=products">Shop</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'about' ? 'active' : '' ?>" href="<?= $base_url ?>&page=about">About
                    Us</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'collection' ? 'active' : '' ?>"
                    href="<?= $base_url ?>&page=collection">Collection</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'review' ? 'active' : '' ?>"
                    href="<?= $base_url ?>&page=review">Review</a>
            </li>
        </ul>        
    </div>
</nav>