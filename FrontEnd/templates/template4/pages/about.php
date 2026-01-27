<?php
// Ensure this file is accessed via index.php
if (!defined('DIR') && !isset($supplier)) {
    // optional security check
}

// Fallback description
$company_desc = !empty($supplier['description'])
    ? $supplier['description']
    : "Welcome to " . htmlspecialchars($supplier['company_name']) . ". We are dedicated to providing the best quality products and exceptional service to our customers. Our journey began with a simple mission: to make premium goods accessible to everyone.";

// Fallback logic for colors/names
$company_name = $supplier['company_name'] ?? 'BRAND';
$accent_color = $shop_assets['primary_color'] ?? '#D4AF37'; // Use DB color or Gold fallback
?>

<style>
    /* --- SHARED VARIABLES (Matches Home.php) --- */
    :root {
        --bg-color: #0a0a0a;
        --card-bg: #111111;
        --text-main: #ffffff;
        --text-muted: #888888;
        --accent: <?= $accent_color ?>;
        --font-display: 'Helvetica Neue', 'Arial Black', sans-serif;
        --font-body: 'Helvetica', sans-serif;
        --transition-smooth: cubic-bezier(0.16, 1, 0.3, 1);
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: var(--font-body);
        overflow-x: hidden;
    }

    /* --- ANIMATIONS --- */
    .reveal-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s var(--transition-smooth);
    }

    .reveal-on-scroll.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* --- TYPOGRAPHY --- */
    .display-header {
        font-family: var(--font-display);
        font-size: clamp(3rem, 6vw, 5rem);
        font-weight: 900;
        text-transform: uppercase;
        line-height: 0.9;
        margin-bottom: 30px;
        color: #fff;
    }

    .section-label {
        color: var(--accent);
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: bold;
        font-size: 0.9rem;
        display: block;
        margin-bottom: 15px;
    }

    /* --- LAYOUT SECTIONS --- */
    .about-header-section {
        padding: 120px 0 80px;
        position: relative;
    }

    /* --- SPLIT CONTENT --- */
    .story-text {
        font-size: 1.2rem;
        line-height: 1.7;
        color: var(--text-muted);
        font-weight: 300;
    }

    .story-text strong {
        color: #fff;
        font-weight: 600;
    }

    .about-image-wrapper {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #333;
    }

    .about-image-wrapper img {
        width: 100%;
        height: auto;
        display: block;
        filter: grayscale(100%) contrast(1.1);
        transition: filter 0.5s ease;
    }

    .about-image-wrapper:hover img {
        filter: grayscale(0%) contrast(1);
    }

    /* --- BENTO GRID (Values) --- */
    .values-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 50px;
    }

    .value-card {
        background: var(--card-bg);
        padding: 40px;
        border-radius: 20px;
        border: 1px solid #222;
        transition: transform 0.3s ease, border-color 0.3s ease;
        text-align: left;
    }

    .value-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent);
    }

    .value-card h4 {
        color: #fff;
        font-weight: 800;
        font-size: 1.5rem;
        margin-top: 20px;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .value-card p {
        color: var(--text-muted);
        font-size: 1rem;
        line-height: 1.5;
        margin: 0;
    }

    /* --- STATS STRIP --- */
    .stats-strip {
        border-top: 1px solid #222;
        border-bottom: 1px solid #222;
        padding: 60px 0;
        margin: 80px 0;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        display: block;
        font-family: var(--font-display);
        font-size: 3.5rem;
        color: #fff;
        line-height: 1;
    }

    .stat-label {
        color: var(--accent);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.85rem;
        margin-top: 10px;
        display: block;
    }

    /* --- BUTTONS --- */
    .magnet-btn {
        display: inline-block;
        padding: 15px 40px;
        background: #fff;
        color: #000;
        border-radius: 50px;
        font-weight: bold;
        text-transform: uppercase;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid #fff;
    }

    .magnet-btn:hover {
        background: transparent;
        color: #fff;
        transform: translateY(-3px);
    }

    .reactor-container {
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 500px;
        /* Tall canvas for 3D effect */
        background: #050505;
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid #222;
        box-shadow: inset 0 0 100px rgba(0, 0, 0, 0.9);
        perspective: 1000px;
        /* Essential for 3D depth */
    }

    /* Moving Cyber Grid Floor */
    .cyber-grid {
        position: absolute;
        width: 300%;
        height: 300%;
        top: -50%;
        left: -100%;
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        background-size: 50px 50px;
        transform: rotateX(70deg);
        /* Flattens it into a floor */
        animation: grid-scroll 10s linear infinite;
        z-index: 1;
        mask-image: radial-gradient(circle, rgba(0, 0, 0, 1) 0%, rgba(0, 0, 0, 0) 70%);
        /* Fade edges */
    }

    /* Glowing Orb Background */
    .glow-core {
        position: absolute;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, rgba(0, 0, 0, 0) 70%);
        border-radius: 50%;
        z-index: 2;
        animation: pulse-glow 4s ease-in-out infinite;
    }

    /* Rotating Rings */
    .orbit-ring {
        position: absolute;
        border-radius: 50%;
        border: 1px solid rgba(255, 255, 255, 0.08);
        z-index: 3;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.02);
    }

    .orbit-ring img {
        border-radius: 50%;
    }

    .ring-outer {
        width: 380px;
        height: 380px;
        border-left: 2px solid var(--accent);
        /* Uses your PHP accent color */
        animation: spin-3d 12s linear infinite;
    }

    .ring-middle {
        width: 280px;
        height: 280px;
        border: 1px dashed rgba(255, 255, 255, 0.15);
        animation: spin-reverse 20s linear infinite;
    }

    .ring-inner {
        width: 200px;
        height: 200px;
        border-top: 2px solid #fff;
        animation: spin-3d 6s linear infinite;
    }

    /* Floating Logo */
    .levitating-logo {
        position: relative;
        z-index: 10;
        max-width: 160px;
        height: auto;
        filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.5));
        animation: levitate 5s ease-in-out infinite;
    }



    /* Animation Keyframes */
    @keyframes grid-scroll {
        0% {
            transform: rotateX(70deg) translateY(0);
        }

        100% {
            transform: rotateX(70deg) translateY(50px);
        }
    }

    @keyframes spin-3d {
        0% {
            transform: rotate(0deg) rotateX(10deg);
        }

        100% {
            transform: rotate(360deg) rotateX(10deg);
        }
    }

    @keyframes spin-reverse {
        0% {
            transform: rotate(360deg);
        }

        100% {
            transform: rotate(0deg);
        }
    }

    @keyframes levitate {

        0%,
        100% {
            transform: translateY(0) scale(1);
        }

        50% {
            transform: translateY(-20px) scale(1.05);
        }
    }

    @keyframes pulse-glow {

        0%,
        100% {
            opacity: 0.5;
            transform: scale(1);
        }

        50% {
            opacity: 1;
            transform: scale(1.2);
        }
    }
