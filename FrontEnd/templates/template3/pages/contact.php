<?php

require_once '../utils/messages.php';

$message_sent = false;
$error_message = "";

// Capture the supplier_id from the URL (?supplier_id=1)
// $supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;


if (isset($_POST['submit'])) {
    $message = htmlspecialchars(trim($_POST['message']));
    $customer_id = 1; // Replace with $_SESSION['user_id'] if available

    if (!empty($message) && $company_id > 0) {
        // $conn comes from the include in index.php
        $success = sendContactMessage($conn, $customer_id, $company_id, $message);

        if ($success) {
            $message_sent = true;
        } else {
            $error_message = "Database error: Could not save message.";
        }
    } else {
        $error_message = "Please enter a message and ensure a valid supplier is selected.";
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Brandflow Agency</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .glass-popup {
            background: rgba(255, 255, 255, 0.08) !important;
            backdrop-filter: blur(25px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
            border-radius: 28px !important;
            padding: 2.5em 1.5em !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
        }

        .glass-title {
            color: #ffffff !important;
            font-family: 'Inter', sans-serif !important;
            font-weight: 600 !important;
            letter-spacing: -0.5px !important;
        }

        .glass-content {
            color: rgba(255, 255, 255, 0.8) !important;
            font-family: 'Inter', sans-serif !important;
            line-height: 1.6 !important;
        }

        .swal2-icon.swal2-success {
            border-color: #000000 !important;
        }

        .swal2-icon.swal2-success [class^='swal2-success-line'] {
            background-color: #000000 !important;
        }

        .swal2-icon.swal2-success .swal2-success-ring {
            border: 4px solid #000000 !important;
        }

        .swal2-success-circular-line-left {
            display: none !important;
        }

        .swal2-success-circular-line-right {
            display: none !important;
        }

        .swal2-success-fix {
            display: none !important;
        }

        .glass-confirm-btn {
            background: #000000 !important;
            color: #ffffff !important;
            border-radius: 14px !important;
            padding: 12px 40px !important;
            font-weight: 600 !important;
            border: none !important;
            outline: none !important;
            box-shadow: 0 10px 20px -10px rgba(0, 0, 0, 0.4) !important;
            transition: all 0.3s ease !important;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .glass-confirm-btn:hover {
            background: #222222 !important;
            transform: translateY(-2px) !important;
        }
    </style>
</head>

<body>
    <section class="contact-wrapper">
        <div class="section-header">
            <h1>CONTACT INFO</h1>
            <div class="gold-divider"></div>
            <p>Our team is ready to help you grow. Send us a message and we'll reply within one business day.</p>
        </div>

        <div class="info-grid">
            <div class="side-title">
                <div class="title-wrapper">
                    <h2>
                        <span class="light-text">GET IN</span>
                        <span class="bold-text">TOUCH</span>
                    </h2>
                    <div class="decorative-line"></div>
                </div>
            </div>

            <div class="cards-container">
                <div class="info-box">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h3>Shop Location</h3>
                    <p>Ward 7, Pyay Road, Kamaryut Township, Yangon</p>
                    <a href="#" class="link">Direction <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="info-box">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <h3>Working Hours</h3>
                    <p>Sun to Fri: 10am to 6pm<br>Sat: 10am to 2pm</p>
                    <a href="#" class="link">Learn more <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="info-box">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <h3>Communication</h3>
                    <p>+95933445577<br>adora@gmail.com</p>
                    <a href="#" class="link">Support <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="form-outer-box">
            <div class="form-flex">
                <div class="form-inputs animate-left">
                    <form method="POST">

                        <div class="input-group">
                            <textarea name="message" rows="4" placeholder="Your Message" required></textarea>
                            <span class="focus-border"></span>
                        </div>
                        <button type="submit" name="submit" class="submit-btn">
                            <span>Send Message</span>
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

                <div class="form-info-text animate-right">
                    <p class="query-tag">HAVE ANY QUERY?</p>
                    <h2>CONTACT <span class="accent">US</span></h2>
                    <p class="description">We’re here to help! Send us a message and our team will get back to you
                        within 24 hours.</p>
                    <div class="social-links">
                        <i class="fab fa-facebook-f"></i>
                        <i class="fab fa-instagram"></i>
                        <i class="fab fa-linkedin-in"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const config = {
                customClass: {
                    popup: 'glass-popup',
                    title: 'glass-title',
                    htmlContainer: 'glass-content',
                    confirmButton: 'glass-confirm-btn'
                },
                buttonsStyling: false,
                confirmButtonText: 'Understood',
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                }
            };

            <?php if ($message_sent): ?>
                Swal.fire({
                    ...config,
                    title: 'Success!',
                    text: 'Your message has been delivered to our team.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                Swal.fire({
                    ...config,
                    title: 'Something went wrong',
                    text: '<?php echo $error_message; ?>',
                    icon: 'error',
                    confirmButtonText: 'Try Again'
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>