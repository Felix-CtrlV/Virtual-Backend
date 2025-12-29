<?php
// Ensure this file is accessed via index.php context
if (!isset($supplier)) {
    die("Access Denied");
}
?>

<style>
    /* Section Styling */
    .contact-section {
        padding: 80px 0 100px;
        background-color: #fff;
    }

    .contact-heading {
        font-weight: 800;
        font-size: 2.5rem;
        margin-bottom: 15px;
        color: #1a1a1b;
    }

    .contact-sub {
        font-size: 1.1rem;
        color: #666;
        margin-bottom: 50px;
    }

    /* Left Side: Contact Info Cards */
    .contact-info-card {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        transition: transform 0.3s ease, border-color 0.3s ease;
        border: 1px solid #eaeaea;
    }

    .contact-info-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary);
        background: #fff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .info-icon-box {
        width: 60px;
        height: 60px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        flex-shrink: 0;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .info-content h5 {
        font-weight: 700;
        margin-bottom: 5px;
        font-size: 1.1rem;
        color: #1a1a1b;
    }

    .info-content p,
    .info-content a {
        margin: 0;
        color: #555;
        font-size: 0.95rem;
        text-decoration: none;
        word-break: break-all;
        /* Handles long emails */
    }

    .info-content a:hover {
        color: var(--primary);
    }

    /* Right Side: Contact Form */
    .contact-form-container {
        background: #fff;
        padding: 40px;
        border-radius: 30px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
    }

    .form-control-custom {
        background-color: #f8f9fa;
        border: 1px solid #eee;
        border-radius: 15px;
        /* Pill inputs */
        padding: 15px 20px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }

    .form-control-custom:focus {
        background-color: #fff;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.03);
        outline: none;
    }

    .btn-send {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 50px;
        padding: 15px 30px;
        font-weight: 700;
        letter-spacing: 0.5px;
        width: 100%;
        transition: opacity 0.3s;
    }

    .btn-send:hover {
        opacity: 0.9;
        color: #fff;
    }

    @media (max-width: 991px) {
        .contact-form-container {
            margin-top: 40px;
            padding: 30px;
        }
    }
</style>

<div class="contact-section">
    <div class="container">

        <div class="row justify-content-center text-center mb-4">
            <div class="col-lg-8">
                <span class="text-uppercase text-primary fw-bold letter-spacing-2" style="font-size: 0.8rem;">Contact Us</span>
                <h2 class="contact-heading">Get in touch</h2>
                <p class="contact-sub">Have a question? We'd love to hear from you.</p>
            </div>
        </div>

        <div class="row align-items-start">

            <div class="col-lg-5">

                <?php if (!empty($supplier['email'])): ?>
                    <div class="contact-info-card">
                        <div class="info-icon-box">
                            <lord-icon
                                src="https://cdn.lordicon.com/wpsdctqb.json"
                                trigger="hover"
                                style="width:32px;height:32px">
                            </lord-icon>
                        </div>
                        <div class="info-content">
                            <h5>Email</h5>
                            <a href="mailto:<?= htmlspecialchars($supplier['email']) ?>"><?= htmlspecialchars($supplier['email']) ?></a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($supplier['phone'])): ?>
                    <div class="contact-info-card">
                        <div class="info-icon-box">
                            <lord-icon
                                src="https://cdn.lordicon.com/wtywrnoz.json"
                                trigger="hover"
                                style="width:32px;height:32px">
                            </lord-icon>
                        </div>
                        <div class="info-content">
                            <h5>Phone</h5>
                            <p><?= htmlspecialchars($supplier['phone']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($supplier['address'])): ?>
                    <div class="contact-info-card">
                        <div class="info-icon-box">
                            <lord-icon
                                src="https://cdn.lordicon.com/surcxhka.json"
                                trigger="hover"
                                colors="primary:#121331,secondary:<?= htmlspecialchars($shop_assets['primary_color']) ?>"
                                style="width:32px;height:32px">
                            </lord-icon>
                        </div>
                        <div class="info-content">
                            <h5>Address</h5>
                            <p><?= htmlspecialchars($supplier['address']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <div class="col-lg-7">
                <div class="contact-form-container">
                    <h3 class="fw-bold mb-4">Send us a Message</h3>

                    <form id="contactForm" method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="name" class="form-label visually-hidden">Name</label>
                                <input type="text" class="form-control form-control-custom" id="name" name="name" placeholder="Your Name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label visually-hidden">Email</label>
                                <input type="email" class="form-control form-control-custom" id="email" name="email" placeholder="Your Email" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label visually-hidden">Message</label>
                            <textarea class="form-control form-control-custom" id="message" name="message" rows="5" placeholder="How can we help you?" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-send">Send Message</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>