<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <div class="logo-box">MALL<span>TIVERSE</span></div>
            <p>Making the world’s data accessible and useful for teams everywhere.</p>
            <div class="social-icons">
                <a href="#" title="X (Twitter)">𝕏</a>
                <a href="#" title="LinkedIn">in</a>
                <a href="#" title="Instagram">ig</a>
            </div>
        </div>

        <div class="footer-right-group">
            <div class="footer-column">
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <?php $base_url = "?supplier_id=" . $supplier_id; ?>
                    <li><a href="<?= $base_url ?>&page=home">Home</a></li>
                    <li><a href="<?= $base_url ?>&page=shop">Shop</a></li>
                    <li><a href="<?= $base_url ?>&page=about">About Us</a></li>
                    <li><a href="<?= $base_url ?>&page=collection">Collection</a></li>
                    <li><a href="<?= $base_url ?>&page=review">Review</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h5>Contact Info</h5>
                <div class="contact-details">
                    <?php if (!empty($supplier['email'])): ?>
                        <p><i class="bi bi-envelope"></i> <a href="mailto:<?= htmlspecialchars($supplier['email']) ?>"><?= htmlspecialchars($supplier['email']) ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($supplier['phone'])): ?>
                        <p><i class="bi bi-phone"></i> <?= htmlspecialchars($supplier['phone']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($supplier['address'])): ?>
                        <p><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($supplier['address']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom-section">
        <hr class="footer-divider">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($supplier['company_name'] ?? 'MALLTIVERSE') ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

