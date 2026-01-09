<?php
// Start the session at the very top to access login data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    include '../../../BackEnd/config/dbconfig.php';
}

$supplier_id = (int)$supplier['supplier_id'];

// --- (Your existing shop_assets logic) ---
$assets_stmt = mysqli_prepare($conn, "SELECT * FROM shop_assets WHERE supplier_id = ?");
if ($assets_stmt) {
    mysqli_stmt_bind_param($assets_stmt, "i", $supplier_id);
    mysqli_stmt_execute($assets_stmt);
    $assets_result = mysqli_stmt_get_result($assets_stmt);
} else {
    $assets_result = false;
}

if($assets_result && mysqli_num_rows($assets_result) > 0){
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

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowed_pages = ['home', 'about', 'products','productDetail','review', 'contact','cart'];
if(!in_array($page, $allowed_pages)){
    $page = 'home';
}

// ==========================================
// NEW CART COUNT LOGIC START
// ==========================================
function getCartCount($conn, $customer_id) {
    $count = 0;
    // We sum the 'quantity' column from your cart table for this specific user
    $stmt = mysqli_prepare($conn, "SELECT SUM(quantity) as total_items FROM cart WHERE customer_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $count = $row['total_items'] ? $row['total_items'] : 0;
    }
    mysqli_stmt_close($stmt);
    return $count;
}

$cart_count = 0;
if (isset($_SESSION['customer_id'])) {
    $cart_count = getCartCount($conn, $_SESSION['customer_id']);
}
// ==========================================
// NEW CART COUNT LOGIC END
// ==========================================

$page_path = __DIR__ . "/pages/$page.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($supplier['company_name']) ?></title>
    <link rel="stylesheet" href="../templates/<?= basename(__DIR__) ?>/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: <?= htmlspecialchars($shop_assets['primary_color']) ?>;
            --secondary: <?= htmlspecialchars($shop_assets['secondary_color']) ?>;
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/partial/nav.php'); ?>

    <main class="main-content">
        <?php
        if(file_exists($page_path)){
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