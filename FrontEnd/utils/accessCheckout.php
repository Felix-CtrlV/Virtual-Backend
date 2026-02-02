<?php
session_start();
include '../../BackEnd/config/dbconfig.php';

// Use session ID for testing with different laptops
$customer_id = $_SESSION['customer_id'] ?? 1;
$supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
$company_id = 0;
if ($supplier_id > 0) {
    $cr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_id FROM companies WHERE supplier_id = $supplier_id LIMIT 1"));
    $company_id = $cr ? (int)$cr['company_id'] : 0;
}

if ($company_id > 0) {
    $check_stock_query = "SELECT p.product_name, c.quantity AS cart_qty, v.quantity AS actual_stock 
                          FROM cart c 
                          JOIN product_variant v ON c.variant_id = v.variant_id 
                          JOIN products p ON v.product_id = p.product_id 
                          WHERE c.customer_id = ? AND c.company_id = ?";

    $stock_stmt = mysqli_prepare($conn, $check_stock_query);
    mysqli_stmt_bind_param($stock_stmt, "ii", $customer_id, $company_id);
    mysqli_stmt_execute($stock_stmt);
    $stock_result = mysqli_stmt_get_result($stock_stmt);

    $out_of_stock_items = [];
    while ($row = mysqli_fetch_assoc($stock_result)) {
        // If a customer on another laptop bought the items and reduced the DB stock:
        if ($row['cart_qty'] > $row['actual_stock']) {
            $out_of_stock_items[] = $row['product_name'] . " (Only " . $row['actual_stock'] . " left)";
        }
    }

    // If stock is insufficient, block checkout and redirect to cart with error
    $callback_url = $_SERVER['HTTP_REFERER'] ?? "http://localhost/malltiverse/FrontEnd/shop/?supplier_id=$supplier_id";
    if (!empty($out_of_stock_items)) {
        $error_msg = "Stock is no longer sufficient for: " . implode(', ', $out_of_stock_items);

        // Redirect back to cart page with the error parameter
        header("Location: " . $callback_url . "&error=" . urlencode($error_msg));
        exit();
    }

    // 2. PAYMENT PHASE (Only reached if stock check passes)
    $sup_query = "SELECT 
    s.*,
    c.*
FROM suppliers s
JOIN companies c ON s.supplier_id = c.supplier_id
WHERE s.supplier_id = ?
";
    $sup_stmt = mysqli_prepare($conn, $sup_query);
    mysqli_stmt_bind_param($sup_stmt, "i", $supplier_id);
    mysqli_stmt_execute($sup_stmt);
    $sup_result = mysqli_stmt_get_result($sup_stmt);
    $supplier_row = mysqli_fetch_assoc($sup_result);

    $my_store_name = $supplier_row['company_name'] ?? "Malltiverse Store";
    $account_number = $supplier_row["account_number"] ?? "";

    $price_query = "SELECT SUM(p.price * c.quantity) as grand_total 
                    FROM cart c 
                    JOIN product_variant v ON c.variant_id = v.variant_id 
                    JOIN products p ON v.product_id = p.product_id 
                    WHERE c.customer_id = ? AND c.company_id = ?";

    $price_stmt = mysqli_prepare($conn, $price_query);
    mysqli_stmt_bind_param($price_stmt, "ii", $customer_id, $company_id);
    mysqli_stmt_execute($price_stmt);
    $price_result = mysqli_stmt_get_result($price_stmt);
    $price_data = mysqli_fetch_assoc($price_result);

    $total_price = $price_data['grand_total'] ?? 0.00;

    $bank_url = "https://crediverse.base44.app/payment?" . http_build_query([
        'amount' => $total_price,
        'merchant' => $my_store_name,
        'return_url' => $callback_url,
        'account_number' => $account_number
    ]);

    header("Location: " . $bank_url);
    exit();
} else {
    die("Error: Invalid Supplier ID or Company not found.");
}
?>