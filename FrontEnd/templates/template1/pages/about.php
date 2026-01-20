<?php

 // later make dynamic if needed

$sql = "SELECT about, banner FROM shop_assets WHERE supplier_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();
$shop_assets = $result->fetch_assoc();

$about_text = $shop_assets['about'] ?? '';
$banner = $shop_assets['banner'] ?? '';
?>
<section class="about-hero"
    style="background-image: url('../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($banner) ?>');">

    <div class="about-overlay">

        <div class="about-center-box">

            <h2 class="about-title">WHO ARE WE?</h2>

            <?php if (!empty($about_text)): ?>
                <p class="about-description">
                    <?= nl2br(htmlspecialchars($about_text)) ?>
                </p>
            <?php else: ?>
                <p class="about-description">
                    No description available.
                </p>
            <?php endif; ?>

            <div class="about-buttons">
                <a href="?page=products&supplier_id=<?= $supplier_id ?>" class="product-page-btn">
                    Shop With Us
                </a>

                <a href="?page=contact&supplier_id=<?= $supplier_id ?>" class="contact-page-btn">
                    Get In Touch
                </a>
            </div>

        </div>

    </div>
</section>