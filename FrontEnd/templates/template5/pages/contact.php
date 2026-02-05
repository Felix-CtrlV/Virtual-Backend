<?php
// ၁။ Session စတင်ခြင်း
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../utils/messages.php'; 

// ၂။ ID များ ရယူခြင်း
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$company_id = 0;

if ($supplier_id > 0) {
    $query = "SELECT company_id FROM companies WHERE supplier_id = $supplier_id LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $company_id = (int)$row['company_id'];
    }
}

// အောင်မြင်မှု အခြေအနေကို မှတ်ရန် Variable
$show_success_modal = false;
$error_message = "";

// ၃။ Form Submit လုပ်ခြင်းကို ကိုင်တွယ်ခြင်း
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_message'])) {
    
    $message = trim($_POST['contact_message']);
    $customer_id = $_SESSION['customer_id'] ?? 0;

    if ($customer_id > 0 && $company_id > 0 && !empty($message)) {
        $is_sent = sendContactMessage($conn, $customer_id, $company_id, $message);

        if ($is_sent) {
            // Redirect မလုပ်တော့ဘဲ modal ပြရန် variable ကို true ပေးလိုက်ပါ
            $show_success_modal = true;
        }
    } else {
        $error_message = "Please ensure you are logged in before sending a message.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | Grand Horizon Timepieces</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;500&family=Inter:wght@200;400&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Alert Box စာလုံးပုံစံကို Luxury ဖြစ်အောင် ပြင်ခြင်း */
        .luxury-font-title {
            font-family: 'Cormorant Garamond', serif !important;
            font-size: 28px !important;
            letter-spacing: 1px !important;
        }
        /* Header ရဲ့ အပေါ်မှာ Alert Box ပေါ်စေရန် layer မြှင့်ခြင်း */
        .swal2-container {
            z-index: 99999 !important;
        }
    </style>
</head>
<body>

    <main class="split-screen">
        <div class="visual-side">
            <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner5 ?? 'default.jpg' ?>">
        </div>

        <div class="content-side">
            <div class="form-wrapper">
                <header>
                    <h1><?= htmlspecialchars($supplier['tags'] ?? 'Contact Us') ?></h1>
                    <p><?= htmlspecialchars($about2 ?? 'How may we assist you today?') ?></p>
                </header>

                <form class="luxury-form" method="POST">
                    <div class="field">
                        <label>Message</label>
                        <textarea name="contact_message" rows="4" placeholder="How may we assist you today?" required></textarea>
                    </div>
                    <button type="submit" name="submit_message" class="submit-btn">Send Message</button>
                </form>
            </div>
        </div>
    </main>

    <?php if ($show_success_modal): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'MESSAGE SENT', 
                text: 'Your inquiry has been received. Our concierge will attend to you shortly.',
                icon: 'success',
                iconColor: '#0bc7cd', 
                background: '#ffffff',
                showConfirmButton: true,
                confirmButtonText: 'CONTINUE',
                confirmButtonColor: '#1a1a1a',
                customClass: {
                    title: 'luxury-font-title'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                 
                    window.location.href = window.location.pathname + window.location.search;
                }
            });
        });
    </script>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '<?= $error_message ?>',
                confirmButtonColor: '#1a1a1a'
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>