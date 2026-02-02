<?php
// utils/messages.php

function sendContactMessage($conn, $customer_id, $supplier_id, $message) {
    if (empty($message)) return false;

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO contact_messages (customer_id, company_id, message, status, created_at)
         VALUES (?, ?, ?, 'pending', NOW())"
    );
    mysqli_stmt_bind_param($stmt, "iis", $customer_id, $supplier_id, $message);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}
?>
