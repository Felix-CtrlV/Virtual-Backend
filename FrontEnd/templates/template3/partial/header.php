<header class="site-header">
    <div class="container">
        <div class="header-wrapper">
            <?php if (!empty($shop_assets['logo'])): ?>
                <div class="logo-container">
                    <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>" 
                         alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo" 
                         class="NFlogo">
                </div>
            <?php endif; ?>
            <div class="header-text">
                <h1 class="site-title"><?= htmlspecialchars($supplier['company_name']) ?></h1>
                <?php if (!empty($supplier['tagline'])): ?>
                    <p class="site-tagline"><?= htmlspecialchars($supplier['tagline']) ?></p>
                <?php endif; ?>
            </div>            
        </div>
    </div>
</header>

