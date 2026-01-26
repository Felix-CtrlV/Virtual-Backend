<?php
session_start();
require_once '../../BackEnd/config/dbconfig.php';

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../phpmailer/src/PHPMailer.php';
require '../../phpmailer/src/SMTP.php';
require '../../phpmailer/src/Exception.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$user_type = $data['type'] ?? 'supplier'; // 'supplier' or 'customer'

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Generate reset token
$reset_token = bin2hex(random_bytes(32));
$reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

if ($user_type === 'supplier') {
    $stmt = $conn->prepare("SELECT supplier_id, name FROM suppliers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        exit;
    }

    $user = $result->fetch_assoc();
    $user_id = $user['supplier_id'];
    $user_name = $user['name'];
} else {
    $stmt = $conn->prepare("SELECT customer_id, name FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        exit;
    }

    $user = $result->fetch_assoc();
    $user_id = $user['customer_id'];
    $user_name = $user['name'];
}

// Store reset token in session (replace with DB storage if you want persistence)
$_SESSION['reset_token_' . $user_id] = [
    'token' => $reset_token,
    'expiry' => $reset_expiry,
    'type' => $user_type
];

// Build reset link
$reset_link = 'http://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . '/reset_password.php?token=' . $reset_token . '&type=' . $user_type;

// Send email using PHPMailer
$mail = new PHPMailer(true);

try {
    // SMTP settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // e.g., Gmail SMTP
    $mail->SMTPAuth = true;
    $mail->Username = 'kaungswan59@gmail.com';       // Your email
    $mail->Password = 'yrdb kwnx exjh zuoa';          // Use app password if 2FA is enabled
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('kaungswan59@gmail.com', 'Malltiverse');
    $mail->addAddress($email, $user_name);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body = "
        <p>Hi {$user_name},</p>
        <p>We received a request to reset your password. Click the link below to reset it:</p>
        <p><a href='{$reset_link}'>Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you didn't request a password reset, please ignore this email.</p>
    ";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Password reset link has been sent to your email'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Could not send email. Mailer Error: ' . $mail->ErrorInfo
    ]);
}
?>
