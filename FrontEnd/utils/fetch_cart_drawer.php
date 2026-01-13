<?php
session_start();
include '../../BackEnd/config/dbconfig.php';

$customer_id = 1; // Or $_SESSION['customer_id']
$supplier_id = (int) $_GET['supplier_id'];

$query = "SELECT c.quantity, p.product_name, p.price, p.image, p.product_id, v.color, v.size 
          FROM cart c 
          JOIN product_variant v ON c.variant_id = v.variant_id 
          JOIN products p ON v.product_id = p.product_id 
          WHERE c.customer_id = ? AND c.supplier_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $customer_id, $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$count = mysqli_num_rows($result);

$html = "";
$total = 0;

while ($item = mysqli_fetch_assoc($result)) {
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
    // Determine the color value
    $colorValue = $item['color'];
    $html .= "
    <div class='cart-item'>
        <img src='../uploads/products/{$item['product_id']}_{$item['image']}' width='50'>
        <div>
            <h6>{$item['product_name']}</h6>
            <small>Qty: {$item['quantity']} | Size: {$item['size']}</small>
                <div class='selected-color-container'> 
                <small>Color: </small>
                <span class='color-preview' style='background-color: {$colorValue};'></span>
            </div>
            <p>$" . number_format($subtotal, 2) . "</p>
        </div>
    </div>";
}

$footer = "<h5>Total: $" . number_format($total, 2) . "</h5><button class='checkout-btn'>Checkout</button>";

echo json_encode(['html' => $html, 'footer' => $footer, 'count' => $count]);