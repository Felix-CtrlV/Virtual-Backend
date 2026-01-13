<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include __DIR__ . '/../../BackEnd/config/dbconfig.php';

header('Content-Type: application/json');

$supplier_id = $_GET['supplier_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? 1; 

$items = [];
$total = 0;

if ($customer_id && $supplier_id) {
    // UPDATED QUERY: Added v.color and v.size
    $query = "SELECT c.cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id, v.color, v.size
              FROM cart c 
              JOIN product_variant v ON c.variant_id = v.variant_id 
              JOIN products p ON v.product_id = p.product_id 
              WHERE c.customer_id = ? AND p.supplier_id = ?"; 
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $customer_id, $supplier_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $image_name = $row['product_id'] . "_" . $row['image'];
        $image_url = "../uploads/products/" . rawurlencode($image_name); 

        $items[] = [
            'cart_id'    => $row['cart_id'],
            'name'       => $row['product_name'],
            'price' => $row['price'], // Keep as number for math
            'qty'        => $row['quantity'],
            'image'      => $image_url,
            'color_code' => $row['color'], // Added this
            'size'       => $row['size']   // Added this
        ];
        $total += ($row['price'] * $row['quantity']);
    }
}

echo json_encode([
    'items'     => $items,
    'total'     => number_format($total, 2),
    'itemCount' => count($items)
]);