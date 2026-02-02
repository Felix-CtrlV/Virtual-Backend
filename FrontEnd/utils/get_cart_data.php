<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] <= 0) {
    echo json_encode([
        'status' => 'guest',
        'items' => []
    ]);
    exit;
}

include __DIR__ . '/../../BackEnd/config/dbconfig.php';

header('Content-Type: application/json');

$supplier_id = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : null;
$company_id = isset($_GET['company_id']) ? (int) $_GET['company_id'] : null;
$customer_id = isset($_SESSION['customer_id']) ? (int) $_SESSION['customer_id'] : 0;
$variant_id = isset($_GET['variant_id']) ? (int) $_GET['variant_id'] : null;

// Resolve company_id from supplier_id if needed (cart table uses company_id)
if ($company_id <= 0 && $supplier_id > 0) {
    $res = mysqli_query($conn, "SELECT company_id FROM companies WHERE supplier_id = $supplier_id LIMIT 1");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $company_id = (int) $row['company_id'];
    }
}

if ($variant_id) {
    $query = "SELECT quantity as available_stock FROM product_variant WHERE variant_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $variant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    echo json_encode([
        'availableStock' => $row ? (int)$row['available_stock'] : 0
    ]);
    exit;
}

$items = [];
$total = 0;

if ($customer_id > 0 && $company_id > 0) {
    $query = "SELECT c.cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id, v.color, v.size, v.quantity as available_stock
              FROM cart c 
              JOIN product_variant v ON c.variant_id = v.variant_id 
              JOIN products p ON v.product_id = p.product_id 
              WHERE c.customer_id = ? AND c.company_id = ?
              ORDER BY c.cart_id DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $company_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $image_name = $row['product_id'] . "_" . $row['image'];
            $image_url = "../uploads/products/" . rawurlencode($image_name); 

            $items[] = [
                'cart_id'    => (int) $row['cart_id'],
                'name'       => $row['product_name'],
                'price'      => (float) $row['price'],
                'qty'        => (int) $row['quantity'],
                'availableStock' => (int) $row['available_stock'],
                'image'      => $image_url,
                'color_code' => $row['color'] ?? '',
                'size'       => $row['size'] ?? '',
            ];
            $total += ($row['price'] * $row['quantity']);
        }
        
        mysqli_stmt_close($stmt);
    }
}

echo json_encode([
    'items'     => $items,
    'total'     => number_format($total, 2),
    'itemCount' => count($items)
], JSON_NUMERIC_CHECK);