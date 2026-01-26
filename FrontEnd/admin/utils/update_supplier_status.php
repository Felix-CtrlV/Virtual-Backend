<?php
include("../../../BackEnd/config/dbconfig.php");
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$company_id = $data['company_id'] ?? null;
$new_status = $data['status'] ?? null;

if (empty($company_id) || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$query = "UPDATE companies SET status = ? WHERE company_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $new_status, $company_id);
if($stmt->execute()){
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}