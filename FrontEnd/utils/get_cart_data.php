<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../BackEnd/config/dbconfig.php';

header('Content-Type: application/json');

$supplier_id = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : null;
$customer_id = isset($_SESSION['customer_id']) ? (int) $_SESSION['customer_id'] : 1;

$items = [];
$total = 0;

if ($customer_id > 0 && $supplier_id > 0) {
    // Query using c.supplier_id to match the cart table schema
    $query = "SELECT c.cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id, v.color, v.size, v.quantity as available_stock
              FROM cart c 
              JOIN product_variant v ON c.variant_id = v.variant_id 
              JOIN products p ON v.product_id = p.product_id 
              WHERE c.customer_id = ? AND c.supplier_id = ?
              ORDER BY c.cart_id DESC"; 
    
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $supplier_id);
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