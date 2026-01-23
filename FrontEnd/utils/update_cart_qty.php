<?php
session_start();
include '../../BackEnd/config/dbconfig.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];

    if ($cart_id > 0 && $quantity > 0) {
        
        // 1. FIRST CHECK: Does the database actually have enough stock?
        $stock_check_query = "SELECT v.quantity AS available_stock, p.product_name 
                              FROM cart c 
                              JOIN product_variant v ON c.variant_id = v.variant_id 
                              JOIN products p ON v.product_id = p.product_id
                              WHERE c.cart_id = ?";
        
        $stock_stmt = mysqli_prepare($conn, $stock_check_query);
        mysqli_stmt_bind_param($stock_stmt, "i", $cart_id);
        mysqli_stmt_execute($stock_stmt);
        $stock_result = mysqli_stmt_get_result($stock_stmt);
        $stock_data = mysqli_fetch_assoc($stock_result);

        if ($stock_data) {
            if ($quantity > $stock_data['available_stock']) {
                // If the user tries to update to 10 but database only has 5
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Only ' . $stock_data['available_stock'] . ' units of ' . $stock_data['product_name'] . ' are available.'
                ]);
                exit();
            }

            // 2. SECOND STEP: If stock is okay, proceed with the update
            $query = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $quantity, $cart_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Item not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
}
?>