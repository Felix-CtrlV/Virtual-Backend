<?php
session_start();
include("../../../BackEnd/config/dbconfig.php");

if (!isset($_SESSION['adminid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_GET['conversation_id'])) {
    $conversation_id = mysqli_real_escape_string($conn, $_GET['conversation_id']);

    // Fetch messages ordered by time ASC (oldest at top, newest at bottom)
    $sql = "SELECT * FROM messages WHERE conversation_id = '$conversation_id' ORDER BY created_at ASC";
    $result = mysqli_query($conn, $sql);

    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} else {
    echo json_encode(['success' => false, 'message' => 'No conversation ID provided']);
}
?>