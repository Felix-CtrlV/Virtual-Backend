<?php
include("../../BackEnd/config/dbconfig.php");

if (!isset($_SESSION["supplier_logged_in"])) {
    header("Location: ../supplierLogin.php");
    exit();
}

$supplierid = $_SESSION["supplierid"];
$stmt = $conn->prepare("SELECT suppliers.*,
shop_assets.*, products.product_id, products.product_name, products.price,
product_variant.variant_id, product_variant.size, product_variant.color, product_variant.quantity
FROM suppliers LEFT JOIN shop_assets ON suppliers.supplier_id = shop_assets.supplier_id
LEFT JOIN products ON suppliers.supplier_id = products.supplier_id
LEFT JOIN product_variant ON products.product_id = product_variant.product_id WHERE suppliers.supplier_id = ?");

$stmt->bind_param("i", $supplierid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// .............................................................................................................................

$countproduct = $conn->prepare("SELECT COUNT(*) AS product_count FROM products WHERE supplier_id = ?");
$countproduct->bind_param("i", $supplierid);
$countproduct->execute();
$result = $countproduct->get_result();
$rowCount = $result->fetch_assoc();
$countproduct->close();
$productCount = $rowCount['product_count'];

// .............................................................................................................................

$orderCountStmt = $conn->prepare("SELECT SUM(order_status = 'pending')   AS pending_count, SUM(order_status = 'cancelled') AS cancelled_count FROM orders WHERE supplier_id = ?");
$orderCountStmt->bind_param("i", $supplierid);
$orderCountStmt->execute();

$orderCounts = $orderCountStmt->get_result()->fetch_assoc();
$orderCountStmt->close();

$pendingOrders = $orderCounts['pending_count'] ?? 0;
$cancelledOrders = $orderCounts['cancelled_count'] ?? 0;

// .............................................................................................................................

$contractStmt = $conn->prepare('SELECT  rp.paid_date AS contract_start, rp.due_date  AS contract_end FROM rent_payments rp INNER JOIN (
SELECT supplier_id, MAX(paid_date) AS latest_paid FROM rent_payments WHERE supplier_id = ? GROUP BY supplier_id) latest ON rp.supplier_id = latest.supplier_id
AND rp.paid_date = latest.latest_paid;');
$contractStmt->bind_param('i', $supplierid);
$contractStmt->execute();
$contractRow = $contractStmt->get_result()->fetch_assoc();
$contractStmt->close();

$start = new DateTime($contractRow['contract_start']);
$end = new DateTime($contractRow['contract_end']);
$today = new DateTime();

$totalDays = $start->diff($end)->days;

$daysPassed = $start->diff($today)->days;
$diffObj = $end->diff($today);
$diff = $diffObj->days;
if (!$diffObj->invert) {
    $diff = -$diff;
}

if ($totalDays > 0) {
    $percent = ($daysPassed / $totalDays) * 100;
} else {
    $percent = 0;
}

$percent = max(0, min(100, $percent));
$percent = round($percent);
// .............................................................................................................................

$totalrevenuestmt = $conn->prepare("SELECT DAY(order_date) AS day, SUM(price) AS daily_revenue, COUNT(*) OVER () AS total_orders_month,
SUM(SUM(price)) OVER () AS total_revenue_month FROM orders WHERE supplier_id = ? AND order_status = 'confirm'
AND order_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND order_date < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH) GROUP BY DAY(order_date)
ORDER BY day ASC;");
$totalrevenuestmt->bind_param("i", $supplierid);
$totalrevenuestmt->execute();
$totalrevenueRow = $totalrevenuestmt->get_result()->fetch_assoc();
$totalrevenuestmt->close();

$totalRevenue = $totalrevenueRow['total_revenue_month'] ?? 0;
$totalOrder = $totalrevenueRow['total_orders_month'] ?? 0;

// .............................................................................................................................

$bestsellerstmt = $conn->prepare("SELECT p.product_id, p.product_name, SUM(oi.quantity) AS total_sold FROM orders o JOIN order_detail oi ON o.order_id = oi.order_id
JOIN products p ON p.product_id = oi.product_id WHERE o.supplier_id = ? AND o.order_status = 'confirm' AND o.order_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND o.order_date < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'),
INTERVAL 1 MONTH) GROUP BY p.product_id, p.product_name ORDER BY total_sold DESC LIMIT 5;");
$bestsellers = [];
if ($bestsellerstmt) {
    $bestsellerstmt->bind_param("i", $supplierid);
    $bestsellerstmt->execute();
    $result = $bestsellerstmt->get_result();
    if ($result) {
        $bestsellers = $result->fetch_all(MYSQLI_ASSOC);
    }
    $bestsellerstmt->close();
}

// .............................................................................................................................
$current_page = basename($_SERVER["PHP_SELF"]);
if (!isset($active)) {
    $active = '';
    if ($current_page === "dashboard.php")
        $active = "dashboard";
    elseif ($current_page === "monthlypayment.php")
        $active = "monthlypayment";
    elseif ($current_page === "setting.php")
        $active = "profile";
    elseif ($current_page === "inventory.php")
        $active = "inventory";
}


$supplierName = $row['company_name'];

$inventory = [
    ['day' => 'Mon', 'level' => 40],
    ['day' => 'Tue', 'level' => 65],
    ['day' => 'Wed', 'level' => 80],
    ['day' => 'Thu', 'level' => 30],
    ['day' => 'Fri', 'level' => 90],
    ['day' => 'Sat', 'level' => 30],
    ['day' => 'Sun', 'level' => 90],

];

$orders = [
    ['id' => '#ORD-001', 'item' => 'Gaming Mouse', 'status' => 'Pending', 'time' => '10:30 AM'],
    ['id' => '#ORD-002', 'item' => 'Mech Keyboard', 'status' => 'Pending', 'time' => '11:15 AM'],
    ['id' => '#ORD-003', 'item' => 'USB-C Hub', 'status' => 'Approved', 'time' => '09:00 AM'],
    ['id' => '#ORD-004', 'item' => 'Monitor 24"', 'status' => 'Pending', 'time' => '01:20 PM'],
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
            --primary:
                <?= htmlspecialchars($row['primary_color']) ?>
            ;
            --secondary:
                <?= htmlspecialchars($row['secondary_color']) ?>
        }
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
                <a href="#" class="active">Dashboard</a>
                <a href="#">Inventory</a>
                <a href="#">Rent Payment</a>
                <a href="#">Settings</a>
            </nav>
            <button class="btn-logout" onclick="window.location.href='../utils/signout.php'">Logout</button>
        </div>
    </header>
    <div class="container">