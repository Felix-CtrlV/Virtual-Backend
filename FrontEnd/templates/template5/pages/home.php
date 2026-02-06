<?php
// PHP Defaults
$heroTitle = $supplier['description'] ?? 'Absolute Precision';
$tags = $supplier['tags'] ?? 'Luxury / Swiss / Automatic';
$features = $section['features'] ?? ['Sapphire Crystal', 'Chronometer Certified', 'Water Resistant'];
?>

<div class="swiss-wrapper">
    
    <nav class="top-nav fade-in">
        <span class="nav-brand"><?=strtoupper(htmlspecialchars($tags))?></span>
    </nav>

    <section class="hero-editorial">
        <div class="container-fluid p-0">
            <div class="row g-0 align-items-center">
                
                <div class="col-lg-5 p-5 position-relative z-2">
                    <div class="text-mask">
                        <span class="meta-tag reveal-text"><?= strtoupper(htmlspecialchars($tags)) ?></span>
                    </div>

                    <div class="title-wrapper">
                        <h1 class="editorial-title">
                            <?= htmlspecialchars($heroTitle) ?>
                        </h1>
                    </div>

                    <p class="editorial-desc reveal-text-delay">
                        <?=htmlspecialchars($shop_assets['description'] ?? '')?>
                    </p>

                    <div class="btn-group-custom reveal-text-delay">
                        <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="btn-magnetic">
                            <span class="btn-text">Discover Collection</span>
                            <span class="btn-circle"><i class="fa-solid fa-arrow-right"></i></span>
                        </a>
                    </div>
                </div>

                <div class="col-lg-7 position-relative overflow-hidden hero-image-col">
                    <div class="image-reveal-curtain"></div>
                    
                    <div class="hero-media-wrapper parallax-target">
                        <?php if (isset($shop_assets['template_type']) && $shop_assets['template_type'] == 'video'): ?>
                            <video class="hero-media" autoplay muted loop playsinline
                                src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>"></video>
                        <?php else: ?>
                            <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>" 
                                 alt="Watch Banner" class="hero-media">
                        <?php endif; ?>
                    </div>

                    <div class="spec-card">
                        <div class="spec-header">
                            <span><?=htmlspecialchars($shop_assets['description'] ?? '')?></span>
                            <i class="fa-solid fa-gears"></i>
                        </div>
                        <div class="spec-body">
                            <h3><?=htmlspecialchars(string:$shop_assets['description'])?></h3>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <div class="marquee-strip">
        <div class="track">
            <div class="content">
                &nbsp;TIMELESS ELEGANCE — SWISS MADE — PRECISION ENGINEERING — 
                TIMELESS ELEGANCE — SWISS MADE — PRECISION ENGINEERING —
            </div>
        </div>
    </div>

    <section class="features-minimal">
        <div class="container">
            <div class="row">
                <?php foreach ($features as $index => $feature): ?>
                    <div class="col-md-4 feature-col">
                        <div class="minimal-feature">
                            <span class="f-num">0<?= $index + 1 ?></span>
                            <h4 class="f-title"><?= htmlspecialchars($feature) ?></h4>
                            <span class="f-line"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<style>
/* --- Fonts --- */
@import url('https://fonts.googleapis.com/css2?family=Archivo:wght@300;400;700;900&family=Cinzel:wght@400;600&display=swap');

:root {
    --sw-dark: #111111;
    --sw-light: #f4f4f4;
    --sw-grey: #888;
    --sw-accent: #cd3e32; /* Subtle Swiss Red Accent */
    --font-head: 'Archivo', sans-serif;
    --font-serif: 'Cinzel', serif;
}

body {
    background-color: var(--sw-light);
    color: var(--sw-dark);
    font-family: var(--font-head);
    overflow-x: hidden;
}

.swiss-wrapper {
    background: #fff;
    position: relative;
}

/* --- Navigation Stub --- */
.top-nav {
    display: flex;
    justify-content: space-between;
    padding: 20px 40px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    font-weight: 700;
    letter-spacing: 1px;
}
.nav-tagline { color: var(--sw-grey); font-weight: 400; }

/* --- Hero Section --- */
.hero-editorial {
    min-height: 85vh;
    display: flex;
    align-items: center;
    overflow: hidden;
}

.meta-tag {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 3px;
    color: var(--sw-accent);
    margin-bottom: 20px;
}

