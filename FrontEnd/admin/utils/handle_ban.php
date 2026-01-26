<?php
// utils/handle_ban.php
include("../../../BackEnd/config/dbconfig.php");
session_start();

// Ensure clean output
ob_clean();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$entity_id = $data['id'] ?? null;
$role = $data['role'] ?? null;
$reason = $data['reason'] ?? '';
$banned_until = $data['banned_until'] ?? null;
$admin_id = $_SESSION['adminid'] ?? null;

if (!$entity_id || !$role || !$reason || !$banned_until || !$admin_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields or session expired.']);
    exit;
}

// 2. Determine Table and ID Column
$table = '';
$id_column = '';

if ($role === 'customer') {
    $table = 'customers';
    $id_column = 'customer_id';
} elseif ($role === 'supplier') {
    $table = 'suppliers';
    $id_column = 'supplier_id';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid role.']);
    exit;
}

// 3. Transaction: Update Status & Insert into Banned List
$conn->begin_transaction();

try {
    // Update User Status
    $updateSql = "UPDATE $table SET status = 'banned' WHERE $id_column = ?";
    $stmt1 = $conn->prepare($updateSql);
    $stmt1->bind_param("i", $entity_id);
    if (!$stmt1->execute()) {
        throw new Exception("Failed to update status.");
    }

    // Insert into Banned List
    $banSql = "INSERT INTO banned_list (entity_type, entity_id, reason, banned_until, banned_by) VALUES (?, ?, ?, ?, ?)";
    $stmt2 = $conn->prepare($banSql);
    $stmt2->bind_param("sisss", $role, $entity_id, $reason, $banned_until, $admin_id);
    if (!$stmt2->execute()) {
        throw new Exception("Failed to insert ban record.");
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>