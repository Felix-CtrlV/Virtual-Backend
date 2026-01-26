<?php
// utils/update_user_status.php
include("../../../BackEnd/config/dbconfig.php");
session_start();

// Ensure clean output
ob_clean(); 
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
$role = $data['role'] ?? null;
$new_status = $data['status'] ?? null;

if (empty($id) || empty($new_status) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$table = '';
$id_column = '';

if ($role === 'customer') {
    $table = 'customers';
    $id_column = 'customer_id';
} elseif ($role === 'supplier') {
    $table = 'suppliers';
    $id_column = 'supplier_id';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

$query = "UPDATE $table SET status = ? WHERE $id_column = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $new_status, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
?>