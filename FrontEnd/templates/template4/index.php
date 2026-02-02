<?php
if (!isset($conn)) {
    include '../../../BackEnd/config/dbconfig.php';
}

if (!isset($_GET['supplier_id'])) {
    die('Supplier ID missing');
}

$supplier_id = (int) $_GET['supplier_id'];

$company_stmt = mysqli_prepare($conn, "Select * from companies where supplier_id = ?");
mysqli_stmt_bind_param($company_stmt, "i", $supplier_id);
mysqli_stmt_execute($company_stmt);
$company_result = mysqli_stmt_get_result($company_stmt);
$company_row = mysqli_fetch_assoc($company_result);
$company_id = $company_row['company_id'];

$supplier_stmt = mysqli_prepare(
    $conn,
    "SELECT 
    s.*,
    c.*
FROM suppliers s
JOIN companies c ON s.supplier_id = c.supplier_id
WHERE s.supplier_id = ?
LIMIT 1
"
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
    "SELECT * FROM shop_assets WHERE company_id = ? LIMIT 1"
);

if ($assets_stmt) {
    mysqli_stmt_bind_param($assets_stmt, "i", $company_id);
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

$banner_string = $shop_assets["banner"];
$banners = explode(",", $banner_string);
$banner_count = count($banners);

for ($i = 0; $i < $banner_count; $i++) {
    ${"banner" . ($i + 1)} = $banners[$i];
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowed_pages = ['home', 'about', 'products', 'contact', 'review', 'productdetail', 'cart'];
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

    <?php include(__DIR__ . '/partial/header.php'); ?>



    <!-- Hero banner inserted here -->


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
    <script src="../templates/template4/script.js"></script>

</body>


</html>