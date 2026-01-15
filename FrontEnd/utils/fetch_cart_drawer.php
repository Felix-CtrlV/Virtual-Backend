
<?php
session_start();
include '../../BackEnd/config/dbconfig.php';


$customer_id = $_SESSION['customer_id'] ?? 1; 
$supplier_id = (int)$_GET['supplier_id'];



$query = "SELECT c.cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id, v.color, v.size 
          FROM cart c 
          JOIN product_variant v ON c.variant_id = v.variant_id 
          JOIN products p ON v.product_id = p.product_id 
          WHERE c.customer_id = ? AND c.supplier_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $customer_id, $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$html = "";         
$drawer_html = "";  
$total = 0;
$total_quantity = 0; /*Shopping cart sum(KPS)*/


while ($item = mysqli_fetch_assoc($result)) {
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;

    $total_quantity += (int)$item['quantity'];/* Shopping cart sum(KPS)*/


    


     $html .= "
    <div class='cart-item'> 
        <img src='../uploads/products/{$item['product_id']}_{$item['image']}' width='50'>
        <div>
            <h6>{$item['product_name']}</h6>
            <small>Qty: {$item['quantity']} | Size: {$item['size']}</small>
            <p>$" . number_format($subtotal, 2) . "</p>
        </div>
    </div>";

       $drawer_html .= "
    <div class='cart-row mb-3 border-bottom pb-2' style='display: flex !important; align-items: center; justify-content: space-between; width: 100%; min-height: 70px; background: #fff;'>
    
    <div style='display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;'>
        <img src='../uploads/products/{$item['product_id']}_{$item['image']}' 
             style='width: 50px; height: 50px; object-fit: cover; border-radius: 4px; flex-shrink: 0; border: 1px solid #eee;'>
        
        <div style='min-width: 0; flex: 1;'>
            <h6 style='margin: 0; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #1a1a1a;'>
                {$item['product_name']}
            </h6>
            <small style='color: #666; font-size: 0.65rem; display: block;'>Qty: {$item['quantity']} | Size: {$item['size']}</small>
            <div style='font-weight: bold; font-size: 0.8rem; color: #bf953f; margin-top: 2px;'>$" . number_format($subtotal, 2) . "</div>

        </div>
    </div>

   <div style='flex-shrink: 0; margin-left: 10px; width: 30px; display: flex; justify-content: center; align-items: center;'>
    <button type='button' 
            class='remove-action-btn'
            style='border: none; background:white; color: #999; cursor: pointer; padding: 15px; transition: all 0.3s ease;'
            onclick='handleRemove({$item['cart_id']})'
            title='Remove Item'>
      <i class='fa-solid fa-trash' style='font-size: 24px;'></i>
    </button>
</div>
</div>";
}

// ... (Rest of the code)

$footer = "<h5>Total: $" . number_format($total, 2) . "</h5><button class='checkout-btn'>Checkout</button>";


echo json_encode([
    'html' => $html,
    'drawer_html' => $drawer_html,
    'footer' => $footer,
    'total' => $total,
    'total_count' => $total_quantity /*Shopping_cart sum(KPS)*/
]);
?>