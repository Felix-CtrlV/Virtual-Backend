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
