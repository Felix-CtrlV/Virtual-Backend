<?php
// Ensure this file is accessed via index.php context
if (!isset($supplier)) {
    die("Access Denied");
}

$company_name = $supplier['company_name'] ?? 'BRAND';
$tagline = $supplier['tagline'] ?? 'Get in touch.';

// Fetch colors from shop_assets
$supplier_id = $supplier['supplier_id'] ?? 1;
$color_sql = "SELECT primary_color, secondary_color FROM shop_assets WHERE company_id = $company_id LIMIT 1";
$color_result = $conn->query($color_sql);
$primary_color = "#000000"; // Default
$secondary_color = "#ededed"; // Default

if ($color_result && $color_result->num_rows > 0) {
    $color_row = $color_result->fetch_assoc();
    $primary_color = $color_row['primary_color'];
    $secondary_color = $color_row['secondary_color'];
}
?>

<style>
    /* ============================================
       1. MODERN VARIABLES (CONSISTENT DESIGN SYSTEM)
       ============================================ */
    :root {
        --bg-color: <?= $secondary_color ?>;
        --card-bg: #141414;
        --text-main: <?= $primary_color ?>;
        --text-muted: #888888;
        --accent: <?= $primary_color ?>;
        --primary-color: <?= $primary_color ?>;
        --border-color: rgba(255, 255, 255, 0.1);
        --font-display: 'Inter', 'Helvetica Neue', sans-serif;
        --font-body: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
        --container-padding: clamp(20px, 5vw, 60px);
        --section-gap: clamp(30px, 6vw, 60px);
        --glow-primary: <?= $primary_color ?>40;
    }

    /* ============================================
       2. GLOBAL RESET & UTILITIES
       ============================================ */
    body {
        background: linear-gradient(135deg, #0A0A0A 0%, #1A1A1A 100%);
        color: var(--text-main);
        font-family: var(--font-body);
        overflow-x: hidden;
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    /* ============================================
       3. 3D ANIMATED HERO SECTION
       ============================================ */
    .contact-hero {
        position: relative;
        height: 70vh;
        min-height: 600px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #000000;
    }

    /* 3D Animated Grid Background */
    .hero-grid {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: grid;
        grid-template-columns: repeat(20, 1fr);
        grid-template-rows: repeat(20, 1fr);
        gap: 1px;
        opacity: 0.3;
    }

    .grid-cell {
        background: rgba(255, 255, 255, 0.05);
        transform: translateZ(0);
        animation: gridPulse 8s ease-in-out infinite;
        animation-delay: calc(var(--x) * 0.1s + var(--y) * 0.1s);
    }

    @keyframes gridPulse {
        0%, 100% { opacity: 0.1; }
        50% { opacity: 0.3; }
    }

    /* Floating 3D Shapes */
    .hero-shapes {
        position: absolute;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .shape {
        position: absolute;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        animation: floatShape 20s linear infinite;
    }

    .shape-1 {
        width: 300px;
        height: 300px;
        top: 20%;
        left: 10%;
        animation-delay: 0s;
        box-shadow: 0 0 100px rgba(128, 0, 128, 0.3);
    }

    .shape-2 {
        width: 200px;
        height: 200px;
        bottom: 30%;
        right: 15%;
        animation-delay: -5s;
        box-shadow: 0 0 80px rgba(0, 100, 255, 0.3);
    }

    .shape-3 {
        width: 150px;
        height: 150px;
        top: 50%;
        left: 50%;
        animation-delay: -10s;
        box-shadow: 0 0 60px rgba(255, 255, 255, 0.2);
    }

    @keyframes floatShape {
        0% {
            transform: translate(0, 0) rotate(0deg);
        }
        25% {
            transform: translate(50px, -30px) rotate(90deg);
        }
        50% {
            transform: translate(0, -60px) rotate(180deg);
        }
        75% {
            transform: translate(-50px, -30px) rotate(270deg);
        }
        100% {
            transform: translate(0, 0) rotate(360deg);
        }
    }

    /* Hero Content */
    .hero-content {
        position: relative;
        z-index: 10;
        text-align: center;
        max-width: 1200px;
        width: 100%;
        padding: 0 var(--container-padding);
    }

    /* SMALLER TITLE as requested */
    .contact-title {
        font-family: var(--font-display);
        font-size: clamp(2.5rem, 8vw, 4.5rem); /* Reduced from 3.5-7rem */
        font-weight: 800;
        text-transform: uppercase;
        line-height: 1;
        letter-spacing: -0.02em;
        margin: 0;
        color: #FFFFFF;
        opacity: 0;
        animation: titleReveal 1.5s var(--transition-smooth) forwards;
        text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        position: relative;
        display: inline-block;
    }

    .contact-title::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 25%;
        width: 50%;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
        opacity: 0;
        animation: lineReveal 1s var(--transition-smooth) 0.8s forwards;
    }

    @keyframes titleReveal {
        from {
            transform: translateY(50px) scale(0.9);
            opacity: 0;
            filter: blur(10px);
        }
        to {
            transform: translateY(0) scale(1);
            opacity: 1;
            filter: blur(0);
        }
    }

    @keyframes lineReveal {
        from {
            width: 0;
            opacity: 0;
        }
        to {
            width: 50%;
            opacity: 1;
        }
    }

    .contact-sub {
        font-size: clamp(1rem, 2vw, 1.2rem);
        letter-spacing: 0.15em;
        text-transform: uppercase;
        margin-top: 30px;
        color: rgba(255, 255, 255, 0.8);
        font-weight: 300;
        opacity: 0;
        animation: subtitleReveal 1.2s var(--transition-smooth) 0.5s forwards;
    }

    @keyframes subtitleReveal {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* ============================================
       4. ORIGINAL MARQUEE CONTAINER (as requested)
       ============================================ */
    .marquee-container {
        background: white;
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
       5. MAIN LAYOUT & GRID - ADJUSTED COLUMNS
       ============================================ */
    .contact-wrapper {
        padding: var(--section-gap) var(--container-padding);
        max-width: 1600px;
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }

    /* ADJUSTED GRID: Form gets more space, info cards smaller */
    .grid-layout {
        display: grid;
        grid-template-columns: 1fr 1.8fr; /* Adjusted ratio: 1:1.8 instead of 1:1.5 */
        gap: clamp(40px, 6vw, 80px);
        align-items: start;
    }

    /* ============================================
       6. INFO CARDS COLUMN - ADJUSTED TO FIT
       ============================================ */
    .info-column {
        display: flex;
        flex-direction: column;
        gap: clamp(20px, 3vw, 30px);
        perspective: 1000px;
    }

    .tilt-card {
        background: rgba(20, 20, 20, 0.7);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: clamp(25px, 3vw, 35px);
        position: relative;
        transform-style: preserve-3d;
        transform: rotateX(0) rotateY(0);
        transition: all 0.3s var(--transition-smooth);
        cursor: default;
        overflow: hidden;
        min-height: 180px; /* Slightly reduced */
        display: flex;
        align-items: center;
    }

    .tilt-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .tilt-card:hover {
        border-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-5px);
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.05);
    }

    .tilt-card:hover::before {
        opacity: 1;
    }

    .tilt-content {
        transform: translateZ(40px);
        position: relative;
        z-index: 2;
        width: 100%;
    }

    .card-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--text-muted);
        font-weight: 600;
        margin-bottom: 10px; /* Reduced */
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-label::before {
        content: '';
        width: 12px;
        height: 2px;
        background: var(--primary-color);
        border-radius: 1px;
    }

    .card-value {
        font-size: clamp(1.2rem, 1.8vw, 1.6rem); /* Slightly smaller */
        font-weight: 700;
        color: #FFFFFF;
        line-height: 1.3;
        margin: 5px 0;
    }

    .card-value a {
        color: inherit;
        text-decoration: none;
        transition: color 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .card-value a:hover {
        color: var(--primary-color);
    }

    .card-value a::after {
        content: '→';
        font-size: 1.2rem;
        transition: transform 0.3s;
    }

    .card-value a:hover::after {
        transform: translateX(5px);
    }

    /* Status Card */
    .status-card {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(20, 20, 20, 0.7) 100%);
        border-color: rgba(16, 185, 129, 0.2);
    }

    .status-card .card-label {
        color: #10b981;
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 12px;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        box-shadow: 0 0 15px rgba(16, 185, 129, 0.5);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* ============================================
       7. FORM COLUMN - ADJUSTED FOR MORE SPACE
       ============================================ */
    .form-column {
        background: rgba(20, 20, 20, 0.6);
        backdrop-filter: blur(30px);
        -webkit-backdrop-filter: blur(30px);
        padding: clamp(40px, 5vw, 70px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        overflow: hidden;
    }

    .form-column::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    }

    .form-header h2 {
        font-family: var(--font-display);
        font-size: clamp(2rem, 4vw, 3.5rem);
        font-weight: 800;
        margin-bottom: 15px;
        color: #FFFFFF;
        line-height: 1.1;
    }

    .form-header p {
        font-size: 1.1rem;
        color: var(--text-muted);
        line-height: 1.6;
        margin-bottom: 40px;
    }

    .form-group {
        margin-bottom: clamp(25px, 3vw, 35px);
        position: relative;
    }

    .form-label {
        position: absolute;
        left: 0;
        top: 0;
        font-size: 0.9rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        transition: all 0.3s;
        pointer-events: none;
        transform: translateY(0);
        opacity: 0.7;
    }

    .modern-input {
        width: 100%;
        background: transparent;
        border: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 25px 0 15px;
        font-size: clamp(1.1rem, 2vw, 1.4rem);
        color: #FFFFFF;
        font-family: var(--font-body);
        transition: all 0.3s;
        border-radius: 0;
        outline: none;
    }

    .modern-input:focus {
        border-bottom-color: var(--primary-color);
        padding-left: 10px;
    }

    .modern-input:focus + .form-label,
    .modern-input:not(:placeholder-shown) + .form-label {
        transform: translateY(-20px);
        font-size: 0.8rem;
        opacity: 1;
        color: var(--primary-color);
    }

    textarea.modern-input {
        min-height: 180px;
        resize: vertical;
        line-height: 1.6;
    }

    /* Modern Button */
    .submit-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        width: 100%;
        padding: clamp(20px, 3vw, 25px);
        background: linear-gradient(135deg, var(--primary-color) 0%, #000000 100%);
        color: #FFFFFF;
        border: none;
        border-radius: 12px;
        font-size: clamp(0.95rem, 1.5vw, 1.1rem);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        cursor: pointer;
        transition: all 0.4s var(--transition-smooth);
        overflow: hidden;
        margin-top: 20px;
    }

    .submit-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s;
    }

    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.4),
            0 0 0 1px rgba(255, 255, 255, 0.1);
    }

    .submit-btn:hover::before {
        left: 100%;
    }

    .submit-btn::after {
        content: '→';
        font-size: 1.3rem;
        transition: transform 0.3s;
    }

    .submit-btn:hover::after {
        transform: translateX(5px);
    }

    /* ============================================
       8. RESPONSIVE MEDIA QUERIES
       ============================================ */
    
    /* Large Tablet & Small Desktop */
    @media (max-width: 1200px) {
        .grid-layout {
            grid-template-columns: 1fr;
            gap: clamp(40px, 5vw, 60px);
        }
        
        .contact-hero {
            height: 60vh;
            min-height: 500px;
        }
    }

    /* Tablet */
    @media (max-width: 992px) {
        .contact-hero {
            height: 50vh;
            min-height: 400px;
        }
        
        .contact-title {
            font-size: clamp(2.2rem, 7vw, 3.5rem);
        }
        
        .marquee-item {
            font-size: 1.8rem;
            margin-right: 3rem;
        }
        
        .form-column {
            padding: clamp(30px, 4vw, 50px);
        }
    }

    /* Mobile */
    @media (max-width: 768px) {
        :root {
            --container-padding: 20px;
        }
        
        .contact-hero {
            height: 45vh;
            min-height: 350px;
        }
        
        .contact-title {
            font-size: clamp(1.8rem, 6vw, 2.8rem);
        }
        
        .contact-sub {
            font-size: 0.9rem;
            margin-top: 20px;
        }
        
        .marquee-container {
            padding: 0.8rem 0;
        }
        
        .marquee-item {
            font-size: 1.5rem;
            margin-right: 2.5rem;
        }
        
        .tilt-card {
            min-height: 160px;
            padding: 25px;
        }
        
        .card-value {
            font-size: 1.3rem;
        }
        
        .form-header h2 {
            font-size: clamp(1.7rem, 3.5vw, 2.5rem);
        }
        
        .modern-input {
            font-size: 1.1rem;
            padding: 20px 0 10px;
        }
        
        .submit-btn {
            padding: 18px;
        }
    }

    /* Small Mobile */
    @media (max-width: 576px) {
        .contact-hero {
            height: 40vh;
            min-height: 300px;
        }
        
        .contact-title {
            font-size: clamp(1.5rem, 5vw, 2.2rem);
        }
        
        .contact-sub {
            font-size: 0.8rem;
        }
        
        .marquee-item {
            font-size: 1.2rem;
            margin-right: 2rem;
        }
        
        .info-column {
            gap: 15px;
        }
        
        .tilt-card {
            padding: 20px;
            min-height: 140px;
        }
        
        .card-label {
            font-size: 0.75rem;
        }
        
        .card-value {
            font-size: 1.1rem;
        }
        
        .form-column {
            padding: 25px;
            border-radius: 20px;
        }
        
        .form-header h2 {
            font-size: 1.8rem;
        }
        
        .form-header p {
            font-size: 1rem;
            margin-bottom: 30px;
        }
        
        .modern-input {
            font-size: 1rem;
            padding: 18px 0 8px;
        }
        
        .submit-btn {
            padding: 16px;
        }
    }

    /* Extra Small Mobile & Disable Tilt */
    @media (max-width: 400px) {
        .contact-hero {
            height: 35vh;
            min-height: 250px;
        }
        
        .contact-title {
            font-size: clamp(1.3rem, 4vw, 1.8rem);
        }
        
        .tilt-card {
            transform-style: flat;
            min-height: 130px;
            padding: 18px;
        }
        
        .tilt-content {
            transform: none;
        }
        
        .modern-input:focus {
            padding-left: 5px;
        }
        
        /* Reduce animation intensity on very small screens */
        .shape {
            display: none;
        }
        
        .hero-grid {
            opacity: 0.1;
        }
    }

    /* ============================================
       9. REVEAL ANIMATIONS
       ============================================ */
    .reveal-on-scroll {
        opacity: 0;
        transform: translateY(40px);
        transition: all 0.8s var(--transition-smooth);
    }

    .reveal-on-scroll.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
