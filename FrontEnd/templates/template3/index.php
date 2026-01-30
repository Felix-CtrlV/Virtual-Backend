<?php
if (!isset($conn)) {
    include '../../../BackEnd/config/dbconfig.php';
}

$supplier_id = (int) $supplier['supplier_id'];

$company_stmt = mysqli_prepare($conn,'Select * from companies where supplier_id = ?');
$company_stmt->bind_param('i', $supplier_id);
$company_stmt->execute();
$company_result = $company_stmt->get_result();
$company_row = $company_result->fetch_assoc();
$company_id = $company_row['company_id'];

$assets_stmt = mysqli_prepare($conn, "SELECT * FROM shop_assets WHERE company_id = ?");
if ($assets_stmt) {
    mysqli_stmt_bind_param($assets_stmt, "i", $company_id);
    mysqli_stmt_execute($assets_stmt);
    $assets_result = mysqli_stmt_get_result($assets_stmt);
} else {
    $assets_result = false;
}

if ($assets_result && mysqli_num_rows($assets_result) > 0) {
    $shop_assets = mysqli_fetch_assoc($assets_result);
    if (isset($assets_stmt)) {
        mysqli_stmt_close($assets_stmt);
    }
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
$allowed_pages = ['home', 'about', 'collection', 'contact', 'review', 'product_details','cart'];
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

$page_path = __DIR__ . "/pages/$page.php";
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

<body class="body">
    <?php include(__DIR__ . '/partial/nav.php'); ?>

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