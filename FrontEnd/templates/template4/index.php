<?php
if (!isset($conn)) {
    include '../../../BackEnd/config/dbconfig.php';
}

if (!isset($_GET['supplier_id'])) {
    die('Supplier ID missing');
}

$supplier_id = (int) $_GET['supplier_id'];

$supplier_stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM suppliers WHERE supplier_id = ? LIMIT 1"
);

mysqli_stmt_bind_param($supplier_stmt, "i", $supplier_id);
mysqli_stmt_execute($supplier_stmt);
$supplier_result = mysqli_stmt_get_result($supplier_stmt);

if (!$supplier_result || mysqli_num_rows($supplier_result) === 0) {
    die('Supplier not found');
}

$supplier = mysqli_fetch_assoc($supplier_result);
mysqli_stmt_close($supplier_stmt);

$assets_result = false;

$assets_stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM shop_assets WHERE supplier_id = ? LIMIT 1"
);

if ($assets_stmt) {
    mysqli_stmt_bind_param($assets_stmt, "i", $supplier_id);
    mysqli_stmt_execute($assets_stmt);
    $assets_result = mysqli_stmt_get_result($assets_stmt);
    mysqli_stmt_close($assets_stmt);
}

if ($assets_result && mysqli_num_rows($assets_result) > 0) {
    $shop_assets = mysqli_fetch_assoc($assets_result);
} else {
    $shop_assets = [
        'logo' => 'default_logo.png',
        'banner' => 'default_banner.jpg',
        'primary_color' => '#4a90e2',
        'secondary_color' => '#2c3e50'
    ];
}


$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowed_pages = ['home', 'about', 'products', 'contact'];
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

$page_path = __DIR__ . "/pages/$page.php";

// Prepare banner URL (use web-relative path from shop/index.php)
$banner_filename = isset($shop_assets['banner']) ? $shop_assets['banner'] : '';
$banner_fs = __DIR__ . '/../../uploads/shops/' . $supplier_id . '/' . $banner_filename;
if ($banner_filename && file_exists($banner_fs)) {
    $banner_url = '../uploads/shops/' . $supplier_id . '/' . rawurlencode($banner_filename);
} else {
    $banner_url = '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($supplier['company_name']) ?></title>
    <link rel="stylesheet" href="../templates/<?= basename(__DIR__) ?>/style.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: <?= htmlspecialchars($shop_assets['primary_color']) ?>;
            --secondary: <?= htmlspecialchars($shop_assets['secondary_color']) ?>;
            /* --text-color: red; */
        }
    </style>
</head>

<body>

    <body>

        <?php include(__DIR__ . '/partial/header.php'); ?>



        <!-- Hero banner inserted here -->
        <?php if ($banner_url): ?>
            <section class="hero-banner" style="background-image: url('<?= htmlspecialchars($banner_url) ?>')">
            <?php else: ?>
                <section class="hero-banner hero-no-image">
                <?php endif; ?>

                <div class="hero-overlay"></div>

                <div class="container hero-container">
                    <div class="hero-side-left">
                        <h1 class="hero-title"><?= htmlspecialchars($supplier['company_name']) ?></h1>
                        <p class="hero-sub"><?= htmlspecialchars($supplier['tagline'] ?? 'Quality products') ?></p>
                        <div class="hero-btns">
                            <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="hero-cta">Explore Shop</a>
                        </div>
                    </div>

                    <div class="hero-side-right d-none d-md-flex">
                        <div class="hero-floating-card">
                            <span>Established Quality</span>
                            <small>Premium Supplier</small>
                        </div>
                    </div>
                </div>

                <div class="hero-curve">
                    <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                        <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V0C49.1,24.2,105.86,48.35,159.27,62.13,212.68,75.91,263.39,67.23,321.39,56.44Z" class="shape-fill"></path>
                    </svg>
                </div>
                </section>

                <main class="main-content">
                    <?php
                    if (file_exists($page_path)) {
                        include($page_path);
                    } else {
                        echo "<p class='not-found'>Page not found.</p>";
                    }
                    ?>
                </main>

                <?php include(__DIR__ . '/partial/footer.php'); ?>

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script src="script.js"></script>

    </body>

</body>

</html>