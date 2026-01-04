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

<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h2 class="footer-logo">LUXURY<span>WATCH</span></h2>
            <p>Providing high-quality products 2026. Quality you can trust, delivered to your door.</p>
            <div class="social-links">
                <a href=""><i class="fab fa-facebook-f"></i></a>
                <a href=""><i class="fab fa-instagram"></i></a>
                <a href=""><i class="fab fa-twitter"></i></a>
                <a href=""><i class="fab fa-viber"></i></a>
            </div>
        </div>

        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="review.php">Review</a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h3>Contact Us</h3>
            <p><i class="fas fa-envelope"></i> kaungpyaesone@gmail.com</p>
            <p><i class="fas fa-envelope"></i> kaungswanthaw@gmail.com</p>
            <p><i class="fas fa-phone"></i> +95 123456</p>
            <p><i class="fas fa-map-marker-alt"></i> Metro IT and Japanese Language Center</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> <span>MALLTIVERSE</span>. All rights reserved.</p>
        

    </div>
</footer>