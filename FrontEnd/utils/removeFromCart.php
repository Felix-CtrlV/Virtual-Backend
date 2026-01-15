<?php
session_start();
error_reporting(0);
require_once __DIR__ . '/../../BackEnd/config/dbconfig.php'; 

header('Content-Type: application/json');

// FALLBACK: If no one is logged in, we use ID 1 (or whatever your guest ID is)
$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $cart_id = (int)$_POST['cart_id'];
    
    if (!$conn) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit;
    }

    // Delete based on the cart_id and the current customer/guest ID
    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $customer_id);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Removed']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Item not found in your cart']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>