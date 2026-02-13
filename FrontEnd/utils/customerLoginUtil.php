<?php
header('Content-Type: application/json');
session_start();

require_once '../../BackEnd/config/dbconfig.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$returnUrl = $data['return_url'] ?? 'index.php'; // fallback

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// 1. Fetch Customer (Removed "and status = 'active'" and added status to SELECT)
$sql = "SELECT customer_id, password, status FROM customers WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();

$stmt->close();

if (!$customer) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// 2. Verify Password
if (password_verify($password, $customer['password'])) {
   
    // 3. CHECK BAN STATUS
    if ($customer['status'] === 'banned') {
        // Set session data for banned.php to use
        $_SESSION['banned_user'] = [
            'id' => $customer['customer_id'],
            'type' => 'customer'
        ];
        
        // Return redirect instruction to frontend
        echo json_encode([
            'success' => false, 
            'redirect' => 'banned.php' 
        ]);
        exit;
    }

    // 4. Success Login
    $_SESSION['customer_logged_in'] = true; 
    $_SESSION['customer_id'] = $customer['customer_id']; 
    $_SESSION['is_logged_in'] = true; 
    
    $new_id = $customer['customer_id'];
    mysqli_query($conn, "UPDATE cart SET customer_id = '$new_id' WHERE customer_id = 1");

    echo json_encode(['success' => true, 'return_url' => $returnUrl]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
}
?>