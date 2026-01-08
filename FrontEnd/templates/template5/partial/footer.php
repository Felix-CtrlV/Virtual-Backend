

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="row">
                <div class="col-md-4">
                    <h5><?= htmlspecialchars($supplier['company_name']) ?></h5>
                    <?php if (!empty($supplier['description'])): ?>
                        <p><?= htmlspecialchars(substr($supplier['description'], 0, 150)) ?>...</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <?php
                        $base_url = "?supplier_id=" . $supplier_id;
                        ?>
                        <li><a href="<?= $base_url ?>&page=home">Home</a></li>
                        <li><a href="<?= $base_url ?>&page=products">Products</a></li>
                        <li><a href="<?= $base_url ?>&page=about">About</a></li>
                        <li><a href="<?= $base_url ?>&page=contact">Contact</a></li>
                        <li><a href="<?= $base_url ?>&page=review">Review</a></li>
                    </ul>
                </div>

                <div class="col-md-4">
                    <h5>Contact Info</h5>
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
            <hr class="footer-divider">
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($supplier['company_name']) ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