.editorial-title {
    font-size: clamp(3rem, 5vw, 5rem);
    font-weight: 900;
    line-height: 0.9;
    letter-spacing: -2px;
    text-transform: uppercase;
    color: var(--sw-dark);
    margin-bottom: 30px;
    position: relative;
    z-index: 5;
    mix-blend-mode: exclusion; /* Cool overlap effect if image slides under */
}

.editorial-desc {
    font-size: 1.1rem;
    color: #555;
    max-width: 400px;
    margin-bottom: 40px;
    line-height: 1.6;
}

/* --- Image & Animations --- */
.hero-image-col {
    height: 85vh;
    background: #e0e0e0;
}

.hero-media-wrapper {
    width: 100%;
    height: 100%;
    transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.hero-media {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scale(1.1); /* Slight zoom for parallax feel */
    animation: slowZoom 20s infinite alternate;
}

/* Reveal Curtain Animation */
.image-reveal-curtain {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: var(--sw-dark);
    z-index: 10;
    animation: revealImage 1.2s cubic-bezier(0.77, 0, 0.175, 1) forwards;
}

/* Spec Card Floating */
.spec-card {
    position: absolute;
    bottom: 0;
    left: 0;
    background: white;
    padding: 30px;
    width: 250px;
    z-index: 5;
    box-shadow: 10px -10px 30px rgba(0,0,0,0.05);
}
.spec-header {
    display: flex; justify-content: space-between; color: var(--sw-grey);
    font-size: 0.8rem; text-transform: uppercase; margin-bottom: 10px;
}
.spec-body h3 { margin: 0; font-size: 1.2rem; font-weight: 700; }
.spec-body p { margin: 0; font-size: 0.8rem; color: #666; }

/* --- Magnetic Button --- */
.btn-magnetic {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    color: var(--sw-dark);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    group: hover;
}

.btn-circle {
    width: 50px; height: 50px;
    border: 1px solid var(--sw-dark);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 15px;
    transition: 0.3s all ease;
}

.btn-magnetic:hover .btn-circle {
    background: var(--sw-dark);
    color: white;
    transform: scale(1.1);
}

/* --- Marquee Strip --- */
.marquee-strip {
    background: var(--sw-dark);
    color: white;
    padding: 15px 0;
    overflow: hidden;
    white-space: nowrap;
}
.marquee-strip .track {
    display: inline-block;
    animation: marquee 20s linear infinite;
}
.marquee-strip .content {
    font-family: var(--font-head);
    font-weight: 700;
    font-size: 1.2rem;
    letter-spacing: 4px;
}

/* --- Features Minimal --- */
.features-minimal {
    padding: 80px 0;
    background: white;
}
.minimal-feature {
    padding: 20px;
    position: relative;
    transition: 0.3s;
}
.f-num {
    display: block;
    font-family: var(--font-serif);
    font-size: 3rem;
    color: #eee;
    font-weight: 700;
    line-height: 1;
    margin-bottom: -15px;
    position: relative;
    z-index: 1;
}
.f-title {
    position: relative;
    z-index: 2;
    font-weight: 700;
    font-size: 1.25rem;
    margin-bottom: 15px;
}
.f-line {
    display: block;
    width: 40px;
    height: 2px;
    background: var(--sw-accent);
    transition: 0.3s width;
}
.minimal-feature:hover .f-line { width: 100%; }

/* --- Keyframe Animations --- */
@keyframes revealImage {
    0% { transform: translateY(0); }
    100% { transform: translateY(-100%); }
}

@keyframes slowZoom {
    0% { transform: scale(1); }
    100% { transform: scale(1.15); }
}

@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* Reveal Text Helper */
.reveal-text {
    animation: fadeInUp 0.8s ease forwards;
    opacity: 0; transform: translateY(20px);
}
.reveal-text-delay {
    animation: fadeInUp 0.8s ease forwards 0.3s;
    opacity: 0; transform: translateY(20px);
}
@keyframes fadeInUp {
    to { opacity: 1; transform: translateY(0); }
}

/* Mobile Tweaks */
@media (max-width: 991px) {
    .editorial-title { font-size: 3.5rem; }
    .hero-image-col { height: 50vh; order: -1; }
    .spec-card { display: none; }
    .hero-editorial { flex-direction: column; }
}
</style>