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

// Start transaction
mysqli_begin_transaction($conn);

try {
    // --- 4. Update Company Status ---
    $stmt = mysqli_prepare($conn, "UPDATE companies SET status = ? WHERE company_id = ?");
    
    if (!$stmt) {
        throw new Exception('DB prepare failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "si", $status, $company_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('DB execute failed: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
    // --- 5. If approved, create conversation ---
    if ($action === 'accept') {
        // Get supplier_id from company
        $supplier_query = mysqli_prepare($conn, "SELECT supplier_id FROM companies WHERE company_id = ?");
        if (!$supplier_query) {
            $supplier_query = mysqli_prepare($conn, "SELECT supplier_id FROM company WHERE company_id = ?");
        }
        
        if (!$supplier_query) {
            throw new Exception('Failed to prepare supplier query: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($supplier_query, "i", $company_id);
        mysqli_stmt_execute($supplier_query);
        $supplier_result = mysqli_stmt_get_result($supplier_query);
        $supplier_data = mysqli_fetch_assoc($supplier_result);
        mysqli_stmt_close($supplier_query);
        
        if (!$supplier_data) {
            throw new Exception('Company not found');
        }
        
        $supplier_id = $supplier_data['supplier_id'];
        $admin_id = $_SESSION['adminid'];
        
        // Create conversation
        $conv_query = mysqli_prepare($conn, "INSERT INTO conversations (supplier_id, created_at) VALUES (?, NOW())");
        if (!$conv_query) {
            throw new Exception('Failed to prepare conversation query: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($conv_query, "i", $supplier_id);
        if (!mysqli_stmt_execute($conv_query)) {
            throw new Exception('Failed to create conversation: ' . mysqli_stmt_error($conv_query));
        }
        
        $conversation_id = mysqli_insert_id($conn);
        mysqli_stmt_close($conv_query);
        
        // Create conversation participants - Admin
        $admin_participant = mysqli_prepare($conn, "INSERT INTO conversation_participants (conversation_id, user_type, user_id, created_at) VALUES (?, 'admin', ?, NOW())");
        if (!$admin_participant) {
            throw new Exception('Failed to prepare admin participant query: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($admin_participant, "ii", $conversation_id, $admin_id);
        if (!mysqli_stmt_execute($admin_participant)) {
            throw new Exception('Failed to create admin participant: ' . mysqli_stmt_error($admin_participant));
        }
        mysqli_stmt_close($admin_participant);
        
        // Create conversation participants - Supplier
        $supplier_participant = mysqli_prepare($conn, "INSERT INTO conversation_participants (conversation_id, user_type, user_id, created_at) VALUES (?, 'supplier', ?, NOW())");
        if (!$supplier_participant) {
            throw new Exception('Failed to prepare supplier participant query: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($supplier_participant, "ii", $conversation_id, $supplier_id);
        if (!mysqli_stmt_execute($supplier_participant)) {
            throw new Exception('Failed to create supplier participant: ' . mysqli_stmt_error($supplier_participant));
        }
        mysqli_stmt_close($supplier_participant);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    ob_clean();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
ob_end_flush();
