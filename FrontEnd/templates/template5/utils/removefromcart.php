<?php
session_start();
include '../../BackEnd/config/dbconfig.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $cart_id = (int)$_POST['cart_id'];
    $customer_id = $_SESSION['customer_id'] ?? 1; // session မရှိရင် default 1 ထားခြင်း

    // Database structure အရ cart_id နှင့် customer_id ကို သုံး၍ ဖျက်ပါ
    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $customer_id);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Item deleted from database']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No item found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>