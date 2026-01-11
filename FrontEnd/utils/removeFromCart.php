<?php
session_start();
// Same path as add_to_cart.php
include '../../BackEnd/config/dbconfig.php'; 

// Use card_id to match your database screenshot
$stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id = ? AND customer_id = ?");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $cart_id = (int)$_POST['cart_id'];
    $customer_id = $_SESSION['customer_id'];

    // Delete only if the cart item belongs to the logged-in customer (Security check)
    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $customer_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Item removed']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove item']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>