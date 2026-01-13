<?php
session_start();
include '../../BackEnd/config/dbconfig.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $cart_id = (int)$_POST['cart_id'];
    $supplier_id = (int)$_POST['supplier_id'];
    $customer_id = $_SESSION['customer_id'] ?? 1;

    
    $query = "DELETE FROM cart WHERE cart_id = ? AND supplier_id = ? AND customer_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $cart_id, $supplier_id, $customer_id);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delete Not Found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>