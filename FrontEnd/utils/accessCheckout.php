<?php
include '../../BackEnd/config/dbconfig.php';

$customer_id = $_SESSION['customer_id'] ?? 1;

$supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;

if ($supplier_id > 0) {

    $sup_query = "SELECT company_name FROM suppliers WHERE supplier_id = ?";
    $sup_stmt = mysqli_prepare($conn, $sup_query);
    mysqli_stmt_bind_param($sup_stmt, "i", $supplier_id);
    mysqli_stmt_execute($sup_stmt);
    $sup_result = mysqli_stmt_get_result($sup_stmt);
    $supplier_row = mysqli_fetch_assoc($sup_result);
    
    $my_store_name = $supplier_row['company_name'] ?? "Malltiverse Store";

    $price_query = "SELECT SUM(p.price * c.quantity) as grand_total 
                    FROM cart c 
                    JOIN product_variant v ON c.variant_id = v.variant_id 
                    JOIN products p ON v.product_id = p.product_id 
                    WHERE c.customer_id = ? AND c.supplier_id = ?";
    
    $price_stmt = mysqli_prepare($conn, $price_query);
    mysqli_stmt_bind_param($price_stmt, "ii", $customer_id, $supplier_id);
    mysqli_stmt_execute($price_stmt);
    $price_result = mysqli_stmt_get_result($price_stmt);
    $price_data = mysqli_fetch_assoc($price_result);
    
    $total_price = $price_data['grand_total'] ?? 0.00;

    $callback_url = $_SERVER['HTTP_REFERER'] ?? "http://localhost/malltiverse/FrontEnd/shop/?supplier_id=$supplier_id";

    $bank_url = "https://crediverse.base44.app/payment?" . http_build_query([
        'amount'     => $total_price,
        'merchant'   => $my_store_name,
        'return_url' => $callback_url
    ]);

    header("Location: " . $bank_url);
    exit();

} else {
    
    die("Error: Invalid Supplier ID.");
}
?>