</style>
</style>

<div class="container about-header-section">
    <div class="row align-items-center">
        <div class="col-lg-12 text-center reveal-on-scroll">
            <span class="section-label">Established to Innovate</span>
            <h1 class="display-header">Driven by Quality,<br>Inspired by You.</h1>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row align-items-center gx-5">
        <div class="col-lg-6 order-2 order-lg-1 reveal-on-scroll">
            <p class="story-text mb-4">
                <?= nl2br(htmlspecialchars($company_desc)) ?>
            </p>
            <p class="story-text">
                At <strong><?= htmlspecialchars($company_name) ?></strong>, we believe that shopping should be seamless and inspiring.
                We don't just sell products; we curate experiences that bring value to your everyday life.
            </p>

            <div class="mt-5">
                <a href="?supplier_id=<?= $supplier_id ?>&page=contact" class="magnet-btn">
                    Start a Conversation
                </a>
            </div>
        </div>

        <div class="col-lg-6 order-1 order-lg-2 mb-5 mb-lg-0 reveal-on-scroll">
            <div class="reactor-container">
                <div class="cyber-grid"></div>

                <div class="glow-core"></div>

                <div class="orbit-ring ring-outer"></div>
                <div class="orbit-ring ring-middle"></div>
                <div class="orbit-ring ring-inner"></div>

                <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                    alt="<?= htmlspecialchars($company_name) ?>" style="border-radius: 50%;"
                    class="levitating-logo">
            </div>
        </div>
    </div>
</div>
</div>

<div class="container py-5">
    <div class="row reveal-on-scroll">
        <div class="col-12 text-center mb-4">
            <h2 class="text-uppercase fw-bold text-white">Why Choose Us?</h2>
            <p style="color: gray;">The core principles that define our legacy.</p>
        </div>
    </div>

    <div class="values-grid reveal-on-scroll">
        <div class="value-card">
            <lord-icon
                src="https://cdn.lordicon.com/hjeefwhm.json"
                trigger="hover"
                colors="primary:#ffffff,secondary:<?= htmlspecialchars($accent_color) ?>"
                style="width:60px;height:60px">
            </lord-icon>
            <h4>Premium Quality</h4>
            <p>We rigorously verify every product. Only the exceptional makes it to our inventory, ensuring durability and style.</p>
        </div>

        <div class="value-card">
            <lord-icon
                src="https://cdn.lordicon.com/cllunfud.json"
                trigger="hover"
                colors="primary:#ffffff,secondary:<?= htmlspecialchars($accent_color) ?>"
                style="width:60px;height:60px">
            </lord-icon>
            <h4>Secure Tech</h4>
            <p>Your data is sacred. We utilize state-of-the-art encryption and security protocols for a worry-free experience.</p>
        </div>

        <div class="value-card">
            <lord-icon
                src="https://cdn.lordicon.com/zpxybbhl.json"
                trigger="hover"
                colors="primary:#ffffff,secondary:<?= htmlspecialchars($accent_color) ?>"
                style="width:60px;height:60px">
            </lord-icon>
            <h4>24/7 Dedicated</h4>
            <p>Questions don't sleep, and neither do we. Our expert support team is always on standby to assist you.</p>
        </div>
    </div>
</div>

<div class="stats-strip reveal-on-scroll">
    <div class="container">
        <div class="row">
            <div class="col-4 stat-item">
                <span class="stat-number">100%</span>
                <span class="stat-label">Satisfaction Rate</span>
            </div>
            <div class="col-4 stat-item">
                <span class="stat-number">24/7</span>
                <span class="stat-label">Support Access</span>
            </div>
            <div class="col-4 stat-item">
                <span class="stat-number">#1</span>
                <span class="stat-label">Market Choice</span>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5 mb-5 text-center reveal-on-scroll">
    <span class="section-label">Ready to join us?</span>
    <h2 class="display-header" style="font-size: clamp(2rem, 4vw, 3.5rem);">Experience the Difference</h2>
    <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="magnet-btn mt-3" style="background: var(--accent); border-color: var(--accent); color: #000;">
        View Collection
    </a>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Shared Intersection Observer for Scroll Animations
        const observerOptions = {
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, observerOptions);

        const elements = document.querySelectorAll('.reveal-on-scroll');
        elements.forEach(el => observer.observe(el));
    });
</script>