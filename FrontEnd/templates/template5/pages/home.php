<?php?>

<section class="hero-container <?= htmlspecialchars($supplier['description']) ?>">
    <div class="hero-content">
        <span class="category-title">
            <i class="fa-solid <?= htmlspecialchars($supplier['email']) ?>"></i>
            <?= htmlspecialchars($supplier['tags']) ?>
        </span>

        <h2 class="home" style="width:500px;">
            <?= htmlspecialchars($supplier['description']) ?>
        </h2>

        <br>

        <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="btn-shop-now">
            SHOP NOW
        </a>
    </div>

    <div class="hero-banner">
        <div class="banner-shape-wrapper">
            <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>"
                alt="<?= htmlspecialchars($supplier['company_name']) ?> banner" class="fashion-banner">
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


