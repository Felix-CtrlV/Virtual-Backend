<?php
session_start();
include("../../../BackEnd/config/dbconfig.php");

if (!isset($_POST["savebutton"])) {
    header("Location: ../setting.php");
    exit;
}

$admin_id = (int) $_SESSION["adminid"];
$redirect = "Location: ../setting.php";

// --- Profile: name, email, username, phone ---
$fullname = trim($_POST["fullname"] ?? '');
$email    = trim($_POST["email"] ?? '');
$username = trim($_POST["username"] ?? '');
$phone    = trim($_POST["phone"] ?? '');

if ($fullname === '' || $email === '' || $username === '') {
    header($redirect . "?status=missing_fields");
    exit;
}

$stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, username = ?, phone = ? WHERE adminid = ?");
$stmt->bind_param("ssssi", $fullname, $email, $username, $phone, $admin_id);
$stmt->execute();
$stmt->close();

// --- Profile image upload ---
$upload_dir = __DIR__ . "/../../assets/customer_profiles/";
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['profile_image']['tmp_name']);
    finfo_close($finfo);
    if (in_array($mime, $allowed)) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $ext = preg_replace('/[^a-z0-9]/i', '', $ext) ?: 'jpg';
        $new_name = 'admin_' . $admin_id . '_' . time() . '.' . strtolower($ext);
        if (is_dir($upload_dir) && move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_name)) {
            $stmt = $conn->prepare("UPDATE admins SET image = ? WHERE adminid = ?");
            $stmt->bind_param("si", $new_name, $admin_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// --- Password change (only if all three fields filled) ---
$current = $_POST["current_password"] ?? '';
$new     = $_POST["new_password"] ?? '';
$confirm = $_POST["confirm_password"] ?? '';

if ($current !== '' || $new !== '' || $confirm !== '') {
    if ($new === '' || $current === '') {
        header($redirect . "?status=password_required");
        exit;
    }
    if (strlen($new) < 8) {
        header($redirect . "?status=password_too_short");
        exit;
    }
    if ($new !== $confirm) {
        header($redirect . "?status=password_mismatch");
        exit;
    }

    $stmt = $conn->prepare("SELECT password FROM admins WHERE adminid = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin  = $result->fetch_assoc();
    $stmt->close();

    if (!$admin || !password_verify($current, $admin["password"])) {
        header($redirect . "?status=wrong_password");
        exit;
    }

    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE adminid = ?");
    $stmt->bind_param("si", $hashed, $admin_id);
    $stmt->execute();
    $stmt->close();
}

header($redirect . "?status=success");
exit;
