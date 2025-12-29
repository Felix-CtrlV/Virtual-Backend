<nav class="main-nav navbar navbar-expand-lg">
    <div class="container-fluid">


        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $base_url ?>&page=home">
            <?php if (!empty($shop_assets['logo'])): ?>
                <div class="logo-container">
                    <img src="../uploads/supplier_shop_id/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                        alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo" class="site-logo">
                </div>
            <?php endif; ?>

            <div class="header-text">
                <h1 class="site-title mb-0"><?= htmlspecialchars($supplier['company_name']) ?></h1>
                <?php if (!empty($supplier['tagline'])): ?>
                    <small class="site-tagline"><?= htmlspecialchars($supplier['tagline']) ?></small>
                <?php endif; ?>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php
                $base_url = "?supplier_id=" . $supplier_id;
                ?>
                <li class="nav-item"><a class="nav-link <?= $page === 'home' ? 'active' : '' ?>"
                        href="<?= $base_url ?>&page=home">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $page === 'products' ? 'active' : '' ?>"
                        href="<?= $base_url ?>&page=products">Products</a></li>
                <li class="nav-item"><a class="nav-link <?= $page === 'review' ? 'active' : '' ?>"
                        href="<?= $base_url ?>&page=review">Review</a></li>
                <li class="nav-item"><a class="nav-link <?= $page === 'contact' ? 'active' : '' ?>"
                        href="<?= $base_url ?>&page=contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link <?= $page === 'about' ? 'active' : '' ?>"
                        href="<?= $base_url ?>&page=about">About</a></li>
            </ul>
        </div>

    </div>
</nav>