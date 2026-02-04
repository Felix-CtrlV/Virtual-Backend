<section class="hero-container <?= htmlspecialchars($supplier['description']) ?>">
    <div class="hero-content">
        <span class="category-title">
            <i class="fa-solid <?= htmlspecialchars($supplier['email']) ?>"></i>
            <?= htmlspecialchars($supplier['tags']) ?>
        </span>

       <h2 class="home">
       <?= htmlspecialchars($supplier['description']) ?>
       </h2>

        <br>

        <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="btn-shop-now">
            SHOP NOW
        </a>
    </div>

    <div class="hero-banner">
        <div class="banner-shape-wrapper">


            <?php if ($shop_assets['template_type'] == 'video'): ?>
                <video class="fashion-banner" autoplay muted loop playsinline
                    src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1?>"></video>

            <?php else: ?>
                <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>" alt="Hero Banner" class="fashion-banner"
                    style="transform: scale(1.1);">
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="collection-section py-5">
    <div class="container">
        <div class="row align-items-center">

            <ul class="section-features mt-4">
                <?php foreach (($section['features'] ?? []) as $feature): ?>
                    <li>
                        <span class="check-icon"><i class="fa-solid fa-check"></i></span>
                        <?= htmlspecialchars($feature) ?>
                    </li>
                <?php endforeach; ?>
            </ul>


        </div>
    </div>
    </div>

    </div>
    </div>
</section>

<style>
@media (max-width: 991px) {
    .hero-container {
        display: flex !important;
        flex-direction: column !important; 
        text-align: center; 
        padding-top: 10px;
    }

    .hero-banner {
        width: 100% !important;
        margin-bottom: 20px !important; 
        order: 1; 
    }

    .hero-content {
        width: 100% !important;
        padding: 20px !important;
        order: 2; 
    }

    .banner-shape-wrapper {
        margin: 0 auto;
        max-width: 95%; 
        height: auto !important; 
        background: transparent !important; 
        clip-path: none !important; 
        -webkit-clip-path: none !important;
        border-radius: 20px; 
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .fashion-banner {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        transform: scale(1) !important;
        object-fit: contain; 
    }
}
</style>


    