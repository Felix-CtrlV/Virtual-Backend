<?php
session_start();
error_reporting(0);
require_once __DIR__ . '/../../BackEnd/config/dbconfig.php'; 

header('Content-Type: application/json');

$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $cart_id = (int)$_POST['cart_id'];
    
    // 🔥 Undo အတွက် ပစ္စည်းအချက်အလက်ကို အရင်ယူထားမယ်
    $get_stmt = mysqli_prepare($conn, "SELECT variant_id, quantity FROM cart WHERE cart_id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($get_stmt, "ii", $cart_id, $customer_id);
    mysqli_stmt_execute($get_stmt);
    $item_info = mysqli_fetch_assoc(mysqli_stmt_get_result($get_stmt));
    mysqli_stmt_close($get_stmt);

    // ပစ္စည်းကို Database မှ ဖျက်မယ်
    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $customer_id);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Removed',
                'deleted_item' => $item_info // ✅ Undo အတွက် data အပိုထည့်ပေးထားပါတယ်
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Item not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    mysqli_stmt_close($stmt);
}
?>