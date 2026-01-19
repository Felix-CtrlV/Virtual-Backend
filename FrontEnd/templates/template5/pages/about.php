<style>
   
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Inter:wght@300;400;600&display=swap');

    .about-luxury-section {
        background-color:white;
        color: #2980b9;
        padding: 120px 0;
        overflow: hidden;
        font-family: 'Inter', sans-serif;
        letter-spacing:1px;
    }

    .about-label {
        color: #D4AF37;
        letter-spacing: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        display: block;
        margin-bottom: 25px;
    }

    .about-heading {
        font-family: 'Playfair Display', serif;
        font-size: 3.2rem;
        line-height: 1.1;
        margin-bottom: 35px;
        color:#2980b9;
    }

    .about-description {
        color: #b0b0b0;
        line-height: 1.9;
        font-size: 1.05rem;
        margin-bottom: 45px;
        max-width: 550px;
    }

    .stat-box {
        border-left: 2px solid #D4AF37;
        padding: 5px 0 5px 25px;
        margin-bottom: 30px;
    }

    .stat-number {
        font-family: 'Playfair Display', serif;
        font-size: 2.2rem;
        color:#2980b9;
        display: block;
        line-height: 1;
        margin-bottom: 5px;
    }

    .stat-text {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 2px;
        color: #777;
        font-weight: 600;
    }

    .about-image-stack {
        position: relative;
        padding: 20px;
    }

    .main-img {
        width: 100%;
        filter: grayscale(20%) contrast(110%);
        border: 1px solid rgba(212, 175, 55, 0.3);
        transition: transform 0.5s ease;
    }

    /*.main-img:hover {
        transform: scale(1.02);
    }*/

    .experience-badge {
        position: absolute;
        top: -30px;
        left: -10px;
        background:white;
        color:#2980b9;
        padding: 25px 15px;
        z-index: 4;
        font-family: 'Playfair Display', serif;
        font-weight: bold;
        text-align: center;
        line-height: 1;
        box-shadow: 10px 10px 30px rgba(0,0,0,0.5);
    }
</style>

<section class="about-luxury-section">
    <div class="container">
        <div class="row align-items-center">
            
            <div class="col-lg-6 mb-5 mb-lg-0 pe-lg-5"> 
                <h2 class="about-heading">
                   <?= htmlspecialchars($supplier['company_name']) ?><br>
    
                </h2>
                
            At <?= htmlspecialchars($supplier['tags'] ?? '') ?>  <strong>
        <?= htmlspecialchars($shop_assets['about'] ?? '') ?>  
    </strong> 
                <br>
                  <div class="row">
                    <div class="col-sm-6">
                        <br>
                        <div class="stat-box">
                            <span class="stat-number"><?= htmlspecialchars($supplier['tags']) ?></span>
                            <span class="stat-text"></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <br>
                        <div class="stat-box">
                            <span class="stat-number"><?= htmlspecialchars($supplier['description']) ?></span>
                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 px-lg-5">
                <div class="about-image-stack">
                    <div class="experience-badge">
                        <span style="font-size: 0.7rem; text-transform: uppercase;"><?= htmlspecialchars($supplier['tags']) ?></span><br>
                        <span style="font-size: 1.5rem;">2026</span>
                    </div>
                    
                    <?php if (!empty($banner4)): ?>
                        <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner4 ?>" 
                             alt="Luxury Craftsmanship" 
                             class="main-img img-fluid shadow-lg">
                    <?php endif; ?>
                    
                   <!-- <div style="position: absolute; bottom: -15px; left: -15px; width: 120px; height: 120px; border-bottom: 3px solid black; border-left: 3px solid black; z-index: 1;"></div>-->
                </div>
            </div>

        </div>
    </div>
</section>