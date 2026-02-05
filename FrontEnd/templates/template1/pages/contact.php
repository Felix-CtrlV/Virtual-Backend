<?php
require_once "../utils/messages.php";

if (!isset($_SESSION['customer_id'])) {
    die("You must be logged in to send a message.");
}

$customer_id = (int)$_SESSION['customer_id'];
// $supplier_id MUST be defined before this file loads

// Handle form submit
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $message_text = trim($_POST['message']);

    $sent = sendContactMessage($conn, $customer_id, $supplier_id, $message_text);

    $feedback = $sent
        ? "Message sent successfully!"
        : "Failed to send message. Please try again.";
}

// Fetch supplier data
$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        c.company_name,
        c.description,
        c.phone,
        c.address,
        s.email
     FROM suppliers s
     JOIN companies c ON s.supplier_id = c.supplier_id
     WHERE s.supplier_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $supplier_id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Fallbacks
$data = $data ?: [
    'company_name' => '',
    'description'  => '',
    'phone'        => '',
    'email'        => '',
    'address'      => ''
];

// Logo
$logo = "../assets/images/logo-placeholder.png";
foreach (['png','jpg','webp'] as $ext) {
    $path = "../uploads/shops/{$supplier_id}/{$supplier_id}_logo.$ext";
    if (file_exists($path)) {
        $logo = $path;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Us</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
}

body {
    background: #60708d;
    color: #000;
    min-height: 100vh;
}

.contact-section {
    text-align: center;
    padding: 80px 20px;
}

.small-title {
    color: #959dab;
    letter-spacing: 2px;
    font-size: 12px;
    margin-bottom: 10px;
}

.main-title {
    font-size: 42px;
    font-weight: 600;
    margin-bottom: 50px;
}

.contact-card {
    max-width: 420px;
    margin: auto;
    background: #959dab;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.6);
}

/* Brand */
.brand-logo {
    width: 64px;
    margin-bottom: 15px;
}

.brand-name {
    font-size: 20px;
    margin-bottom: 8px;
}

.brand-desc {
    font-size: 14px;
    color: #959dab;
    margin-bottom: 25px;
}

/* Info */
.contact-info p {
    font-size: 14px;
    color: #ddd;
    margin-bottom: 8px;
}

/* Form */
.contact-form {
    margin-top: 25px;
}

.contact-form textarea {
    width: 100%;
    height: 120px;
    background: #0b0b0b;
    border: 1px solid #2a2a2a;
    border-radius: 10px;
    padding: 12px;
    color: #fff;
    resize: none;
    margin-bottom: 15px;
}

.contact-form textarea::placeholder {
    color: #777;
}

.contact-form button {
    width: 100%;
    background: #60708d;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-weight: 600;
    cursor: pointer;
}

.contact-form button:hover {
    background: #959dab;
}

.feedback {
    margin-bottom: 15px;
    color: #959dab;
    font-size: 14px;
}
</style>
</head>

<body>

<section class="contact-section">
    <p class="small-title">CONTACT</p>
    <h1 class="main-title">Get in touch with us</h1>

    <div class="contact-card">

        <img src="<?= htmlspecialchars($logo) ?>" class="brand-logo" alt="Logo">

        <h2 class="brand-name"><?= htmlspecialchars($data['company_name']) ?></h2>

        <p class="brand-desc"><?= htmlspecialchars($data['description']) ?></p>

        <div class="contact-info">
            <p><strong>Phone:</strong> <?= htmlspecialchars($data['phone']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($data['email']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($data['address']) ?></p>
        </div>

        <?php if ($feedback): ?>
            <div class="feedback"><?= htmlspecialchars($feedback) ?></div>
        <?php endif; ?>

        <form method="POST" class="contact-form">
            <textarea name="message" placeholder="Write your message here..." required></textarea>
            <button type="submit">Send Message</button>
        </form>

    </div>
</section>

</body>
</html>
