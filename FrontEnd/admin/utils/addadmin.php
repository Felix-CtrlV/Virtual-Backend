<?php

include("../../../BackEnd/config/dbconfig.php");

$hashed = password_hash('kaung273', PASSWORD_DEFAULT);

// Insert new admin
$fullname = 'felix';
$email = 'kaungswan59@gmail.com';
$username = 'ion run it';
$phone = '0912312312';

$stmt = $conn->prepare("INSERT INTO admins (name, email, username, phone, password) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $fullname, $email, $username, $phone, $hashed);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: ../add_admin.php?status=success");
    exit;
} else {
    $stmt->close();
    header("Location: ../add_admin.php?status=error");
    exit;
} 
?>