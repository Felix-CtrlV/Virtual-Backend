<?php
session_start();
include '../../BackEnd/config/dbconfig.php';

header('Content-Type: application/json');


$customer_id = $_SESSION['customer_id'] ?? 2; // Typo fixed from 'customer=_id'

if (!isset($_POST['variant_id'], $_POST['supplier_id'], $_POST['quantity'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit;
}

$variant_id = (int) $_POST['variant_id'];
$supplier_id = (int) $_POST['supplier_id'];
$quantity = (int) $_POST['quantity'];

if ($quantity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid quantity']);
    exit;
}


$check_stmt = mysqli_prepare($conn, "SELECT card_id, quantity FROM cart WHERE customer_id = ? AND variant_id = ? AND supplier_id = ?");
mysqli_stmt_bind_param($check_stmt, "iii", $customer_id, $variant_id, $supplier_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

$success = false;

if ($row = mysqli_fetch_assoc($result)) {
  
    $new_qty = $row['quantity'] + $quantity;
    $update_stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE card_id = ?");
    mysqli_stmt_bind_param($update_stmt, "ii", $new_qty, $row['card_id']);
    $success = mysqli_stmt_execute($update_stmt);
} else {
    // ၃။ အသစ်ထည့်ခြင်း
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO cart (customer_id, supplier_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert_stmt, "iiii", $customer_id, $supplier_id, $variant_id, $quantity);
    $success = mysqli_stmt_execute($insert_stmt);
}

// ၄။ ရလဒ် ထုတ်ပေးခြင်း
if ($success) {
    echo json_encode(['status' => 'success', 'message' => 'Item added to selection!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>