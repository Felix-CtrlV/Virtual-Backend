<div id="basket-container" class="detail_card" style="margin: 40px auto; max-width: 1200px; width: 90%;">
    <div class="related-section-header">
        <h2>Your Bag</h2>
        <div class="related-section-line"></div>
    </div>

    <div id="basket-items" style="margin-top: 20px;">
    </div>

    <div style="margin-top: 20px; text-align: right; padding-right: 20px;">
        <h3 style="font-size: 1.5rem;">Total: $<span id="total-price">0</span></h3>
        <button class="product-page-btn" style="width: auto; margin-top: 15px;">Checkout Now</button>
    </div>
</div>
<footer class="footer">
    <div class="footer-inner">
        <h5><?= htmlspecialchars($supplier['company_name']) ?></h5>
        <?php if (!empty($supplier['description'])): ?>
            <p><?= htmlspecialchars(substr($supplier['description'], 0, 150)) ?>...</p>
        <?php endif; ?>

        <div class="footer-links">
            <?php
            $base_url = "?supplier_id=" . $supplier_id;
            ?>
            <li><a href="<?= $base_url ?>&page=home">Home</a></li>
            <li><a href="<?= $base_url ?>&page=products">Products</a></li>
            <li><a href="<?= $base_url ?>&page=about">About</a></li>
            <li><a href="<?= $base_url ?>&page=contact">Contact</a></li>
        </div>
    </div>

    <div class="footer-bar"></div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($supplier['company_name']) ?>. All rights reserved.</p>
    </div>
</footer>