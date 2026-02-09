<style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Inter:wght@300;400;600&display=swap');

    .about-luxury-section {
        background-color: white;
        color: #2980b9;
        padding: 80px 0;
        overflow: hidden;
        font-family: 'Inter', sans-serif;
    }

    .about-heading {
        font-family: 'Playfair Display', serif;
        font-size: 3.5rem;
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: 30px;
        color: #2980b9;
        text-transform: uppercase;
    }

    .about-description {
        color: #2980b9;
        line-height: 1.6;
        font-size: 1.1rem;
        margin-bottom: 50px;
        text-align: justify;
    }

    /* Bottom Discover Section Layout */
    .discover-container {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        margin-top: 40px;
    }

    .info-item {
        border-left: 2px solid #D4AF37; /* Vertical gold line */
        padding-left: 20px;
        display: flex;
        align-items: center;
    }

    .brand-tag {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        font-weight: bold;
        color: #2980b9;
        text-transform: uppercase;
    }

    .discover-text {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        font-weight: bold;
        line-height: 1.2;
        color: #2980b9;
        max-width: 300px;
    }

    /* Image & Badge Styles */
    .about-image-stack {
        position: relative;
        padding: 20px;
    }

    .experience-badge {
        position: absolute;
        top: -10px;
        left: -10px;
        background: white;
        color: #2980b9;
        padding: 20px 15px;
        z-index: 4;
        font-family: 'Playfair Display', serif;
        font-weight: bold;
        text-align: center;
        box-shadow: 0px 10px 30px rgba(0,0,0,0.1);
        border: 1px solid #eee;
    }

    .main-img {
        width: 100%;
        height: auto;
        object-fit: cover;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .about-heading {
            font-size: 2.5rem;
        }
        .discover-text, .brand-tag {
            font-size: 1.4rem;
        }
        .about-luxury-section {
            padding: 40px 0;
        }
        .discover-container {
            flex-direction: column;
            gap: 20px;
        }
    }
</style>

<section class="about-luxury-section">
    <div class="container">
        <div class="row align-items-center">
            
            <div class="col-lg-6 mb-5 mb-lg-0 pe-lg-5"> 
                <h2 class="about-heading">
                    <?= htmlspecialchars($supplier['company_name']) ?>
                </h2>
                
                <div class="about-description">
                    At <strong><?= htmlspecialchars($supplier['company_name']) ?></strong>, 
                    <?= nl2br(htmlspecialchars($shop_assets['about'] ?? 'we believe that a watch is more than a tool for time—it is a legacy.')) ?>
                </div>

                <div class="discover-container">
                    <div class="info-item">
                        <span class="brand-tag">ROLEX</span>
                    </div>
                    <div class="info-item">
                        <span class="discover-text">Discover The Value Of Your Precious Hours Yourself</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="about-image-stack">
                    <div class="experience-badge">
                        <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px;">ROLEX</span><br>
                        <span style="font-size: 1.5rem;">2026</span>
                    </div>
                    
                    <?php if (!empty($banner4)): ?>
                        <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner4 ?>" 
                             alt="Luxury Craftsmanship" 
                             class="main-img img-fluid">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/600x400" class="main-img img-fluid" alt="Rolex">
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>