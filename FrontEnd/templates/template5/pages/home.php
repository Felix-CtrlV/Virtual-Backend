<?php
// home.php

// 1️⃣ Supplier ID ကို GET parameter မှာယူ၊ default = 10
$supplier_id = $_GET['supplier_id'] ?? 10;

// 2️⃣ Supplier data array (dynamic content)
$suppliers = [
    10 => [
        'company_name' => 'Rolex Official',
        'hero_class'   => 'luxury-hero',
        'category_title' => 'Luxury Watch',
        'category_icon'  => 'fa-clock',
        'description'    => 'Discover our exclusive luxury watches collection',
        'banner'         => 'banner1.png',
        'section'        => [
            'badge'       => 'Exclusive Luxury Watches',
            'badge_icon'  => 'fa-gem',
            'title'       => 'Timeless Elegance, Crafted to Perfection',
            'description' => 'Each watch is a masterpiece of precision engineering and refined design. Designed for those who value sophistication, durability, and timeless style.',
            'features'    => ['Swiss Movement Precision','Sapphire Crystal Glass','Limited Edition Collections'],
            'image'       => 'luxury-watch.png'
        ]
    ],
    4 => [
        'company_name' => 'Uniqlo Sports',
        'hero_class'   => 'sport-hero',
        'category_title' => 'Sport Wear',
        'category_icon'  => 'fa-bolt',
        'description'    => 'Check out our latest sport wear collection',
        'banner'         => 'banner1.png',
        'section'        => [
            'badge'       => 'Sport Collection',
            'badge_icon'  => 'fa-bolt',
            'title'       => 'Move Freely, Perform Better',
            'description' => 'Our sport wear combines comfort and durability. Perfect for active lifestyle enthusiasts.',
            'features'    => ['Breathable Materials','Flexible Fit','Modern Design'],
            'image'       => 'sport.png'
        ]
    ],
    5 => [
        'company_name' => 'Casual Store',
        'hero_class'   => 'casual-hero',
        'category_title' => 'Casual Wear',
        'category_icon'  => 'fa-shirt',
        'description'    => 'Explore our casual collection',
        'banner'         => 'banner1.png',
        'section'        => [
            'badge'       => 'Casual Collection',
            'badge_icon'  => 'fa-shirt',
            'title'       => 'Relaxed Style, Everyday Comfort',
            'description' => 'Trendy and comfortable casual wear for all occasions.',
            'features'    => ['Soft Fabric','Modern Cuts','Affordable Prices'],
            'image'       => 'casual.png'
        ]
    ],
];

// 3️⃣ Current supplier data
$supplier = $suppliers[$supplier_id] ?? $suppliers[10];
$section = $supplier['section'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($supplier['company_name']) ?></title>
    <link rel="stylesheet" href="../templates/<?= basename(__DIR__) ?>/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Hero Section -->
<section class="hero-container <?= htmlspecialchars($supplier['hero_class']) ?>">
    <div class="hero-content">
        <span class="category-title">
            <i class="fa-solid <?= htmlspecialchars($supplier['category_icon']) ?>"></i>
            <?= htmlspecialchars($supplier['category_title']) ?>
        </span>

        <h2 class="home" style="width:500px;">
            <?= htmlspecialchars($supplier['description']) ?>
        </h2>

        <br>

        <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="btn-shop-now">
            SHOP NOW
        </a>
    </div>

    <div class="hero-banner">
        <div class="banner-shape-wrapper">
             <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>"
                    alt="<?= htmlspecialchars($supplier['company_name']) ?> banner" class="fashion-banner">
        </div>
    </div>
</section>

<section class="collection-section py-5">
    <div class="container">
        <div class="row align-items-center">
            
            <div class="col-md-6 pe-lg-5 mb-4 mb-md-0">
                <div class="content-wrapper">
                    <span class="badge-premium mb-3">
                        <i class="fa-solid <?= htmlspecialchars($section['badge_icon'] ?? 'fa-crown') ?>"></i> 
                        <?= htmlspecialchars($section['badge'] ?? 'New Arrival') ?>
                    </span>

                    <h2 class="section-title mt-3">
                        <?= htmlspecialchars($section['title']) ?>
                    </h2>

                    <p class="section-description mt-3">
                        <?= htmlspecialchars($section['description']) ?>
                    </p>

                    <ul class="section-features mt-4">
                        <?php foreach(($section['features'] ?? []) as $feature): ?>
                            <li>
                                <span class="check-icon"><i class="fa-solid fa-check"></i></span>
                                <?= htmlspecialchars($feature) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="btn-premium mt-4">
                        View Products <i class="fa-solid fa-arrow-right-long ms-2"></i>
                    </a>
                </div>
                        </div>
            </div>

        </div>
    </div>
</section>
</body>
</html>
