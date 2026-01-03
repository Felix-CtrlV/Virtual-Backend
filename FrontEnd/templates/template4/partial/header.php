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
        <!-- <button class="login-link">Log In</button> -->
    </div>




</header>