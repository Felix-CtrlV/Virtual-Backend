<?php
session_start();
include("../../../BackEnd/config/dbconfig.php");

// 1. Check if Admin is logged in
if (!isset($_SESSION['adminid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['adminid'];

// 2. Validate Input
if (isset($_POST['conversation_id']) && isset($_POST['message'])) {
    $conversation_id = mysqli_real_escape_string($conn, $_POST['conversation_id']);
    $message_text = mysqli_real_escape_string($conn, $_POST['message']);
    
    if(trim($message_text) === '') {
        echo json_encode(['success' => false, 'message' => 'Empty message']);
        exit();
    }

    // 3. Insert Message
    // Schema: message_id, conversation_id, sender_type, sender_id, message_text, created_at
    $sql = "INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, created_at) 
            VALUES ('$conversation_id', 'admin', '$admin_id', '$message_text', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
}
?>