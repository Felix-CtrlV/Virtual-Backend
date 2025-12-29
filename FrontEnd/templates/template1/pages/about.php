<div class="about-content">
    <div class="about_image">
        <img src="../uploads/supplier_shop_id/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
            alt="Company Logo" class="about-logo">
    </div>
    <div class="about-text-container">
        <h2>About Us</h2>
        <?php if (!empty($supplier['description'])): ?>
            <p><?= nl2br(htmlspecialchars($shop_assets['about'])) ?></p>
        <?php else: ?>
            <p>No description available for this supplier.</p>
        <?php endif; ?>
        
        
        <div class="extras">
            <button class="product-page-btn"
        onclick="window.location.href='<?= $base_url ?>&page=products'">
    Shop With Us
</button>

<button class="contact-page-btn"
        onclick="window.location.href='<?= $base_url ?>&page=contact'">
    Get In Touch With Us
</button>

        </div>

    </div>
</div>