<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<nav class="main-nav navbar navbar-expand-lg navbar-light bg-light border-bottom sticky-top">
    <div class="container">
        <a href="<?= $base_url ?>&page=home" class="brand-link navbar-brand py-0">
            <?php if (!empty($shop_assets['logo'])): ?>
                <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                     alt="Logo" 
                     class="NFlogo rounded-circle" 
                     style="height: 50px; width: 50px; object-fit: cover; display: block;">
            <?php endif; ?>
            
            <div class="header-text d-flex flex-column justify-content-center">
                <h1 class="site-title-text fs-4">
                    <?= htmlspecialchars($supplier['tags']) ?>
                </h1>
                <?php if (!empty($supplier['tagline'])): ?>
                    <p class="site-tagline mb-0 text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($supplier['tagline']) ?></p>
                <?php endif; ?>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto"> <?php
                $base_url = "?supplier_id=" . $supplier_id;
                $nav_items = [
                    'home' => 'Home',
                    'products' => 'Products',
                    'about' => 'About Us',
                    'contact' => 'Contact',
                    'review' => 'Review'
                ];
                
                foreach ($nav_items as $key => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page === $key) ? 'active fw-bold' : '' ?>" 
                           href="<?= $base_url ?>&page=<?= $key ?>">
                           <?= $label ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="nav-cart d-flex align-items-center">
                <a href="<?= $base_url ?>&page=cart" class="position-relative text-dark">
                    <i class="fas fa-shopping-cart fa-lg"></i>
                    <span id="cart-badge-count" 
                          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                          style="font-size: 0.6rem; min-width: 18px; height: 18px; display: none;">
                        0
                    </span>
                </a>
            </div>
        </div>
    </div>
</nav>
