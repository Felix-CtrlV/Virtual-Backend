
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
        <h2>New collection<br>for Luxury Watch</h2>
        <p class="hero-description"><b><i>Discover Your Favorite Smart Watch.</b></i></p>
        
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
    <div class="hero-content">
        <span class="subtitle">Handcrafted Excellence</span>
        <h1>Precision in Every Second</h1>
        <p>Discover the art of horology with our limited edition 2025 collection.</p>
        <a href="#" class="btn-primary">Explore Collection</a>
    </div>
</section>
    
<section class="features">
    <div class="feature-card">
        <h3>Swiss Movement</h3>
        <p>Engineered with world-class precision and 72-hour power reserve.</p>
    </div>
    <div class="feature-card">
        <h3>Sapphire Crystal</h3>
        <p>Scratch-resistant clarity designed to last a lifetime.</p>
    </div>
    <div class="feature-card">
        <h3>Heritage</h3>
        <p>A legacy of watchmaking spanning over a century of innovation.</p>
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