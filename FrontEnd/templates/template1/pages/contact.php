<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../utils/messages.php";

if (!isset($supplier_id)) {
    die("Supplier not defined.");
}

$customer_id = $_SESSION['customer_id'] ?? null;

/* ===============================
   FETCH SUPPLIER + COMPANY INFO
================================= */
$stmt = mysqli_prepare($conn, "
    SELECT 
        s.email,
        c.company_name,
        c.description,
        c.address,
        c.phone
    FROM suppliers s
    LEFT JOIN companies c ON s.supplier_id = c.supplier_id
    WHERE s.supplier_id = ?
");

mysqli_stmt_bind_param($stmt, "i", $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

/* ===============================
   HANDLE MESSAGE SUBMIT
================================= */
$feedback_js = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message']) && $customer_id) {
    $message_text = trim($_POST['message']);

    if (sendContactMessage($conn, $customer_id, $supplier_id, $message_text)) {
        $feedback_js = "showPopup('Message sent successfully!', 'success')";
    } else {
        $feedback_js = "showPopup('Failed to send message.', 'error')";
    }
}

/* ===============================
   LOAD BACKGROUND IMAGE
================================= */
$contact_bg = '';
$base_url_path = "/Malltiverse/FrontEnd/uploads/shops/{$supplier_id}/";
$base_fs_path  = $_SERVER['DOCUMENT_ROOT'] . $base_url_path;

$allowed_ext = ['jpg','png','webp'];

foreach ($allowed_ext as $ext) {
    $file = "{$supplier_id}_contact.$ext";
    if (file_exists($base_fs_path . $file)) {
        $contact_bg = $base_url_path . $file;
        break;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Contact</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #fdfcf9; /* Matches the light background color */
}

/* CONTACT SECTION */
.contact-section {
    min-height: 100vh; /* Set to full viewport height */
    display: flex;
    align-items: center;
    justify-content: flex-end; /* Keeps text on the right */
    padding: 0 10% 0 0;
    /* Use 'cover' to ensure it fills the space naturally, anchored to the left */
    background-size: cover; 
    background-position: left center;
    background-repeat: no-repeat;
}

<?php if ($contact_bg): ?>
.contact-section {
    background-image: url("<?= htmlspecialchars($contact_bg) ?>");
}
<?php endif; ?>

.contact-content {
    width: 500px;
    text-align: center; /* Matches the centered text in your photo */
}

/* Heading: HeavyLoads */
.contact-content h2 {
    margin: 0;
    margin-bottom: 20px;
    font-size: 72px; /* Large and bold as seen in image */
    font-weight: 800;
    color: #60708d; /* The deep red color */
}

/* Description / Slogan */
.contact-content p:first-of-type {
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 2px;
    color: #959dab; /* Muted red slogan */
    margin-top: -5px;
    margin-bottom: 30px;
}

/* Contact Info Text */
.contact-content p {
    margin: 5px 0;
    font-size: 18px;
    color: #959dab;
}

.contact-content p strong {
    font-weight: bold;
    color: #60708d;
    margin-right: 5px;
}

/* Textarea styling to match the white box */
.contact-content textarea {
    width: 90%;
    height: 130px;
    margin: 25px auto 0 auto;
    padding: 15px;
    border: none;
    border-radius: 4px;
    background: #ffffff;
    display: block;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03); /* Subtle shadow for depth */
    font-size: 14px;
}

/* Button Styling */
.contact-content button {
    margin-top: 20px;
    padding: 12px 45px;
    border: none;
    background: #60708d; /* The specific brownish-red from the image */
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    border-radius: 8px;
    transition: 0.3s;
}

.contact-content button:hover {
    opacity: 0.9;
}

/* POPUP */
.popup {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 10px 20px;
    border-radius: 5px;
}


/* ===============================
   WHY US SECTION - UPDATED VIBE
================================= */
.why-us {
    padding: 100px 80px;
    background-color: #ffffff; /* Matches top section background */
    text-align: center;
}

.why-us h2 {
    font-size: 42px;
    font-weight: 800;
    color: #60708d; /* Same deep red as main title */
    margin-bottom: 60px;
    letter-spacing: -1px;
    text-transform: uppercase;
}

.why-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
}

