<?php
// utils/get_user_details.php
include("../../../BackEnd/config/dbconfig.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$id = $_POST['id'] ?? null;
$role = $_POST['role'] ?? null;

if (!$id || !$role) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$table = '';
$id_col = '';

if ($role === 'admin') {
    $table = 'admins';
    $id_col = 'adminid';
} elseif ($role === 'supplier') {
    $table = 'suppliers';
    $id_col = 'supplier_id';
} elseif ($role === 'customer') {
    $table = 'customers';
    $id_col = 'customer_id';
} else {
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

$stmt = $conn->prepare("SELECT name, email, status, image, created_at FROM $table WHERE $id_col = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data) {
    echo json_encode([
        'success' => true,
        'id' => $id,
        'role' => $role,
        'name' => $data['name'],
        'email' => $data['email'],
        'status' => strtolower($data['status']),
        'image' => $data['image'] ?? 'default.png', // Fallback if null
        'joined' => date("M d, Y", strtotime($data['created_at']))
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>