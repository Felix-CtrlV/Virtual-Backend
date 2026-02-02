<?php

require_once "../utils/messages.php"; // Include the message helper

// Make sure user is logged in
if (!isset($_SESSION['customer_id'])) {
    die("You must be logged in to send a message.");
}

$customer_id = $_SESSION['customer_id'];
$supplier_id = $supplier_id; // Already set in your existing code

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message_text = trim($_POST['message']);
    $sent = sendContactMessage($conn, $customer_id, $supplier_id, $message_text);
    
    if ($sent) {
        $feedback = "Message sent successfully!";
    } else {
        $feedback = "Failed to send message. Please try again.";
    }
}



$base_path = "../uploads/shops/{$supplier_id}/";
$allowed_ext = ['jpg', 'png', 'webp'];

$image_to_use = "../assets/images/contact-placeholder.jpg";
foreach ($allowed_ext as $ext) {
    $path = $base_path . "{$supplier_id}_contact.$ext";
    if (file_exists($path)) {
        $image_to_use = $path;
        break;
    }
}

// CONTACT PAGE BACKGROUND IMAGE
$bg_base_path = "../uploads/shops/{$supplier_id}/";
$bg_allowed_ext = ['jpg', 'png', 'webp'];
$contact_bg_image = "../assets/images/contact-bg-placeholder.jpg";
foreach ($bg_allowed_ext as $ext) {
    $bg_path = $bg_base_path . "contact-bg.$ext";
    if (file_exists($bg_path)) {
        $contact_bg_image = $bg_path;
        break;
    }
}

// CONTACT PAGE SUPPLIER DATA
$supplier_stmt = mysqli_prepare(
    $conn,
    "SELECT 
        c.company_name,
        c.description,
        s.email,
        c.phone,
        c.address
     FROM suppliers s
     JOIN companies c ON s.supplier_id = c.supplier_id
     WHERE s.supplier_id = ?"
);

mysqli_stmt_bind_param($supplier_stmt, "i", $supplier_id);
mysqli_stmt_execute($supplier_stmt);
$result = mysqli_stmt_get_result($supplier_stmt);
$supplier_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($supplier_stmt);

$supplier_data = $supplier_data ?: [
    'company_name' => '',
    'description'  => '',
    'email'        => '',
    'phone'        => '',
    'address'      => ''
];


?>



<style>
:root {
  --scale: 1.2; /* 40% bigger */
}

.contact-page-bg {
  background-size: cover;
  background-position: center;
  padding: calc(80px * var(--scale)) 20px;
}

.contact-wrapper {
  max-width: calc(1100px * var(--scale));
  margin: auto;
  display: grid;
  grid-template-columns: calc(420px * var(--scale)) 1fr;
  gap: calc(40px * var(--scale));
}

/* LEFT CARD */
.contact-form-card {
  background: #e6e6e6;
  padding: calc(40px * var(--scale));
  border-radius: 6px;
  color: #333;
}

.contact-form-card h2 {
  margin: 0 0 calc(10px * var(--scale));
  font-size: calc(28px * var(--scale));
}

.contact-form-card p {
  margin-bottom: calc(25px * var(--scale));
  color: #666;
  font-size: calc(16px * var(--scale));
}

.contact-form-card textarea {
  width: 100%;
  min-height: calc(160px * var(--scale));
  padding: calc(15px * var(--scale));
  border: none;
  resize: none;
  background: #9aa57a;
  color: #fff;
  border-radius: 4px;
  font-size: calc(16px * var(--scale));
}

.contact-form-card button {
  margin-top: calc(20px * var(--scale));
  padding: calc(12px * var(--scale)) calc(30px * var(--scale));
  border: none;
  background: #6c74ff;
  color: #fff;
  border-radius: 4px;
  cursor: pointer;
  font-size: calc(16px * var(--scale));
}

/* RIGHT SIDE */
.contact-info {
  display: grid;
  /* Image taller, info boxes shorter */
  grid-template-rows:
    calc(180px * var(--scale) * 1.15)  /* IMAGE: +15% height */
    auto
    auto;
  gap: calc(20px * var(--scale));
}

.contact-image {
  border-radius: 6px;
  overflow: hidden;
}

.contact-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.info-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: calc(20px * var(--scale));
}

.info-box {
  background: #c83b3b;
  color: #fff;
  padding: calc(22px * var(--scale) * 0.85); /* 15% smaller height */
  border-radius: 6px;
  text-align: center;
  font-size: calc(16px * var(--scale));
}

.info-box.full {
  width: 100%;
}

@media (max-width: 768px) {
  .contact-wrapper {
    grid-template-columns: 1fr;
  }
}
</style>
  <div class="contact-wrapper">

    <!-- LEFT FORM -->
    <div class="contact-form-card">
      <h2>Contact us</h2>
      <p>Let us know your thoughts</p>
      <textarea placeholder="Type your message here..."></textarea>
      <button>Send</button>
    </div>

    <!-- RIGHT INFO -->
    <div class="contact-info">

      <div class="contact-image">
        <img src="<?= htmlspecialchars($image_to_use) ?>" alt="<?= htmlspecialchars($supplier_data['company_name']) ?>">
      </div>

      <div class="info-row">
        <div class="info-box">
          <strong>PH</strong><br>
          <?= htmlspecialchars($supplier_data['phone']) ?>
        </div>
        <div class="info-box">
          <strong>@</strong><br>
          <?= htmlspecialchars($supplier_data['email']) ?>
        </div>
      </div>

      <div class="info-box full">
        <strong>ADDRESS</strong><br>
        <?= htmlspecialchars($supplier_data['address']) ?>
      </div>

    </div>

  </div>
</div>
