<?php
// Ensure this file is accessed via index.php
if (!defined('DIR') && !isset($supplier)) {
    // optional security check, or just rely on index.php context
}

// Fallback description if not set in DB
$company_desc = !empty($supplier['description'])
    ? $supplier['description']
    : "Welcome to " . htmlspecialchars($supplier['company_name']) . ". We are dedicated to providing the best quality products and exceptional service to our customers. Our journey began with a simple mission: to make premium goods accessible to everyone.";
?>

<style>
    .about-section {
        padding: 60px 0 100px;
        background-color: #fff;
    }

    .about-heading {
        font-weight: 800;
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: #1a1a1b;
    }

    .about-lead {
        font-size: 1.15rem;
        line-height: 1.8;
        color: #555;
    }

    .stat-card {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #eaeaea;
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.05);
        border-color: var(--primary);
    }

    .stat-number {
        display: block;
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #666;
        font-weight: 600;
    }

    .feature-icon-box {
        width: 80px;
        height: 80px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }

    /* Image placeholder styling if no specific about image exists */
    .about-image-container {
        position: relative;
        border-radius: 30px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .about-logo-overlay {
        background: rgba(255, 255, 255, 0.9);
        padding: 40px;
        border-radius: 20px;
        backdrop-filter: blur(10px);
    }
</style>

<div class="about-section">
    <div class="container">

        <div class="row align-items-center mb-5 gx-5">
            <div class="col-lg-6 order-2 order-lg-1">
                <span class="text-uppercase text-primary fw-bold letter-spacing-2">Who We Are</span>
                <h2 class="about-heading">Driven by Quality,<br>Inspired by You.</h2>
                <p class="about-lead mb-4">
                    <?= nl2br(htmlspecialchars($company_desc)) ?>
                </p>
                <p class="text-muted">
                    At <strong><?= htmlspecialchars($supplier['company_name']) ?></strong>, we believe that shopping should be seamless and inspiring.
                    Established with a vision to innovate, we curate products that bring value to your everyday life.
                </p>

                <div class="mt-4">
                    <a href="?supplier_id=<?= $supplier_id ?>&page=contact" class="btn btn-dark rounded-pill px-4 py-2 fw-bold">Get in Touch</a>
                </div>
            </div>

            <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                <div class="about-image-container">
                    <div class="about-logo-overlay text-center">
                        <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                            alt="<?= htmlspecialchars($supplier['company_name']) ?>"
                            style="max-width: 150px; height: auto;">
                        <h5 class="mt-3 fw-bold mb-0"><?= htmlspecialchars($supplier['company_name']) ?></h5>
                        <small class="text-muted">Official Store</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 py-5 mt-4">
            <div class="col-12 text-center mb-4">
                <h3 class="fw-bold">Why Choose Us?</h3>
                <p class="text-muted">The core values that define our business</p>
            </div>

            <div class="col-md-4">
                <div class="stat-card">
                    <div class="feature-icon-box">
                        <lord-icon
                            src="https://cdn.lordicon.com/hjeefwhm.json"
                            trigger="hover"
                            colors="primary:#121331,secondary:<?= htmlspecialchars($shop_assets['primary_color'] ?? '#000') ?>"
                            style="width:50px;height:50px">
                        </lord-icon>
                    </div>
                    <h4>Premium Quality</h4>
                    <p class="text-muted mt-3">We carefully select and verify every product to ensure it meets our high standards of excellence.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card">
                    <div class="feature-icon-box">
                        <lord-icon
                            src="https://cdn.lordicon.com/cllunfud.json"
                            trigger="hover"
                            colors="primary:#121331,secondary:<?= htmlspecialchars($shop_assets['primary_color'] ?? '#000') ?>"
                            style="width:50px;height:50px">
                        </lord-icon>
                    </div>
                    <h4>Secure Shopping</h4>
                    <p class="text-muted mt-3">Your security is our priority. We utilize the latest technology to keep your data safe.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card">
                    <div class="feature-icon-box">
                        <lord-icon
                            src="https://cdn.lordicon.com/zpxybbhl.json"
                            trigger="hover"
                            colors="primary:#121331,secondary:<?= htmlspecialchars($shop_assets['primary_color'] ?? '#000') ?>"
                            style="width:50px;height:50px">
                        </lord-icon>
                    </div>
                    <h4>Fast Support</h4>
                    <p class="text-muted mt-3">Our dedicated support team is here to assist you with any questions or concerns.</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2 justify-content-center">
            <div class="col-6 col-md-3">
                <div class="text-center">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Satisfaction</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Support</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center">
                    <span class="stat-number">#1</span>
                    <span class="stat-label">Choice</span>
                </div>
            </div>
        </div>

    </div>
</div>