<?php
// Start session at the very top
if (session_status() === PHP_SESSION_NONE) {
    // Set cookie path so session works across subfolders
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Prevent any output before JSON
ob_start();

// Include DB
if (!isset($conn)) {
    include __DIR__ . '/../../../BackEnd/config/dbconfig.php';
}

header('Content-Type: application/json');

// --- 1. Check if admin is logged in ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// --- 2. Validate request ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
$action = $_POST['action'] ?? '';

if (!$company_id || !in_array($action, ['accept', 'reject'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
    exit;
}

// --- 3. Determine status ---
$status = $action === 'accept' ? 'active' : 'rejected';

// --- 4. Update DB ---
$stmt = mysqli_prepare($conn, "UPDATE companies SET status = ? WHERE company_id = ?");

// Fallback if table is singular
if (!$stmt) {
    $stmt = mysqli_prepare($conn, "UPDATE company SET status = ? WHERE company_id = ?");
}

if (!$stmt) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'DB prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "si", $status, $company_id);

if (mysqli_stmt_execute($stmt)) {
    ob_clean();
    echo json_encode(['success' => true]);
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'DB execute failed: ' . mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
ob_end_flush();
