<?php
// PHP Logic for Banner/Video
// $has_video = !empty($banner_video_url);
// Fallback texts if variables aren't set
$company_name = $supplier['company_name'] ?? 'BRAND';
$tagline = $supplier['tagline'] ?? 'Redefine Your Limits.';
?>

<style>
    /* --- 1. GLOBAL VARIABLES & RESET --- */
    :root {
        --bg-color: #0a0a0a;
        --text-main: #ffffff;
        --text-muted: #888888;
        --accent: #D4AF37;
        /* Gold/Premium accent or change to neon green for sport */
        --font-display: 'Helvetica Neue', 'Arial Black', sans-serif;
        --font-body: 'Helvetica', sans-serif;
        --transition-smooth: cubic-bezier(0.16, 1, 0.3, 1);
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: var(--font-body);
        overflow-x: hidden;
        /* Prevent horizontal scroll from animations */
    }

    /* Utility: Smooth Scroll Reveal Class */
    .reveal-on-scroll {
        opacity: 0;
        transform: translateY(50px);
        transition: all 1s var(--transition-smooth);
    }

    .reveal-on-scroll.is-visible {
        opacity: 1;
        transform: translateY(0);
    }



    /* --- 2. HERO SECTION (CINEMATIC) --- */
    .hero-section {
        position: relative;
        top: -65px;
        height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .hero-media {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 0;
        filter: brightness(0.6) contrast(1.1);
        /* Cinematic look */
    }

    .hero-content {
        z-index: 2;
        text-align: center;
        width: 100%;
        padding: 0 20px;
        mix-blend-mode: exclusion;
        /* Trendy blend effect */
    }

    .hero-title {
        font-family: var(--font-display);
        font-size: clamp(3rem, 10vw, 9rem);
        /* Massive responsive text */
        font-weight: 900;
        text-transform: uppercase;
        line-height: 0.9;
        letter-spacing: -0.04em;
        margin: 0;
        color: #fff;
        opacity: 0;
        animation: heroTextReveal 1.2s var(--transition-smooth) forwards;
    }

    .hero-sub {
        font-size: clamp(1rem, 2vw, 1.5rem);
        letter-spacing: 0.2em;
        text-transform: uppercase;
        margin-top: 20px;
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

    /* Scroll Down Indicator */
    .scroll-down {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 3;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {

        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateX(-50%) translateY(0);
        }

        40% {
            transform: translateX(-50%) translateY(-10px);
        }

        60% {
            transform: translateX(-50%) translateY(-5px);
        }
    }

    /* --- 3. INFINITE MARQUEE (TRENDY) --- */
    .marquee-container {
        background: var(--text-main);
        color: var(--bg-color);
        padding: 1.5rem 0;
        overflow: hidden;
        position: relative;
        top: -64px;
        white-space: nowrap;
        position: relative;
        z-index: 5;
    }

    .marquee-content {
        display: inline-block;
        animation: marquee 20s linear infinite;
    }

    .marquee-item {
        font-family: var(--font-display);
        font-size: 3rem;
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

    /* --- 4. BENTO GRID FEATURES --- */
    .bento-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        padding: 5% 5%;
    }

    .bento-card {
        background: #1a1a1a;
        padding: 40px;
        border-radius: 20px;
        /* Rounded corners like Apple/Nike UI */
        position: relative;
        overflow: hidden;
        transition: transform 0.5s var(--transition-smooth);
        border: 1px solid #333;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 350px;
    }

    .bento-card:hover {
        transform: scale(0.98);
        border-color: #fff;
    }

    .bento-card i {
        font-size: 3rem;
        margin-bottom: 20px;
        color: #fff;
    }

    .bento-card h3 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .bento-card p {
        color: var(--text-muted);
        font-size: 1.1rem;
        line-height: 1.6;
    }

    /* --- 5. LARGE TYPOGRAPHY CTA --- */
    .big-cta {
        padding: 100px 20px;
        text-align: center;
        background: #fff;
        color: #000;
    }

    .big-cta h2 {
        font-size: clamp(3rem, 8vw, 6rem);
        font-weight: 900;
        line-height: 0.9;
        text-transform: uppercase;
        margin-bottom: 40px;
    }

    .magnet-btn {
        display: inline-block;
        padding: 20px 60px;
        background: #000;
        color: #fff;
        border-radius: 50px;
        font-size: 1.2rem;
        font-weight: bold;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .magnet-btn:hover {
        background: #333;
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        color: #fff;
    }

    @media(max-width: 768px) {
        .marquee-item {
            font-size: 2rem;
        }
    }

    .p1 {
        font-size: 1.1rem;
        margin-bottom: 20px;
        color: var(--text-muted);
        font-weight: bold;
    }
</style>



<section class="hero-section">
    <?php if ($shop_assets['template_type'] == 'video'): ?>
        <video class="hero-media" autoplay muted loop playsinline src="../uploads/shops/<?= $supplier_id?>/<?= $shop_assets['banner'] ?>"></video>
    
    <?php else: ?>
        <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>" alt="Hero Banner" class="hero-media" style="transform: scale(1.1);">
    <?php endif; ?>

    <div class="hero-content">
        <h1 class="hero-title"><?= htmlspecialchars($company_name) ?></h1>
        <p class="hero-sub"><?= htmlspecialchars($tagline) ?></p>

        <div style="margin-top: 40px; opacity: 0; animation: heroTextReveal 1.2s ease 0.6s forwards; margin-right: 770px;">
            <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="magnet-btn" style="background: white; color: black;">
                Explore Collection
            </a>
        </div>
    </div>

    <div class="scroll-down text-white">
        <small>SCROLL</small><br>
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<div class="marquee-container">
    <div class="marquee-content">
        <span class="marquee-item">New Arrivals</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">Premium Quality</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">Global Shipping</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">New Arrivals</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">Premium Quality</span>
        <span class="marquee-item">•</span>
        <span class="marquee-item">Global Shipping</span>
        <span class="marquee-item">•</span>
    </div>
</div>

<section class="container-fluid py-5" style="background: var(--bg-color);">
    <div class="row align-items-center py-5">
        <div class="col-lg-6 px-lg-5 reveal-on-scroll">
            <span class="text-uppercase" style="color: var(--accent); letter-spacing: 2px; font-weight: bold;">Our Philosophy</span>
            <h2 class="display-3 fw-bold mt-3 mb-4 text-white">
                <?= $shop_assets['description'] ?? 'ENGINEERED FOR PERFECTION.' ?>
            </h2>
        </div>
        <div class="col-lg-5 text-muted lead reveal-on-scroll" style="font-weight: 300;">
            <p class="p1">
                <?= $supplier['description'] ?? "We don't just sell products. We curate experiences. Every stitch, every material, and every design choice is made with the athlete in mind." ?>
            </p>
            <p class="p1">Join a community of innovators and game-changers.</p>
        </div>
    </div>
</section>

<section class="container-fluid">
    <div class="row justify-content-center mb-5 reveal-on-scroll">
        <div class="col-12 text-center">
            <h2 class="text-white text-uppercase display-5 fw-bold">The Advantage</h2>
        </div>
    </div>

    <div class="bento-grid">
        <div class="bento-card reveal-on-scroll">
            <div>
                <i class="fas fa-shield-alt"></i>
                <h3>Unmatched Durability</h3>
            </div>
            <p>Built to withstand the toughest conditions. Our materials are stress-tested to ensure longevity.</p>
        </div>

        <div class="bento-card reveal-on-scroll" style="background: #222;">
            <div>
                <i class="fas fa-bolt"></i>
                <h3>Rapid Logistics</h3>
            </div>
            <p>From our warehouse to your door in record time. We partner with top-tier global couriers.</p>
        </div>

        <div class="bento-card reveal-on-scroll">
            <div>
                <i class="fas fa-fingerprint"></i>
                <h3>Unique Identity</h3>
            </div>
            <p>Stand out with limited edition releases and custom designs available only here.</p>
        </div>
    </div>
</section>

<section class="big-cta reveal-on-scroll">
    <div class="container">
        <p class="text-uppercase letter-spacing-2 mb-3 text-muted">Ready to start?</p>
        <h2>Create Your Legacy</h2>
        <a href="?supplier_id=<?= $supplier_id ?>&page=contact" class="magnet-btn">
            Get in Touch
        </a>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Simple Intersection Observer for Scroll Animations
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