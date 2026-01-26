<?php
session_start();
require_once '../BackEnd/config/dbconfig.php';

$token = $_GET['token'] ?? '';
$user_type = $_GET['type'] ?? 'supplier';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $user_type = $_POST['type'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Find user by token (in production, use a proper password_resets table)
        // For now, we'll search through session data
        // In production, store tokens in database with expiry
        
        if ($user_type === 'supplier') {
            $stmt = $conn->prepare("SELECT supplier_id FROM suppliers WHERE email = ?");
            // In production, verify token from database
            // For demo, we'll use a simple approach
            $email = $_POST['email'] ?? '';
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $supplier = $result->fetch_assoc();
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_stmt = $conn->prepare("UPDATE suppliers SET password = ? WHERE supplier_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $supplier['supplier_id']);
                
                if ($update_stmt->execute()) {
                    header('Location: supplierLogin.php?msg=password_reset');
                    exit;
                } else {
                    $error = "Failed to reset password";
                }
            } else {
                $error = "Invalid token or email";
            }
        } else {
            // Customer password reset
            $email = $_POST['email'] ?? '';
            $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $customer = $result->fetch_assoc();
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_stmt = $conn->prepare("UPDATE customers SET password = ? WHERE customer_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $customer['customer_id']);
                
                if ($update_stmt->execute()) {
                    header('Location: customerLogin.php?msg=password_reset');
                    exit;
                } else {
                    $error = "Failed to reset password";
                }
            } else {
                $error = "Invalid token or email";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/Css/<?= $user_type === 'supplier' ? 'supplier.css' : 'customerAuth.css' ?>">
</head>
<body>
    <div class="container">
        <div class="right-panel" style="max-width: 500px; margin: 50px auto;">
            <h1>Reset Password</h1>
            <?php if (isset($error)): ?>
                <p style="color: #d32f2f;"><?= $error ?></p>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($user_type) ?>">
                
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                
                <div class="input-group password-container">
                    <input type="password" name="password" id="password" placeholder="New Password" required>
                    <i id="togglePassword" class="fa-regular fa-eye eye-icon"></i>
                </div>
                
                <div class="input-group password-container">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required>
                    <i id="togglePassword2" class="fa-regular fa-eye eye-icon"></i>
                </div>
                
                <button type="submit" class="submit-btn">Reset Password</button>
            </form>
        </div>
    </div>
    
    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const toggle = document.getElementById('togglePassword');
        const toggle2 = document.getElementById('togglePassword2');
        
        toggle.addEventListener('click', () => {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        });
        
        toggle2.addEventListener('click', () => {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            toggle2.classList.toggle('fa-eye');
            toggle2.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
