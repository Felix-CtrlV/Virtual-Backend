<?php
session_start();
include("../../../BackEnd/config/dbconfig.php");

if (!isset($_POST["savebutton"])) {
    header("Location: ../setting.php");
    exit;
}

$admin_id = $_SESSION["adminid"];

$fullname = $_POST["fullname"];
$email    = $_POST["email"];
$username = $_POST["username"];
$phone    = $_POST["phone"];

$stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, username = ?, phone = ? WHERE adminid = ?");
$stmt->bind_param("ssssi", $fullname, $email, $username, $phone, $admin_id);
$stmt->execute();
$stmt->close();

if (!empty($_POST["new_password"]) && !empty($_POST["current_password"]) && !empty($_POST["confirm_password"])) {

    $stmt = $conn->prepare("SELECT password FROM admins WHERE adminid = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin  = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($_POST["current_password"], $admin["password"])) {
        header("Location: ../setting.php?status=wrong_password");
        exit;
    }

    if ($_POST["new_password"] !== $_POST["confirm_password"]) {
        header("Location: ../setting.php?status=password_mismatch");
        exit;
    }

    $hashed = password_hash($_POST["new_password"], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE adminid = ?");
    $stmt->bind_param("si", $hashed, $admin_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../setting.php?status=success");
exit;
?>
