<?php
session_start();

if (!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'You must login first']);
    exit;
}

$customer_id = (int) $_SESSION['customer_id'];

header('Content-Type: application/json');

include '../../BackEnd/config/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

if (!isset($_POST['variant_id'], $_POST['supplier_id'], $_POST['quantity'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

$customer_id = $_SESSION['customer_id'] ?? 1; 

$variant_id = (int) $_POST['variant_id'];
$supplier_id = (int) $_POST['supplier_id'];
$quantity_to_add = (int) $_POST['quantity'];

$stock_query = "SELECT quantity FROM product_variant WHERE variant_id = ?";
$stock_stmt = mysqli_prepare($conn, $stock_query);
mysqli_stmt_bind_param($stock_stmt, "i", $variant_id);
mysqli_stmt_execute($stock_stmt);
$stock_result = mysqli_stmt_get_result($stock_stmt);
$stock_data = mysqli_fetch_assoc($stock_result);

if (!$stock_data) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}

$db_stock = (int)$stock_data['quantity'];

$cart_check_query = "SELECT cart_id, quantity FROM cart WHERE customer_id = ? AND variant_id = ?";
$cart_check_stmt = mysqli_prepare($conn, $cart_check_query);
mysqli_stmt_bind_param($cart_check_stmt, "ii", $customer_id, $variant_id);
mysqli_stmt_execute($cart_check_stmt);
$cart_result = mysqli_stmt_get_result($cart_check_stmt);
$cart_data = mysqli_fetch_assoc($cart_result);

$in_cart_qty = $cart_data ? (int)$cart_data['quantity'] : 0; 

$total_requested = $quantity_to_add + $in_cart_qty;

if ($db_stock >= $total_requested) {
    if ($cart_data) {
        $update_stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE cart_id = ?");
        mysqli_stmt_bind_param($update_stmt, "ii", $total_requested, $cart_data['cart_id']);
        $success = mysqli_stmt_execute($update_stmt);
    } else {
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO cart (customer_id, supplier_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "iiii", $customer_id, $supplier_id, $variant_id, $quantity_to_add);
        $success = mysqli_stmt_execute($insert_stmt);
    }

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Item added to cart!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }

} else {
    $can_add = $db_stock - $in_cart_qty;
    $msg = "Your item is not enough! ";
    $msg .= "Stock only has $db_stock items. ";
    $msg .= "You already have $in_cart_qty in cart. ";
    
    if ($can_add > 0) {
        $msg .= "You can only add $can_add more.";
    } else {
        $msg .= "You cannot add any more of this item.";
    }

    echo json_encode([
        'status' => 'error', 
        'message' => $msg
    ]);
}
?>