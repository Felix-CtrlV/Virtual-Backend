<?php
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

// ================================
// CONTACT PAGE BACKGROUND IMAGE
// ================================

$bg_base_path = "../uploads/shops/{$supplier_id}/";
$bg_allowed_ext = ['jpg', 'png', 'webp'];

$contact_bg_image = "../assets/images/contact-bg-placeholder.jpg"; // fallback

foreach ($bg_allowed_ext as $ext) {
    $bg_path = $bg_base_path . "contact-bg.$ext"; // 👈 SPECIAL SET NAME
    if (file_exists($bg_path)) {
        $contact_bg_image = $bg_path;
        break;
    }
}

// ================================
// CONTACT PAGE SUPPLIER DATA
// ================================

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

// Safety fallback
$supplier_data = $supplier_data ?: [
    'company_name' => '',
    'description'  => '',
    'email'        => '',
    'phone'        => '',
    'address'      => ''
];
?>

<div class="contact-page-bg"
     style="background-image: url('<?= htmlspecialchars($contact_bg_image) ?>');">

  
  <div class="contact-card-container">
    <div class="contact-page">
      
      <div class="contact-content-wrapper">
        <section class="contact-header">
          <h2>CONTACT US</h2>
          <p>For any inquiries, or just to say hello, get in touch and contact us.</p>
        </section>

        <div class="contact-info-grid">
          <div class="info-column">
            <i class="bi bi-house-door-fill"></i>
            <h3>VISIT US</h3>
            <p><?= htmlspecialchars($supplier_data['address']) ?></p>
          </div>
          <div class="info-column">
            <i class="bi bi-telephone-fill"></i>
            <h3>CALL US</h3>
            <p><?= htmlspecialchars($supplier_data['phone']) ?></p>
          </div>
          <div class="info-column">
            <i class="bi bi-envelope-fill"></i>
            <h3>CONTACT US</h3>
            <p><a href="mailto:<?= htmlspecialchars($supplier_data['email']) ?>"><?= htmlspecialchars($supplier_data['email']) ?></a></p>
          </div>
        </div>
      </div>

      <div class="contact-image-container">
        <img src="<?= htmlspecialchars($image_to_use) ?>" alt="Supplier image">
      </div>

    </div>
  </div>
</div>


