<?php
session_start();
header('Content-Type: application/json');
include '../../../../BackEnd/config/dbconfig.php';


$customer_id = (int) $_SESSION['customer_id'];
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 10; 

$query = "SELECT c.cart_id, c.quantity, p.product_id, p.product_name, p.price, p.image, v.size 
          FROM cart c 
          JOIN product_variant v ON c.variant_id = v.variant_id
          JOIN products p ON v.product_id = p.product_id
          WHERE c.customer_id = ? AND p.supplier_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $customer_id, $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$html = "";
$total_raw = 0;

if (mysqli_num_rows($result) > 0) {
    while ($item = mysqli_fetch_assoc($result)) {
        $item_price = (float)$item['price'];
        $item_qty = (int)$item['quantity'];
        $subtotal = $item_price * $item_qty;
        $total_raw += $subtotal;

        $p_name = htmlspecialchars($item['product_name']);
        
        $subtotal_fmt = number_format($subtotal, 2);
        $img_path = "../uploads/products/{$item['product_id']}_{$item['image']}";

     $html .= "
        <div style='display: flex; align-items: center; justify-content: space-between; 
                    background: #ffffff; padding: 15px; margin-bottom: 12px; 
                    border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
                    width: 100%; box-sizing: border-box;'>
            
            <div style='display: flex; align-items: center; flex: 1;'>
                <div style='width: 60px; height: 60px; border-radius: 50%; border: 2px solid #e0c060; overflow: hidden; margin-right: 15px; flex-shrink: 0; padding: 2px;'>
                    <img src='{$img_path}' style='width: 100%; height: 100%; object-fit: cover; border-radius: 50%;'>
                </div>
                
                <div style='flex: 1;'>
                    <h6 style='margin: 0; font-size: 1rem; color: #333; font-weight: 500;'>{$p_name}</h6>
                    <div style='color: #d9534f; font-weight: bold; font-size: 1rem; margin-top: 5px;'>\${$subtotal_fmt}</div>
                </div>
            </div>

            <button type='button' onclick='removeFromCart({$item['cart_id']})' 
                    style='background: transparent; border: none; cursor: pointer; padding: 5px; transition: transform 0.2s;'>
                
                <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512' width='22' height='22' fill='#d4a017'>
                    <path d='M135.2 17.7C140.6 6.8 151.7 0 163.8 0H284.2c12.1 0 23.2 6.8 28.6 17.7L320 32h96c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 96 0 81.7 0 64S14.3 32 32 32h96l7.2-14.3zM32 128H416V448c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V128zm96 64c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16z'/>
                </svg>

            </button>
        </div>";
    }
} else {
    $html = "<div class='py-4 text-center'><p class='text-muted small italic'>Your selection is currently empty.</p></div>";
}

echo json_encode([
    'html' => $html,
    'total_raw' => number_format($total_raw, 2, '.', '')
]);
?>