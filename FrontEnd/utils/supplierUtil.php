<?php
header('Content-Type: application/json');
session_start();

require_once '../../BackEnd/config/dbconfig.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$name || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

$sql = "SELECT supplier_id, password FROM suppliers WHERE name = ? AND email = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("ss", $name, $email);
$stmt->execute();

$result = $stmt->get_result();
$supplier = $result->fetch_assoc();

$stmt->close();

if (!$supplier) {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

if (password_verify($password, $supplier['password'])) {
    $_SESSION['supplier_logged_in'] = true;
    $_SESSION['supplierid'] = $supplier['supplier_id'];

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
}
