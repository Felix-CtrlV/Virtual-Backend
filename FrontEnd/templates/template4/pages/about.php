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
$accent_color = $shop_assets['primary_color'] ?? '#D4AF37';
?>

<style>
    /* --- MODERN DESIGN SYSTEM --- */
    :root {
        --bg-color: #0a0a0a;
        --card-bg: #111111;
        --card-bg-hover: #1a1a1a;
        --text-main: #ffffff;
        --text-muted: #888888;
        --accent: <?= $accent_color ?>;
        --accent-glow: <?= $accent_color ?>33;
        --font-display: 'Helvetica Neue', 'Arial Black', sans-serif;
        --font-body: 'Helvetica', sans-serif;
        --transition-smooth: cubic-bezier(0.16, 1, 0.3, 1);
        --gradient-1: linear-gradient(135deg, var(--accent) 0%, #ffffff 100%);
        --gradient-2: linear-gradient(135deg, #0a0a0a 0%, var(--accent) 100%);
        --glow-shadow: 0 0 30px var(--accent-glow);
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    /* --- PAGE TRANSITIONS --- */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }

    @keyframes gradientFlow {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    @keyframes particleFloat {
        0% { transform: translateY(0) rotate(0deg); opacity: 0; }
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
    }

    /* --- GLOBAL STYLES --- */
    html, body {
        width: 100%;
        overflow-x: hidden;
        background: var(--bg-color);
        color: var(--text-main);
        font-family: var(--font-body);
        scroll-behavior: smooth;
    }

    .page-entrance {
        animation: fadeInUp 1.2s var(--transition-smooth) forwards;
        opacity: 0;
    }

    .section-padding {
        padding: 120px 0;
    }

    /* --- HERO SECTION WITH PARTICLE BACKGROUND --- */
    .hero-section {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        background: radial-gradient(circle at 50% 50%, #1a1a1a 0%, #0a0a0a 100%);
        overflow: hidden;
    }

    .particles-container {
        position: absolute;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: var(--accent);
        border-radius: 50%;
        animation: particleFloat linear infinite;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero-title {
        font-family: var(--font-display);
        font-size: clamp(3rem, 8vw, 6rem);
        font-weight: 900;
        line-height: 1.1;
        margin-bottom: 1.5rem;
        background: var(--gradient-1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: gradientFlow 8s ease infinite;
        background-size: 200% 200%;
    }

    .hero-subtitle {
        font-size: 1.5rem;
        color: var(--text-muted);
        margin-bottom: 2.5rem;
        max-width: 600px;
    }

    /* --- 3D REACTOR ENHANCEMENT --- */
    .reactor-container-modern {
        position: relative;
        width: 100%;
        min-height: 600px;
        background: linear-gradient(45deg, #050505 0%, #111 100%);
        border-radius: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transform-style: preserve-3d;
        perspective: 1000px;
    }

    .holographic-grid {
        position: absolute;
        width: 300%;
        height: 300%;
        background: 
            linear-gradient(90deg, 
                transparent 49%, 
                rgba(255, 255, 255, 0.03) 50%, 
                transparent 51%
            ),
            linear-gradient(transparent 49%, 
                rgba(255, 255, 255, 0.03) 50%, 
                transparent 51%
            );
        background-size: 60px 60px;
        animation: gridMove 20s linear infinite;
        opacity: 0.3;
    }

    .energy-orb {
        position: absolute;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
        border-radius: 50%;
        filter: blur(40px);
        opacity: 0.4;
        animation: pulseOrb 4s ease-in-out infinite;
    }

    .orbital-ring {
        position: absolute;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 0 30px rgba(255, 255, 255, 0.05);
    }

    .ring-1 {
        width: 500px;
        height: 500px;
        animation: spin3D 15s linear infinite;
        border-left: 3px solid var(--accent);
        border-right: 3px solid transparent;
    }

    .ring-2 {
        width: 350px;
        height: 350px;
        animation: spin3DReverse 20s linear infinite;
        border-top: 2px dashed var(--accent);
    }

    .ring-3 {
        width: 200px;
        height: 200px;
        animation: spin3D 10s linear infinite;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: inset 0 0 20px var(--accent-glow);
    }

    .floating-logo {
        position: relative;
        z-index: 10;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        object-fit: cover;
        filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.8));
        animation: float 6s ease-in-out infinite;
        border: 3px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
    }

    /* --- STORY SECTION --- */
    .story-section {
        background: linear-gradient(180deg, #0a0a0a 0%, #111 100%);
        position: relative;
    }

    .story-card {
        background: rgba(17, 17, 17, 0.8);
        backdrop-filter: blur(20px);
        border-radius: 30px;
        padding: 60px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.6s var(--transition-smooth);
    }

    .story-card:hover {
        transform: translateY(-10px) scale(1.02);
        border-color: var(--accent);
        box-shadow: var(--glow-shadow);
    }

    .story-text {
        font-size: 1.2rem;
        line-height: 1.8;
        color: var(--text-muted);
        margin-bottom: 2rem;
    }

    .highlight-text {
        color: var(--text-main);
        font-weight: 600;
        background: linear-gradient(90deg, transparent, var(--accent-glow), transparent);
        padding: 0.5rem 1rem;
        border-radius: 10px;
        display: inline-block;
    }

    /* --- VALUES GRID ENHANCED --- */
    .values-grid-modern {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 60px;
    }

    .value-card-modern {
        background: linear-gradient(145deg, var(--card-bg), #0f0f0f);
        border-radius: 25px;
        padding: 50px 40px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.5s var(--transition-smooth);
        position: relative;
        overflow: hidden;
    }

    .value-card-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, var(--accent-glow), transparent);
        transition: left 0.7s var(--transition-smooth);
    }

    .value-card-modern:hover::before {
        left: 100%;
    }

    .value-card-modern:hover {
        transform: translateY(-15px) scale(1.03);
        border-color: var(--accent);
        box-shadow: var(--glow-shadow);
    }

    .value-icon {
        width: 80px;
        height: 80px;
        margin-bottom: 30px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.5s ease;
    }

    .value-card-modern:hover .value-icon {
        transform: rotateY(180deg) scale(1.2);
        background: var(--accent);
    }

    .value-card-modern h4 {
        color: var(--text-main);
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 20px;
        position: relative;
        display: inline-block;
    }

    .value-card-modern h4::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 50px;
        height: 3px;
        background: var(--accent);
        transition: width 0.5s ease;
    }

    .value-card-modern:hover h4::after {
        width: 100%;
    }

    /* --- STATS SECTION --- */
    .stats-section {
        background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
        position: relative;
        overflow: hidden;
    }

    .stat-item-modern {
        text-align: center;
        padding: 40px;
        position: relative;
    }

    .stat-number {
        font-family: var(--font-display);
        font-size: clamp(3rem, 5vw, 4.5rem);
        font-weight: 900;
        background: var(--gradient-1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: block;
        line-height: 1;
        margin-bottom: 15px;
    }

    .stat-glow {
        position: absolute;
        width: 100px;
        height: 100px;
        background: var(--accent);
        filter: blur(60px);
        opacity: 0.3;
        animation: float 6s ease-in-out infinite;
    }

    /* --- CTA SECTION --- */
    .cta-section {
        background: linear-gradient(135deg, #0a0a0a 0%, var(--accent) 200%);
        position: relative;
        overflow: hidden;
    }

    .cta-content {
        position: relative;
        z-index: 2;
    }

    /* --- BUTTONS --- */
    .btn-modern {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 18px 45px;
        background: linear-gradient(135deg, var(--accent) 0%, #ffffff 100%);
        color: #000;
        border: none;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-decoration: none;
        transition: all 0.4s var(--transition-smooth);
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
    }

    .btn-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transition: left 0.7s ease;
    }

    .btn-modern:hover::before {
        left: 100%;
    }

    .btn-modern:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 20px 40px rgba(212, 175, 55, 0.5);
    }

    /* --- ANIMATION KEYFRAMES --- */
    @keyframes gridMove {
        0% { transform: translateX(0) translateY(0); }
        100% { transform: translateX(-60px) translateY(-60px); }
    }

    @keyframes spin3D {
        0% { transform: rotateY(0deg) rotateX(20deg); }
        100% { transform: rotateY(360deg) rotateX(20deg); }
    }

    @keyframes spin3DReverse {
        0% { transform: rotateY(360deg) rotateX(-20deg); }
        100% { transform: rotateY(0deg) rotateX(-20deg); }
    }

    @keyframes pulseOrb {
        0%, 100% { opacity: 0.3; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(1.1); }
    }

    @keyframes shimmer {
        0% { background-position: -200% center; }
        100% { background-position: 200% center; }
    }

    /* --- RESPONSIVE DESIGN --- */
    @media (max-width: 1200px) {
        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
        }
        
        .reactor-container-modern {
            min-height: 500px;
        }
    }

    @media (max-width: 992px) {
        .section-padding {
            padding: 80px 0;
        }
        
        .story-card {
            padding: 40px;
        }
        
        .values-grid-modern {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .hero-section {
            min-height: 80vh;
        }
        
        .reactor-container-modern {
            min-height: 400px;
            border-radius: 25px;
        }
        
        .floating-logo {
            width: 150px;
            height: 150px;
        }
        
        .ring-1 {
            width: 350px;
            height: 350px;
        }
        
        .ring-2 {
            width: 250px;
            height: 250px;
        }
        
        .ring-3 {
            width: 150px;
            height: 150px;
        }
        
        .values-grid-modern {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .value-card-modern {
            padding: 40px 30px;
        }
        
        .btn-modern {
            padding: 15px 35px;
        }
    }

    @media (max-width: 576px) {
        .hero-title {
            font-size: clamp(2rem, 5vw, 3rem);
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
        }
        
        .reactor-container-modern {
            min-height: 300px;
        }
        
        .story-card {
            padding: 30px 20px;
        }
        
        .story-text {
            font-size: 1.1rem;
        }
        
        .value-icon {
            width: 60px;
            height: 60px;
        }
        
        .stat-item-modern {
            padding: 30px 20px;
        }
    }
</style>

<!-- Particle Background Generator -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Create particles
        const particlesContainer = document.querySelector('.particles-container');
        const particleCount = 50;
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            
            // Random properties
            const size = Math.random() * 4 + 2;
            const posX = Math.random() * 100;
            const duration = Math.random() * 20 + 10;
            const delay = Math.random() * 5;
            const opacity = Math.random() * 0.5 + 0.2;
            
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${posX}%`;
            particle.style.opacity = opacity;
            particle.style.animationDuration = `${duration}s`;
            particle.style.animationDelay = `${delay}s`;
            
            particlesContainer.appendChild(particle);
        }
        
        // Animate numbers on scroll
        const statNumbers = document.querySelectorAll('.stat-number');
        const observerOptions = {
            threshold: 0.5
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const number = entry.target;
                    const target = parseInt(number.textContent.replace(/%/, ''));
                    const suffix = number.textContent.includes('%') ? '%' : '';
                    const duration = 2000;
                    const step = target / (duration / 16);
                    let current = 0;
                    
                    const timer = setInterval(() => {
                        current += step;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        number.textContent = Math.floor(current) + suffix;
                    }, 16);
                    
                    observer.unobserve(number);
                }
            });
        }, observerOptions);
        
        statNumbers.forEach(number => observer.observe(number));
        
        // Add scroll reveal animation
        const revealElements = document.querySelectorAll('.page-entrance');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = (entries.indexOf(entry) * 0.2) + 's';
                    entry.target.style.opacity = '1';
                    entry.target.style.animation = 'fadeInUp 1s var(--transition-smooth) forwards';
                }
            });
        }, { threshold: 0.1 });
        
        revealElements.forEach(el => revealObserver.observe(el));
    });
</script>

<!-- HERO SECTION -->
<section class="hero-section section-padding">
    <div class="particles-container"></div>
    <div class="container hero-content">
        <div class="row justify-content-center text-center">
            <div class="col-lg-10">
                <span class="section-label" style="color: var(--accent); letter-spacing: 3px; font-size: 0.9rem; margin-bottom: 20px; display: block;">
                    ESTABLISHED TO INNOVATE
                </span>
                <h1 class="hero-title page-entrance">
                    Redefining Excellence<br>Through Innovation
                </h1>
                <p class="hero-subtitle page-entrance" style="animation-delay: 0.2s">
                    Where premium quality meets cutting-edge technology to deliver unparalleled experiences.
                </p>
                <a href="#story" class="btn-modern page-entrance" style="animation-delay: 0.4s">
                    Discover Our Story
                    <lord-icon
                        src="https://cdn.lordicon.com/msoeawqm.json"
                        trigger="loop"
                        colors="primary:#000000"
                        style="width:24px;height:24px">
                    </lord-icon>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- STORY SECTION -->
<section id="story" class="story-section section-padding">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 page-entrance">
                <div class="story-card">
                    <span class="section-label" style="color: var(--accent); margin-bottom: 20px;">OUR LEGACY</span>
                    <h2 class="hero-title" style="font-size: clamp(2rem, 4vw, 3.5rem); margin-bottom: 30px;">
                        Crafting Tomorrow's<br>Standards Today
                    </h2>
                    <p class="story-text">
                        <?= nl2br(htmlspecialchars($company_desc)) ?>
                    </p>
                    <p class="story-text">
                        At <span class="highlight-text"><?= htmlspecialchars($company_name) ?></span>, we believe that exceptional products should be paired with transformative experiences. Our commitment extends beyond transactions to building lasting relationships.
                    </p>
                    <div class="mt-4">
                        <a href="?supplier_id=<?= $supplier_id ?>&page=contact" class="btn-modern" style="background: transparent; border: 2px solid var(--accent); color: var(--accent);">
                            Connect With Us
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 page-entrance" style="animation-delay: 0.3s">
                <div class="reactor-container-modern">
                    <div class="holographic-grid"></div>
                    <div class="energy-orb"></div>
                    <div class="orbital-ring ring-1"></div>
                    <div class="orbital-ring ring-2"></div>
                    <div class="orbital-ring ring-3"></div>
                    <img 
                        src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                        alt="<?= htmlspecialchars($company_name) ?>"
                        class="floating-logo"
                        onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMTAwIiBjeT0iMTAwIiByPSI4MCIgZmlsbD0iIzMzMyIgc3Ryb2tlPSIjREFGQUYzNyIgc3Ryb2tlLXdpZHRoPSI0Ii8+PHRleHQgeD0iMTAwIiB5PSIxMTAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiNEREFGMzciIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIyNCI+PD90aXBwIHN1cHBsaWVyWydjb21wYW55X25hbWUnXT8+PC90ZXh0Pjwvc3ZnPg=='"
                    >
                </div>
            </div>
        </div>
    </div>
</section>

<!-- VALUES SECTION -->
<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8 page-entrance">
                <span class="section-label" style="color: var(--accent);">OUR PILLARS</span>
                <h2 class="hero-title" style="font-size: clamp(2rem, 4vw, 3.5rem);">
                    Built on Excellence,<br>Powered by Innovation
                </h2>
            </div>
        </div>
        
        <div class="values-grid-modern">
            <div class="value-card-modern page-entrance">
                <div class="value-icon">
                    <lord-icon
                        src="https://cdn.lordicon.com/hjeefwhm.json"
                        trigger="hover"
                        colors="primary:#ffffff"
                        style="width:40px;height:40px">
                    </lord-icon>
                </div>
                <h4>Uncompromising Quality</h4>
                <p class="story-text">Every product undergoes rigorous testing and quality assurance to ensure it meets our premium standards.</p>
            </div>
            
            <div class="value-card-modern page-entrance" style="animation-delay: 0.2s">
                <div class="value-icon">
                    <lord-icon
                        src="https://cdn.lordicon.com/cllunfud.json"
                        trigger="hover"
                        colors="primary:#ffffff"
                        style="width:40px;height:40px">
                    </lord-icon>
                </div>
                <h4>Advanced Security</h4>
                <p class="story-text">Military-grade encryption and real-time monitoring protect your data and transactions.</p>
            </div>
            
            <div class="value-card-modern page-entrance" style="animation-delay: 0.4s">
                <div class="value-icon">
                    <lord-icon
                        src="https://cdn.lordicon.com/zpxybbhl.json"
                        trigger="hover"
                        colors="primary:#ffffff"
                        style="width:40px;height:40px">
                    </lord-icon>
                </div>
                <h4>24/7 Excellence</h4>
                <p class="story-text">Round-the-clock support ensuring your experience remains flawless at all times.</p>
            </div>
        </div>
    </div>
</section>

<!-- STATS SECTION -->
<section class="stats-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col-md-4 stat-item-modern page-entrance">
                <div class="stat-glow" style="top: 20%; left: 30%;"></div>
                <span class="stat-number">100%</span>
                <span class="section-label" style="color: var(--accent); font-size: 1rem;">Client Satisfaction</span>
            </div>
            
            <div class="col-md-4 stat-item-modern page-entrance" style="animation-delay: 0.2s">
                <div class="stat-glow" style="top: 40%; right: 20%;"></div>
                <span class="stat-number">10K+</span>
                <span class="section-label" style="color: var(--accent); font-size: 1rem;">Products Delivered</span>
            </div>
            
            <div class="col-md-4 stat-item-modern page-entrance" style="animation-delay: 0.4s">
                <div class="stat-glow" style="bottom: 20%; left: 40%;"></div>
                <span class="stat-number">24/7</span>
                <span class="section-label" style="color: var(--accent); font-size: 1rem;">Support Available</span>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="cta-section section-padding">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8 page-entrance">
                <span class="section-label" style="color: #fff; font-size: 1rem; margin-bottom: 20px;">
                    READY TO EXPERIENCE THE FUTURE
                </span>
                <h2 class="hero-title" style="font-size: clamp(2.5rem, 5vw, 4rem); margin-bottom: 30px;">
                    Join the Revolution
                </h2>
                <p class="hero-subtitle" style="color: rgba(255,255,255,0.8); margin-bottom: 40px;">
                    Discover products that redefine excellence and service that sets new standards.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="btn-modern">
                        Explore Collection
                        <lord-icon
                            src="https://cdn.lordicon.com/udbbfuld.json"
                            trigger="loop"
                            colors="primary:#000000"
                            style="width:24px;height:24px">
                        </lord-icon>
                    </a>
                    <a href="?supplier_id=<?= $supplier_id ?>&page=contact" class="btn-modern" style="background: transparent; border: 2px solid #fff; color: #fff;">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- LordIcon Script -->
<script src="https://cdn.lordicon.com/lordicon.js"></script>