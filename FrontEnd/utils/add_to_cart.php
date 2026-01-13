<?php
session_start();
header('Content-Type: application/json');

include '../../BackEnd/config/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

if (
    !isset($_POST['variant_id'], $_POST['supplier_id'], $_POST['quantity'])
) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

// TEMP for testing
$customer_id = 1;
// $customer_id = (int) $_SESSION['customer_id'];

$variant_id = (int) $_POST['variant_id'];
$supplier_id = (int) $_POST['supplier_id'];
$quantity = max(1, (int) $_POST['quantity']);

$check_stmt = mysqli_prepare(
    $conn,
    "SELECT cart_id, quantity FROM cart WHERE customer_id = ? AND variant_id = ?"
);
mysqli_stmt_bind_param($check_stmt, "ii", $customer_id, $variant_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $new_qty = $row['quantity'] + $quantity;

    $update_stmt = mysqli_prepare(
        $conn,
        "UPDATE cart SET quantity = ? WHERE cart_id = ?"
    );
    mysqli_stmt_bind_param($update_stmt, "ii", $new_qty, $row['cart_id']);
    $success = mysqli_stmt_execute($update_stmt);
} else {
    $insert_stmt = mysqli_prepare(
        $conn,
        "INSERT INTO cart (customer_id, supplier_id, variant_id, quantity)
         VALUES (?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param(
        $insert_stmt,
        "iiii",
        $customer_id,
        $supplier_id,
        $variant_id,
        $quantity
    );
    $success = mysqli_stmt_execute($insert_stmt);
}

if ($success) {
    echo json_encode(['status' => 'success', 'message' => 'Item added to cart!']);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
}