.why-card {
    padding: 40px 30px;
    border-radius: 4px; /* Sharp, clean edges like the image */
    background: #ffffff;
    border: 1px solid #f2ece4;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
}

.why-card h3 {
    font-size: 22px;
    color: #60708d;
    margin-bottom: 15px;
    font-weight: 700;
}

.why-card p {
    font-size: 16px;
    color: #959dab; /* Same muted red/grey as top text */
    line-height: 1.6;
}

/* HOVER EFFECTS */
.why-card:hover {
    transform: translateY(-10px); /* Lifts the card up */
    background-color: #ffffff;
    box-shadow: 0 20px 40px rgba(165, 48, 48, 0.08); /* Soft red shadow */
    border-color: #60708d;
}

/* Subtle line effect on hover */
.why-card::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 4px;
    background: #60708d;
    transition: width 0.4s ease;
}

.why-card:hover::after {
    width: 100%;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
    .contact-section {
        background-position: -150px center; /* Pulls image left to make room */
    }
}

@media (max-width: 800px) {
    .contact-section {
        background-image: none !important;
        justify-content: center;
        padding: 50px 20px;
    }
    .contact-content { width: 100%; }
}

   .why-grid {
        grid-template-columns: 1fr;
    }

</style>
</head>

<body>
<section class="contact-section">

    <div class="contact-content">

        <!-- Company Name -->
        <h2><?= htmlspecialchars($data['company_name'] ?? '') ?></h2>

        <!-- Slogan -->
        <?php if (!empty($data['description'])): ?>
            <p class="slogan"><?= nl2br(htmlspecialchars($data['description'])) ?></p>
        <?php endif; ?>

        <!-- Contact Info -->
        <p><strong>Email:</strong> <?= htmlspecialchars($data['email'] ?? '') ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($data['phone'] ?? '') ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($data['address'] ?? '') ?></p>

        <!-- Message Box -->
        <?php if ($customer_id): ?>
            <form method="POST">
                <textarea name="message" placeholder="Write your message..." required></textarea>
                <button type="submit">Send Message</button>
            </form>
        <?php else: ?>
            <textarea 
                placeholder="Please log in to send us a message"
                readonly
                onclick="openAuthModal()"
                style="cursor:pointer;"></textarea>

            <button type="button" onclick="openAuthModal()">
                Login Required
            </button>
        <?php endif; ?>

        <!-- Popup -->
        <div id="message-popup" class="popup"></div>

    </div>

</section>



<!-- ===============================
     WHY US SECTION
================================= -->
<section class="why-us">
    <h2>Why Choose Us?</h2>

    <div class="why-grid">
        <div class="why-card">
            <h3>Fast Response</h3>
            <p>We reply within 24 hours.</p>
        </div>

        <div class="why-card">
            <h3>Trusted Quality</h3>
            <p>We ensure high product standards.</p>
        </div>

        <div class="why-card">
            <h3>Secure Shopping</h3>
            <p>Your data is safe with us.</p>
        </div>
    </div>
</section>

<script>
function showPopup(message, type) {
    const popup = document.getElementById('message-popup');
    popup.textContent = message;
    popup.className = 'popup ' + type;
    popup.style.display = 'block';
    popup.style.opacity = '1';

    // Hide after 2 seconds
    setTimeout(() => {
        popup.style.opacity = '0';
        setTimeout(() => { popup.style.display = 'none'; }, 300);
    }, 2000);
}

// Trigger popup if PHP has feedback
<?php if ($feedback_js): ?>
    <?= $feedback_js ?>;
<?php endif; ?>
</script>

</body>
</html>
