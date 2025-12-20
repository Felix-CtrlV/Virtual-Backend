<?php
include("../../../BackEnd/config/dbconfig.php");
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$supplier_id = $data['supplier_id'] ?? null;
$new_status = $data['status'] ?? null;

if (empty($supplier_id) || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$query = "UPDATE suppliers SET status = ? WHERE supplier_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $new_status, $supplier_id);
if($stmt->execute()){
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}