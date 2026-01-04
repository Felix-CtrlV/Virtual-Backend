<?php
// Ensure this file is accessed via index.php context
if (!isset($supplier)) {
    die("Access Denied");
}

$company_name = $supplier['company_name'] ?? 'BRAND';
$tagline = $supplier['tagline'] ?? 'Get in touch.';
?>

<style>
    /* ============================================
       1. SHARED VARIABLES (SYNCED WITH HOME.PHP)
       ============================================ */
    :root {
        --bg-color: #0a0a0a;
        --card-bg: #141414;
        --text-main: #ffffff;
        --text-muted: #888888;
        --accent: #D4AF37;
        /* Gold/Premium accent */
        --border-color: #333333;
        --font-display: 'Helvetica Neue', 'Arial Black', sans-serif;
        --font-body: 'Helvetica', sans-serif;
        --transition-smooth: cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* ============================================
       2. GLOBAL RESET & UTILITIES
       ============================================ */
    body {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: var(--font-body);
        overflow-x: hidden;
    }

    * {
        box-sizing: border-box;
    }

    /* Scroll Reveal Animation */
    .reveal-on-scroll {
        opacity: 0;
        transform: translateY(50px);
        transition: all 1s var(--transition-smooth);
    }

    .reveal-on-scroll.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* ============================================
       3. HERO SECTION (MATCHING HOME.PHP)
       ============================================ */
    .contact-hero {
        position: relative;
        height: 50vh;
        min-height: 400px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle at 50% 50%, #1a1a1a 0%, #000000 100%);
        overflow: hidden;
        border-bottom: 1px solid #222;
    }

    /* Abstract Background Noise/Grain */
    .contact-hero::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
        opacity: 0.4;
        pointer-events: none;
    }

    .contact-title {
        font-family: var(--font-display);
        font-size: clamp(4rem, 12vw, 9rem);
        font-weight: 900;
        text-transform: uppercase;
        line-height: 0.9;
        letter-spacing: -0.04em;
        margin: 0;
        color: #fff;
        z-index: 2;
        text-align: center;
        opacity: 0;
        animation: heroTextReveal 1.2s var(--transition-smooth) forwards;
    }

    .contact-sub {
        font-size: clamp(1rem, 2vw, 1.2rem);
        letter-spacing: 0.2em;
        text-transform: uppercase;
        margin-top: 20px;
        color: var(--accent);
        z-index: 2;
        opacity: 0;
        animation: heroTextReveal 1.2s var(--transition-smooth) 0.3s forwards;
    }

    @keyframes heroTextReveal {
        from {
            transform: translateY(100px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* ============================================
       4. INFINITE MARQUEE (IMPORTED FROM HOME)
       ============================================ */
    .marquee-container {
        background: var(--text-main);
        color: var(--bg-color);
        padding: 1rem 0;
        overflow: hidden;
        white-space: nowrap;
        position: relative;
        z-index: 5;
        border-bottom: 1px solid #000;
    }

    .marquee-content {
        display: inline-block;
        animation: marquee 20s linear infinite;
    }

    .marquee-item {
        font-family: var(--font-display);
        font-size: 2rem;
        font-weight: 900;
        text-transform: uppercase;
        margin-right: 4rem;
    }

    @keyframes marquee {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }

    /* ============================================
       5. MAIN GRID LAYOUT
       ============================================ */
    .contact-wrapper {
        padding: 80px 20px;
        max-width: 1600px;
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }

    .grid-layout {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 60px;
        align-items: start;
    }

    @media (max-width: 992px) {
        .grid-layout {
            grid-template-columns: 1fr;
            gap: 60px;
        }
    }

    /* ============================================
       6. TILT CARDS (HOVER ANIMATION PRESERVED)
       ============================================ */
    .info-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
        perspective: 1000px;
        /* REQUIRED FOR 3D EFFECT */
    }

    .tilt-card {
        background: #111;
        border: 1px solid #222;
        border-radius: 12px;
        padding: 40px;
        position: relative;
        transform-style: preserve-3d;
        /* REQUIRED FOR 3D EFFECT */
        transform: rotateX(0) rotateY(0);
        transition: transform 0.1s ease-out;
        /* SNAP ANIMATION */
        cursor: default;
        overflow: hidden;
    }

    /* Inner Glare Effect */
    .tilt-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(125deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 60%);
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
    }

    .tilt-card:hover {
        border-color: #444;
    }

    .tilt-card:hover::after {
        opacity: 1;
    }

    .tilt-content {
        transform: translateZ(30px);
        /* POP EFFECT */
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .card-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--accent);
        font-weight: bold;
    }

    .card-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
    }

    .card-value a {
        color: #fff;
        text-decoration: none;
        transition: 0.3s;
    }

    .card-value a:hover {
        color: var(--accent);
    }

    /* ============================================
       7. MODERN FORM (BENTO/GLASS STYLE)
       ============================================ */
    .form-column {
        background: rgba(20, 20, 20, 0.6);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        padding: 60px;
        border-radius: 20px;
        border: 1px solid #333;
        position: relative;
    }

    .form-header h2 {
        font-family: var(--font-display);
        font-size: 3rem;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .minimal-input {
        width: 100%;
        background: transparent;
        border: none;
        border-bottom: 1px solid #333;
        padding: 25px 0;
        font-size: 1.5rem;
        /* Larger typing font */
        color: #fff;
        font-family: var(--font-display);
        transition: all 0.3s ease;
        border-radius: 0;
    }

    .minimal-input:focus {
        outline: none;
        border-bottom-color: var(--accent);
        padding-left: 20px;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0.03), transparent);
    }

    .minimal-input::placeholder {
        color: #444;
        font-family: var(--font-body);
        font-size: 1.2rem;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    /* Modern Button (Matches Home) */
    .magnet-btn {
        display: inline-block;
        padding: 25px 60px;
        background: #fff;
        color: #000;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 900;
        text-transform: uppercase;
        text-decoration: none;
        border: none;
        cursor: pointer;
        width: 100%;
        margin-top: 40px;
        transition: all 0.3s var(--transition-smooth);
    }

    .magnet-btn:hover {
        background: var(--accent);
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    }

    @media (max-width: 768px) {
        .form-column {
            padding: 30px;
        }

        .minimal-input {
            font-size: 1.2rem;
        }
    }
</style>

<div class="contact-hero">
    <h1 class="contact-title">Contact</h1>
    <p class="contact-sub">Let's Build something great</p>
</div>

<div class="marquee-container">
    <div class="marquee-content">
        <span class="marquee-item">Customer Support</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">Custom Inquiries</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">Global Partnerships</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">24/7 Assistance</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">Customer Support</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">Custom Inquiries</span>
    </div>
</div>

<div class="contact-wrapper">
    <div class="grid-layout">

        <div class="info-column">

            <?php if (!empty($supplier['email'])): ?>
                <div class="tilt-card reveal-on-scroll">
                    <div class="tilt-content">
                        <span class="card-label">Email Us</span>
                        <div class="card-value">
                            <a href="mailto:<?= htmlspecialchars($supplier['email']) ?>">
                                <?= htmlspecialchars($supplier['email']) ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($supplier['phone'])): ?>
                <div class="tilt-card reveal-on-scroll">
                    <div class="tilt-content">
                        <span class="card-label">Call Us</span>
                        <div class="card-value">
                            <a href="tel:<?= htmlspecialchars($supplier['phone']) ?>">
                                <?= htmlspecialchars($supplier['phone']) ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($supplier['address'])): ?>
                <div class="tilt-card reveal-on-scroll">
                    <div class="tilt-content">
                        <span class="card-label">Visit Us</span>
                        <div class="card-value">
                            <?= htmlspecialchars($supplier['address']) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="tilt-card reveal-on-scroll" style="background: linear-gradient(135deg, #111, #0a0a0a);">
                <div class="tilt-content">
                    <span class="card-label" style="color: #10b981;">Live Status</span>
                    <div class="card-value" style="font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                        <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981;"></span>
                        Systems Online
                    </div>
                </div>
            </div>
        </div>

        <div class="form-column reveal-on-scroll">
            <div class="form-header">
                <h2>Send a Message</h2>
                <p style="color: #666;">Fill out the form below and we will get back to you shortly.</p>
            </div>

            <form id="contactForm" method="POST" action="">
                <div class="form-group">
                    <input type="text" id="subject" name="subject" class="minimal-input" placeholder="What is this regarding?" required />
                </div>

                <div class="form-group">
                    <textarea id="message" name="message" class="minimal-input" placeholder="Your Message..." style="min-height: 150px; resize: vertical;" required></textarea>
                </div>

                <button type="submit" class="magnet-btn">Submit Request</button>
            </form>
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        // 1. SCROLL REVEAL (Consistent with Home.php)
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));


        // 2. 3D TILT LOGIC (PRESERVED AS REQUESTED)
        const cards = document.querySelectorAll('.tilt-card');

        cards.forEach(card => {
            card.addEventListener('mousemove', handleHover);
            card.addEventListener('mouseleave', resetCard);
        });

        function handleHover(e) {
            const card = this;
            const cardRect = card.getBoundingClientRect();

            // Math for 3D rotation
            const x = e.clientX - cardRect.left;
            const y = e.clientY - cardRect.top;
            const centerX = cardRect.width / 2;
            const centerY = cardRect.height / 2;

            const rotateX = ((y - centerY) / centerY) * -10; // Max 10deg rotation
            const rotateY = ((x - centerX) / centerX) * 10;

            // Apply
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
        }

        function resetCard() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
        }

        // 3. FORM MOCKUP
        const form = document.getElementById('contactForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = form.querySelector('.magnet-btn');
                const originalText = btn.textContent;

                btn.textContent = 'Sending...';
                btn.style.opacity = '0.8';

                setTimeout(() => {
                    btn.textContent = 'Message Sent';
                    btn.style.background = '#10b981'; // Green
                    btn.style.color = '#fff';
                    btn.style.opacity = '1';
                    form.reset();

                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.style.background = '#fff';
                        btn.style.color = '#000';
                    }, 3000);
                }, 1500);
            });
        }
    });
</script>