<?php
header('Content-Type: application/json');
session_start();

require_once '../../BackEnd/config/dbconfig.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// 1. Inputs (Name removed to match customer flow, relying on Email)
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$returnUrl = $data['return_url'] ?? 'suppliers/dashboard.php';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

// 2. Fetch Supplier (added status check)
$sql = "SELECT supplier_id, password, status FROM suppliers WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();
$stmt->close();

if (!$supplier) {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

// 3. Verify Password
if (password_verify($password, $supplier['password'])) {
    
    // 4. CHECK BAN STATUS
    // Assuming status column contains 'banned' or 'active'
    if ($supplier['status'] === 'banned') {
        // Set session data for banned.php to use
        $_SESSION['banned_user'] = [
            'id' => $supplier['supplier_id'],
            'type' => 'supplier'
        ];
        
        // Return redirect instruction to frontend
        echo json_encode([
            'success' => false, 
            'redirect' => 'banned.php' 
        ]);
        exit;
    }

    // Success Login
    $_SESSION['supplier_logged_in'] = true;
    $_SESSION['supplierid'] = $supplier['supplier_id'];

    echo json_encode(['success' => true, 'return_url' => $returnUrl]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
}
?>