<?php
$isPreview = !isset($supplier);

if ($isPreview) {
    $supplier_id = 0;
    $supplier = [
        'company_name' => 'Your Shop Name',
        'about_headline' => 'Welcome to our store'
    ];
    $shop_assets = [
        'logo' => '',
        'banner' => '',
        'primary_color' => '#7d6de3',
        'secondary_color' => '#ff00e6'
    ];
} else {
    if (!isset($conn)) {
        include '../../../BackEnd/config/dbconfig.php';
    }

    $supplier_id = (int) $supplier['supplier_id'];
    $assets_stmt = mysqli_prepare($conn, "SELECT * FROM shop_assets WHERE supplier_id = ?");

    if ($assets_stmt) {
        mysqli_stmt_bind_param($assets_stmt, "i", $supplier_id);
        mysqli_stmt_execute($assets_stmt);
        $assets_result = mysqli_stmt_get_result($assets_stmt);

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
        mysqli_stmt_close($assets_stmt);
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowed_pages = ['home', 'about', 'products', 'contact'];
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

$page_path = __DIR__ . "/pages/$page.php";

$banner_string = $shop_assets["banner"];
$banners = explode(",", $banner_string);
$banner_count = count($banners);

for ($i = 0; $i < $banner_count; $i++) {
    ${"banner" . ($i + 1)} = $banners[$i];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($supplier['company_name']) ?></title>
    <link rel="stylesheet" href="../templates/<?= basename(__DIR__) ?>/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary:
                <?= htmlspecialchars($shop_assets['primary_color']) ?>
            ;
            --secondary:
                <?= htmlspecialchars($shop_assets['secondary_color']) ?>
            ;
            /* --text-color: red; */
        }
    </style>
</head>

<body>
    <?php include(__DIR__ . '/partial/header.php'); ?>

    <?php include(__DIR__ . '/partial/nav.php'); ?>

    <section class="banner-section">
        <div class="banner-overlay">
            <div class="container">
                <h1 class="banner-title"><?= htmlspecialchars($supplier['company_name']) ?></h1>
                <p class="banner-subtitle">Welcome to our store</p>
            </div>
        </div>
        <?php if (!empty($shop_assets['banner'])): ?>

            <?php if ($shop_assets['template_type'] == 'video'): ?>
                <video class="banner-image" autoplay muted loop playsinline
                    src="../uploads/shops/<?= $supplier_id ?>/<?= $shop_assets['banner'] ?>"></video>

            <?php else: ?>
                <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>" alt="Hero Banner" class="banner-image"
                    style="transform: scale(1.1);">
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <main class="main-content">
        <?php
        if (file_exists($page_path)) {
            include($page_path);
        } else {
            echo "<div class='container'><p>Page not found.</p></div>";
        }
        ?>
    </main>

    <?php include(__DIR__ . '/partial/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../templates/<?= basename(__DIR__) ?>/script.js"></script>
</body>

</html>