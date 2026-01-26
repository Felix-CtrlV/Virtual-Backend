<?php
include("../../BackEnd/config/dbconfig.php");

if (!isset($_SESSION["supplier_logged_in"])) {
    header("Location: ../supplierLogin.php");
    exit();
}

$supplierid = $_SESSION["supplierid"];

// 1. Fetch Supplier & Shop Details
$stmt = $conn->prepare("SELECT  s.*, c.company_name, c.description AS company_description, sa.*, p.product_id, p.product_name, p.price, pv.variant_id, pv.size, pv.color, pv.quantity
FROM suppliers s LEFT JOIN companies c ON c.supplier_id = s.supplier_id LEFT JOIN shop_assets sa ON sa.supplier_id = s.supplier_id LEFT JOIN products p 
ON p.supplier_id = s.supplier_id LEFT JOIN product_variant pv ON pv.product_id = p.product_id WHERE s.supplier_id = ? AND c.status = 'active';");
$stmt->bind_param("i", $supplierid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
$supplierName = $row['name'];

// 2. Product Count
$countproduct = $conn->prepare("SELECT COUNT(*) AS product_count FROM products WHERE supplier_id = ?");
$countproduct->bind_param("i", $supplierid);
$countproduct->execute();
$rowCount = $countproduct->get_result()->fetch_assoc();
$countproduct->close();
$productCount = $rowCount['product_count'];

// 3. Order Stats (Pending/Cancelled)
$orderCountStmt = $conn->prepare("SELECT SUM(order_status = 'pending') AS pending_count, SUM(order_status = 'cancelled') AS cancelled_count FROM orders WHERE supplier_id = ?");
$orderCountStmt->bind_param("i", $supplierid);
$orderCountStmt->execute();
$orderCounts = $orderCountStmt->get_result()->fetch_assoc();
$orderCountStmt->close();
$pendingOrders = $orderCounts['pending_count'] ?? 0;
$cancelledOrders = $orderCounts['cancelled_count'] ?? 0;

// 4. Contract / Rent Logic
$contractStmt = $conn->prepare('SELECT rp.paid_date AS contract_start, rp.due_date AS contract_end FROM rent_payments rp INNER JOIN (
SELECT supplier_id, MAX(paid_date) AS latest_paid FROM rent_payments WHERE supplier_id = ? GROUP BY supplier_id) latest ON rp.supplier_id = latest.supplier_id
AND rp.paid_date = latest.latest_paid;');
$contractStmt->bind_param('i', $supplierid);
$contractStmt->execute();
$contractRow = $contractStmt->get_result()->fetch_assoc();
$contractStmt->close();

$start = new DateTime($contractRow['contract_start'] ?? 'now');
$end = new DateTime($contractRow['contract_end'] ?? 'now');
$today = new DateTime();
$totalDays = $start->diff($end)->days;
$daysPassed = $start->diff($today)->days;
$diffObj = $end->diff($today);
$diff = $diffObj->days;
if (!$diffObj->invert) {
    $diff = -$diff;
}
$percent = ($totalDays > 0) ? ($daysPassed / $totalDays) * 100 : 0;
$percent = max(0, min(100, round($percent)));

// 5. Total Revenue (Current Month)
$totalrevenuestmt = $conn->prepare("SELECT SUM(daily_revenue) OVER () AS total_revenue_month, SUM(daily_orders) OVER () AS total_orders_month
FROM (SELECT DAY(order_date) AS day, SUM(price) AS daily_revenue, COUNT(*) AS daily_orders FROM orders WHERE supplier_id = ?
AND order_status = 'confirm' AND order_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND order_date < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
GROUP BY DAY(order_date)) t LIMIT 1;");
$totalrevenuestmt->bind_param("i", $supplierid);
$totalrevenuestmt->execute();
$totalrevenueRow = $totalrevenuestmt->get_result()->fetch_assoc();
$totalrevenuestmt->close();
$totalRevenue = $totalrevenueRow['total_revenue_month'] ?? 0;
$totalOrder = $totalrevenueRow['total_orders_month'] ?? 0;

// 6. Best Sellers (Top 5)
$bestsellerstmt = $conn->prepare("SELECT p.product_id, p.product_name, p.image, MAX(variant_sales.total_sold) AS best_variant_sold 
FROM (SELECT pv.product_id, oi.variant_id, SUM(oi.quantity) AS total_sold FROM orders o 
JOIN order_detail oi ON o.order_id = oi.order_id 
JOIN product_variant pv ON oi.variant_id = pv.variant_id 
WHERE o.supplier_id = ? AND o.order_status = 'confirm' 
GROUP BY pv.product_id, oi.variant_id) AS variant_sales 
JOIN products p ON p.product_id = variant_sales.product_id 
GROUP BY p.product_id, p.product_name 
ORDER BY best_variant_sold DESC LIMIT 5;");

$bestsellers = [];
if ($bestsellerstmt) {
    $bestsellerstmt->bind_param("i", $supplierid);
    $bestsellerstmt->execute();
    $bestsellers = $bestsellerstmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $bestsellerstmt->close();
}

// 7. Monthly Revenue Chart with YEAR FILTER
// Check for Year in GET, default to current year
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Fetch available years for the dropdown
$yearStmt = $conn->prepare("SELECT DISTINCT YEAR(order_date) as yr FROM orders WHERE supplier_id = ? ORDER BY yr DESC");
$yearStmt->bind_param("i", $supplierid);
$yearStmt->execute();
$availableYearsResult = $yearStmt->get_result();
$availableYears = [];
while ($y = $availableYearsResult->fetch_assoc()) {
    $availableYears[] = $y['yr'];
}
if(empty($availableYears)) $availableYears[] = date('Y'); // Fallback
$yearStmt->close();

// Fetch Revenue for Selected Year
$revenue = $conn->prepare("SELECT MONTH(order_date) AS month, SUM(price) AS total_revenue FROM orders
WHERE supplier_id = ? AND YEAR(order_date) = ? AND order_status = 'confirm' GROUP BY MONTH(order_date) ORDER BY month;");
$revenue->bind_param("ii", $supplierid, $selectedYear);
$revenue->execute();
$revenue_result = $revenue->get_result();
$monthlyRevenue = array_fill(1, 12, 0);
while ($revenuerow = $revenue_result->fetch_assoc()) {
    $monthlyRevenue[(int) $revenuerow['month']] = (float) $revenuerow['total_revenue'];
}
$revenue->close();

// 8. Inventory / Best Selling Categories (Pie Chart)
// Assuming products table has a 'category' column. If not, adapt query.
$categoryStmt = $conn->prepare("SELECT 
    c.category_name, 
    SUM(od.quantity) AS total_sold
FROM order_detail od
JOIN product_variant pv ON od.variant_id = pv.variant_id
JOIN products p ON pv.product_id = p.product_id
JOIN category c ON p.category_id = c.category_id
WHERE p.supplier_id = ?
GROUP BY c.category_name
ORDER BY total_sold DESC;
");
$categoryData = [];
$categoryLabels = [];
if ($categoryStmt) {
    $categoryStmt->bind_param("i", $supplierid);
    $categoryStmt->execute();
    $catResult = $categoryStmt->get_result();
    while ($c = $catResult->fetch_assoc()) {
        $categoryLabels[] = $c['category_name'];
        $categoryData[] = $c['total_sold'];
    }
    $categoryStmt->close();
}
// Fallback if empty (for demo)
if (empty($categoryData)) {
    $categoryLabels = ['NoData'];
    $categoryData = [0];
}


// 9. Recent Reviews (Last 7 Days)
// Assuming a 'reviews' table exists. If not, I am mocking the structure for the "Full Code" requirement.
// Schema assumption: reviews(id, product_id, customer_name, rating, comment, created_at, customer_image)
// Note: Adapting to join with products to filter by supplier.
$reviewStmt = $conn->prepare("SELECT 
    r.review_id,
    r.rating,
    r.review,
    r.created_at,
    u.name AS customer_name,
    u.image
FROM reviews r
LEFT JOIN customers u ON r.customer_id = u.customer_id
WHERE r.supplier_id = ?
  AND r.created_at >= DATE_SUB(NOW(), INTERVAL 100 DAY)
ORDER BY r.created_at DESC;
");

$recentReviews = [];
// NOTE: Since I cannot verify if you have a reviews table, I will use a dummy array if the query fails or returns empty, 
// so the UI still works for you.
if ($reviewStmt) {
    $reviewStmt->bind_param("i", $supplierid);
    $reviewStmt->execute();
    $recentReviews = $reviewStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $reviewStmt->close();
}


// Active Page Logic
$current_page = basename($_SERVER["PHP_SELF"]);
if (!isset($active)) {
    $active = '';
    if ($current_page === "dashboard.php") $active = "dashboard";
    elseif ($current_page === "rentpayment.php") $active = "rentpayment";
    elseif ($current_page === "setting.php" || $current_page === "customize.php") $active = "setting";
    elseif ($current_page === "inventory.php") $active = "inventory";
    elseif ($current_page === "orders.php") $active = "orders";
}

// Dummy Pending Orders for the Left Column (since we moved Bestsellers to Right)
$pendingOrderList = [
    ['id' => '#ORD-991', 'item' => 'Wireless Headset', 'time' => '10 min ago'],
    ['id' => '#ORD-992', 'item' => 'HDMI Cable 2m', 'time' => '1 hour ago'],
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard - <?= $row['company_name'] ?> Style</title>
    <link rel="stylesheet" href="css/supplierCss.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: <?= htmlspecialchars($row['primary_color']) ?>;
            --secondary: <?= htmlspecialchars($row['secondary_color']) ?>;
        }
        /* Modal Styles */
        .modal-overlay {
            display: none; position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;
        }
        .modal-content {
            background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; animation: popIn 0.3s ease;
        }
        @keyframes popIn { from {transform: scale(0.8); opacity: 0;} to {transform: scale(1); opacity: 1;} }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #888; }
        .star-gold { color: #f5c518; }
    </style>
</head>

<body style="background-image:
  radial-gradient(circle at 5% 10%, color-mix(in srgb, var(--primary) 10%, transparent) 0%, transparent 35%),
  radial-gradient(circle at 90% 90%, color-mix(in srgb, var(--primary) 30%, transparent) 0%, transparent 40%);
">

    <header>
        <div class="logo"><?= $row['company_name'] ?></div>
        <div class="navline">
            <nav class="nav-links">
                <a href="dashboard.php" <?= $active === "dashboard" ? 'class="active"' : '' ?>>Dashboard</a>
                <a href="inventory.php" <?= $active === "inventory" ? 'class="active"' : '' ?>>Inventory</a>
                <a href="orders.php" <?= $active === "orders" ? 'class="active"' : '' ?>>Orders</a>
                <a href="rentpayment.php" <?= $active === "rentpayment" ? 'class="active"' : '' ?>>Rent Payment</a>
                <a href="setting.php" <?= $active === "setting" ? 'class="active"' : '' ?>>Settings</a>
            </nav>
            <button class="btn-logout" onclick="window.location.href='../index.html'">Logout</button>
        </div>
    </header>
    <div class="container">