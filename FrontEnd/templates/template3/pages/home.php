<?php
$current_page = 'home.php';
?>

<head>
     <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($supplier['company_name']) ?></title>
    <link rel="stylesheet" href="../templates/<?= basename(__DIR__) ?>/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <span class="title">NEW FASHION</span>
            <h2>New collection for 2026</h2>
            <p>Discover Your Favorite Style: All the Fashion You Need Awaits Here!</p>
            <a href="?supplier_id=<?= $supplier['supplier_id'] ?>&page=products" class="btn-shop-now">SHOP NOW</a>
        </div>
        <div class="hero-banner">
            <div class="logo-container">

                <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>) ?>"
                    alt="<?= htmlspecialchars($supplier['company_name']) ?> banner" class="fashion-banner">
            </div>
        </div>
    </section>