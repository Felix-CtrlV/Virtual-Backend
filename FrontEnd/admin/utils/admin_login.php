<?php
header('Content-Type: application/json');
session_start();

if (!isset($conn)) {
    include '../../../BackEnd/config/dbconfig.php';
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false]);
    exit();
}

$name = $data['username'] ?? ''; 
$password = $data['password'] ?? '';

$sql = "SELECT * FROM admins WHERE name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
$admininfo = $result->fetch_assoc();
$stmt->close();

if (!$admininfo) {
    echo json_encode(['success' => false]);
    exit();
}

if (password_verify($password, $admininfo['password'])) {
    $_SESSION["admin_logged_in"] = true;
    $_SESSION["adminid"] = $admininfo['adminid'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
