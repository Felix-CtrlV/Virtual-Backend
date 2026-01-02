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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    

   <section class="hero-container">
    <div class="hero-content">
       <span class="category-title">
    <i class="fa-solid fa-clock"></i> Luxury Watch
</span>
        <h2 class="home" style="width:500px;"><?= $shop_assets['description']?></h2>
        <p class="hero-description"><b><i>Discover Your Favorite Smart Watch.</b></i></p> 
        <br>
        <a href="?supplier_id=<?= $supplier['supplier_id'] ?>&page=products" class="btn-shop-now">
         SHOP NOW
        </a>
    </div>
    



    <div class="hero-banner">
        <div class="banner-shape-wrapper">
            <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>" class="fashion-banner">
        </div>
    </div>
</section>

    <!--Contact Section -->

<section class="hero">
    <div class="hero-content1">
        <span class="subtitle">Handcrafted Excellence</span>
        <h1>Precision in Every Second</h1>
        <p>Discover the art of horology with our limited edition 2025 collection.</p>
        <br>
        <a href="#" class="btn-primary">Explore Collection</a>
    </div>
</section>
    
<section class="features">
    <div class="feature-card">
        <h3>Swiss Movement</h3>
        <p><b><i>Engineered with world-class precision and 72-hour power reserve.</i></b></p>
    </div>
    <div class="feature-card">
        <h3>Sapphire Crystal</h3>
        <p><b><i>Scratch-resistant clarity designed to last a lifetime.</i></b></p>
    </div>
    <div class="feature-card">
        <h3>Heritage</h3>
        <p><b><i>A legacy of watchmaking spanning over a century of innovation.</i></b></p>
    </div>
</section>

    <!--Footer Section-->

<footer class="luxury-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <h2>MALLTIVERSE</h2>
            <p><i>Every moment is precious.</i></p>
        </div>
        
        <div class="footer-links">
            <h4>Collections</h4>
            <ul>
                <li class="nav-item"><a href="">Chronograph</a></li>
                <li class="nav-item"><a href="">Diving</a></li>
                <li class="nav-item"><a href="">Minimalist</a></li>
            </ul>
        </div>
        <div class="footer-links">
            <h4>Support</h4>
            <ul>
                <li class="nav-item"><a href="">Servicing</a></li>
                <li class="nav-item"><a href="">Warranty</a></li>
                <li class="nav-item"><a href="">Contact</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
       <b> <p>&copy; 2025 MALLTIVERSE Luxury Watches. All Rights Reserved.</p></b>
    </div>
</footer>