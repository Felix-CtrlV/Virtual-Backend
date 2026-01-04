<?php
// products.php - Modern "Carved" Dark Aesthetic
// Ensure access context
if (!isset($supplier_id)) {
    die("Access Denied");
}

// Check for category filter
$category_filter = isset($_GET['category_id']) ? $_GET['category_id'] : 'all';
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
        --border-color: #2a2a2a;
        --font-display: 'Helvetica Neue', 'Arial Black', sans-serif;
        --font-body: 'Helvetica', sans-serif;
        --transition-smooth: cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* ============================================
       2. PAGE LAYOUT & HEADER
       ============================================ */
    .products-page {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: var(--font-body);
        min-height: 100vh;
        overflow-x: hidden;
    }

    .products-hero {
        padding: 120px 0 60px;
        text-align: center;
        position: relative;
    }

    .products-title {
        font-family: var(--font-display);
        font-size: clamp(3rem, 10vw, 8rem);
        text-transform: uppercase;
        font-weight: 900;
        letter-spacing: -0.04em;
        line-height: 0.9;
        margin-bottom: 40px;
        opacity: 0;
        animation: fadeUp 1s var(--transition-smooth) forwards;
    }

    @keyframes fadeUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* ============================================
       3. CATEGORY PILLS (MAGNETIC STYLE)
       ============================================ */
    .filter-container {
        display: flex;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 80px;
        opacity: 0;
        animation: fadeUp 1s var(--transition-smooth) 0.2s forwards;
    }

    .category-btn {
        background: transparent;
        border: 1px solid #333;
        color: #666;
        padding: 12px 30px;
        border-radius: 50px;
        text-transform: uppercase;
        font-weight: 700;
        font-size: 0.85rem;
        letter-spacing: 1px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .category-btn:hover,
    .category-btn.active {
        background: #fff;
        color: #000;
        border-color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
    }

    /* ============================================
       4. CARVED PRODUCT CARD (3D TILT + NIKE STYLE)
       ============================================ */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 40px;
        padding: 0 5%;
        margin-bottom: 100px;
    }

    .product-card-wrapper {
        perspective: 1000px;
        /* Essential for 3D */
    }

    .product-card {
        background-color: var(--card-bg);
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        transition: transform 0.1s ease-out;
        /* Snappy tilt */
        transform-style: preserve-3d;
        border: 1px solid var(--border-color);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    /* Inner Glare */
    .product-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(125deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 60%);
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
        z-index: 5;
    }

    .product-card:hover::after {
        opacity: 1;
    }

    .product-card:hover {
        border-color: #444;
    }

    /* Image Area */
    .card-image-box {
        position: relative;
        width: 100%;
        aspect-ratio: 1 / 1;
        /* Square like Nike */
        background: #1a1a1a;
        overflow: hidden;
        transform: translateZ(20px);
        /* Pop out in 3D */
    }

    .product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s var(--transition-smooth), filter 0.3s ease;
        mix-blend-mode: normal;
    }

    /* Hover Zoom */
    .product-card:hover .product-img {
        transform: scale(1.08);
    }

    /* Info Area */
    .card-info {
        padding: 25px;
        transform: translateZ(30px);
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        justify-content: space-between;
    }

    .p-category {
        color: var(--accent);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .p-title {
        color: #fff;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    .p-footer {
        display: flex;
        justify-content: space-between;
        align-items: end;
        margin-top: auto;
    }

    .p-price {
        font-family: var(--font-display);
        font-size: 1.25rem;
        color: #fff;
    }

    /* Colorway Dots (Nike Style) */
    .color-options {
        display: flex;
        gap: 8px;
    }

    .color-dot {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s;
        position: relative;
    }

    .color-dot:hover,
    .color-dot.active {
        border-color: #fff;
        transform: scale(1.2);
    }

    /* Link Overlay */
    .card-link {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 10;
    }

    /* Load More Button */
    .load-more-container {
        text-align: center;
        margin-top: 60px;
    }

    .magnet-btn-dark {
        display: inline-block;
        padding: 20px 50px;
        background: #1a1a1a;
        color: #fff;
        border: 1px solid #333;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: bold;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .magnet-btn-dark:hover {
        background: #fff;
        color: #000;
        border-color: #fff;
    }
</style>

<section class="page-content products-page">

    <div class="products-hero">
        <h1 class="products-title">The Collection</h1>

        <div class="filter-container">
            <a href="?supplier_id=<?= $supplier_id ?>&page=products"
                class="category-btn <?= $category_filter == 'all' ? 'active' : '' ?>">
                All Products
            </a>

            <?php
            $cat_query = mysqli_query($conn, "SELECT * FROM category WHERE supplier_id = $supplier_id ORDER BY category_name ASC");
            while ($cat = mysqli_fetch_assoc($cat_query)) {
                $cId = $cat['category_id'];
                $cName = htmlspecialchars($cat['category_name']);
                $isActive = ($category_filter == $cId) ? 'active' : '';

                echo "<a href='?supplier_id=$supplier_id&page=products&category_id=$cId' class='category-btn $isActive'>$cName</a>";
            }
            ?>
        </div>
    </div>

    <div class="row product-grid" id="product-grid">
        <?php
        // Prepare Query
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN category c ON p.category_id = c.category_id 
                WHERE p.supplier_id = ?";

        if ($category_filter !== 'all') {
            $sql .= " AND p.category_id = ?";
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT 9"; // Initial limit

        $stmt = mysqli_prepare($conn, $sql);

        if ($category_filter !== 'all') {
            mysqli_stmt_bind_param($stmt, "ii", $supplier_id, $category_filter);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $supplier_id);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
                // Image handling
                $imgUrl = "../uploads/products/" . $row['product_id'] . "_" . $row['image'];
                $price = number_format($row['price'], 2);
                $name = htmlspecialchars($row['product_name']);
                $catName = htmlspecialchars($row['category_name'] ?? 'Exclusive');

                // Link to Product Detail Page (Router Structure)
                $detailLink = "?supplier_id=$supplier_id&page=productdetail&product_id=" . $row['product_id'];
        ?>

                <div class="product-card-wrapper">
                    <div class="product-card tilt-element">

                        <a href="<?= $detailLink ?>" class="card-link"></a>

                        <div class="card-image-box">
                            <img src="<?= $imgUrl ?>" alt="<?= $name ?>" class="product-img" id="img-<?= $row['product_id'] ?>">
                        </div>

                        <div class="card-info">
                            <div>
                                <div class="p-category"><?= $catName ?></div>
                                <h3 class="p-title"><?= $name ?></h3>
                            </div>

                            <div class="p-footer">
                                <span class="p-price">$<?= $price ?></span>

                                <div class="color-options" style="position: relative; z-index: 20;">
                                    <div class="color-dot active" style="background: #333;"
                                        onmouseover="changeColor(<?= $row['product_id'] ?>, 0)"></div>
                                    <div class="color-dot" style="background: #5D4037;"
                                        onmouseover="changeColor(<?= $row['product_id'] ?>, 30)"></div>
                                    <div class="color-dot" style="background: #1A237E;"
                                        onmouseover="changeColor(<?= $row['product_id'] ?>, 200)"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            endwhile;
        else:
            ?>
            <div class="col-12 text-center text-muted">
                <p>No products found in this collection.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="load-more-container">
        <button id="load-more-btn" class="magnet-btn-dark" data-offset="9" data-supplier="<?= $supplier_id ?>">
            View More Products
        </button>
    </div>

</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        // 1. 3D TILT EFFECT (Re-used from Contact/Home)
        const cards = document.querySelectorAll('.tilt-element');

        cards.forEach(card => {
            card.addEventListener('mousemove', handleHover);
            card.addEventListener('mouseleave', resetCard);
        });

        function handleHover(e) {
            const card = this;
            const rect = card.getBoundingClientRect();

            // Calculate mouse position
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // Center of card
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            // Rotation degrees (subtle)
            const rotateX = ((y - centerY) / centerY) * -5;
            const rotateY = ((x - centerX) / centerX) * 5;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
        }

        function resetCard() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
        }
    });

    // 2. SIMULATED VARIANT HOVER (Changes Image Filter)
    // This simulates different colorways without needing extra database images
    function changeColor(productId, hueRotate) {
        const img = document.getElementById('img-' + productId);
        if (img) {
            // Apply CSS filter to change color
            if (hueRotate === 0) {
                img.style.filter = 'none';
            } else {
                // Sepia + Hue Rotate gives a realistic color tinting effect on dark/light images
                img.style.filter = `sepia(0.3) hue-rotate(${hueRotate}deg) saturate(1.2)`;
            }
        }
    }
</script>