</style>

<div class="contact-hero">
    <!-- 3D Animated Grid Background -->
    <div class="hero-grid" id="heroGrid"></div>
    
    <!-- Floating 3D Shapes -->
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <!-- Hero Content -->
    <div class="hero-content">
        <h1 class="contact-title">Contact</h1>
        <p class="contact-sub">Let's Build Something Great</p>
    </div>
</div>

<!-- ORIGINAL MARQUEE CONTAINER (as requested) -->
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

            <div class="tilt-card status-card reveal-on-scroll">
                <div class="tilt-content">
                    <span class="card-label">System Status</span>
                    <div class="card-value">All Systems Operational</div>
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <span style="color: #10b981; font-size: 0.9rem;">Response time: &lt; 2 hours</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-column reveal-on-scroll">
            <div class="form-header">
                <h2>Send a Message</h2>
                <p>Fill out the form below and our team will get back to you within 2 hours during business hours.</p>
            </div>

            <form id="contactForm" method="POST" action="">
                <div class="form-group">
                    <input type="text" id="subject" name="subject" class="modern-input" placeholder=" " required />
                    <label class="form-label" for="subject">Subject</label>
                </div>

                <div class="form-group">
                    <input type="email" id="email" name="email" class="modern-input" placeholder=" " required />
                    <label class="form-label" for="email">Your Email</label>
                </div>

                <div class="form-group">
                    <textarea id="message" name="message" class="modern-input" placeholder=" " style="min-height: 180px;" required></textarea>
                    <label class="form-label" for="message">Your Message</label>
                </div>

                <button type="submit" class="submit-btn">
                    Send Message
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Generate 3D grid cells
        const heroGrid = document.getElementById('heroGrid');
        if (heroGrid) {
            for (let i = 0; i < 400; i++) { // 20x20 grid
                const cell = document.createElement('div');
                cell.className = 'grid-cell';
                cell.style.setProperty('--x', i % 20);
                cell.style.setProperty('--y', Math.floor(i / 20));
                heroGrid.appendChild(cell);
            }
        }

        // 1. SCROLL REVEAL
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.05, rootMargin: "0px 0px -50px 0px" });

        document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));

        // 2. 3D TILT EFFECT
        const cards = document.querySelectorAll('.tilt-card');
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

        if (!isTouchDevice) {
            cards.forEach(card => {
                card.addEventListener('mousemove', handleHover);
                card.addEventListener('mouseleave', resetCard);
                card.addEventListener('mouseenter', () => {
                    card.style.transition = 'all 0.1s ease-out';
                });
            });
        }

        function handleHover(e) {
            const card = this;
            const cardRect = card.getBoundingClientRect();
            
            const x = e.clientX - cardRect.left;
            const y = e.clientY - cardRect.top;
            const centerX = cardRect.width / 2;
            const centerY = cardRect.height / 2;
            
            const rotateX = ((y - centerY) / centerY) * -5;
            const rotateY = ((x - centerX) / centerX) * 5;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
        }

        function resetCard() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
            this.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        }

        // 3. FORM SUBMISSION
        const form = document.getElementById('contactForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const btn = form.querySelector('.submit-btn');
                const originalText = btn.innerHTML;
                const originalBg = btn.style.background;
                
                // Show loading state
                btn.innerHTML = '<span style="display:flex;align-items:center;gap:10px;">Sending<span style="width:20px;height:20px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite"></span></span>';
                btn.disabled = true;
                btn.style.opacity = '0.8';
                
                // Add CSS for spinner
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
                
                // Simulate API call
                setTimeout(() => {
                    // Success state
                    btn.innerHTML = 'Message Sent ✓';
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    
                    // Reset form
                    form.reset();
                    
                    // Revert after 3 seconds
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = originalBg;
                    }, 3000);
                }, 2000);
            });
        }

        // 4. INPUT LABEL ANIMATION
        const inputs = document.querySelectorAll('.modern-input');
        inputs.forEach(input => {
            if (input.value) {
                input.nextElementSibling.style.transform = 'translateY(-20px)';
                input.nextElementSibling.style.fontSize = '0.8rem';
                input.nextElementSibling.style.opacity = '1';
            }
            
            input.addEventListener('focus', () => {
                input.nextElementSibling.style.color = 'var(--primary-color)';
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.nextElementSibling.style.color = 'var(--text-muted)';
                }
            });
        });
    });
</script>