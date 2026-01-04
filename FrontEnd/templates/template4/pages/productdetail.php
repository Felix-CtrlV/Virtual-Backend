<?php
// product_detail.php - Nike-Style Layout with Hover Effects

if (!isset($_GET['product_id'])) {
    echo "Product not found.";
    exit;
}

$product_id = $_GET['product_id'];

// Secure Query
$stmt = mysqli_prepare($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN category c ON p.category_id = c.category_id WHERE p.product_id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo "Product not found.";
    exit;
}

// Image Path Builder
$main_image = "../uploads/products/" . $product['product_id'] . "_" . htmlspecialchars($product['image']);
?>

<style>
    /* ============================================
       NIKE-STYLE DARK THEME VARIABLES
       ============================================ */
    :root {
        --bg-color: #0a0a0a;
        --text-main: #ffffff;
        --text-muted: #757575;
        --accent: #fff;
        /* White accent for high contrast */
        --font-display: 'Helvetica Neue', 'Arial Black', sans-serif;
    }

    /* Layout Structure */
    .pdp-container {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 40px;
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
        min-height: 100vh;
    }

    /* Left: Image Gallery (Scrollable) */
    .pdp-gallery {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .main-image-frame {
        width: 100%;
        background: #141414;
        border-radius: 12px;
        overflow: hidden;
        position: relative;
        cursor: crosshair;
        /* Suggests zoom ability */
    }

    .pdp-img {
        width: 100%;
        height: auto;
        display: block;
        transition: transform 0.3s ease;
    }

    /* Zoom effect on hover */
    .main-image-frame:hover .pdp-img {
        transform: scale(1.05);
    }

    /* Right: Sticky Details */
    .pdp-details {
        position: sticky;
        top: 40px;
        height: fit-content;
        padding: 20px 40px;
        color: var(--text-main);
    }

    /* Typography */
    .pdp-title {
        font-family: var(--font-display);
        font-size: 3rem;
        line-height: 0.95;
        text-transform: uppercase;
        margin-bottom: 10px;
        font-weight: 800;
    }

    .pdp-category {
        font-size: 1rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 20px;
    }

    .pdp-price {
        font-size: 1.5rem;
        font-weight: 500;
        margin-bottom: 30px;
        display: block;
    }

    .pdp-desc {
        font-size: 1.1rem;
        line-height: 1.6;
        color: #ccc;
        margin-bottom: 40px;
    }

    /* Interactive Color Selectors (Hover to swap) */
    .variant-section {
        margin-bottom: 30px;
    }

    .variant-label {
        font-size: 0.9rem;
        font-weight: bold;
        margin-bottom: 15px;
        display: block;
    }

    .color-grid {
        display: flex;
        gap: 15px;
    }

    .color-swatch {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        border: 1px solid #333;
        background-size: cover;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }

    .color-swatch:hover,
    .color-swatch.active {
        border-color: #fff;
        transform: scale(1.1);
    }

    /* Add to Cart Button */
    .btn-add-cart-large {
        width: 100%;
        padding: 25px 0;
        background: #fff;
        color: #000;
        border: none;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 900;
        text-transform: uppercase;
        cursor: pointer;
        transition: transform 0.2s, background 0.2s;
        margin-top: 20px;
    }

    .btn-add-cart-large:hover {
        background: #ddd;
        transform: translateY(-2px);
    }

    /* Mobile Responsive */
    @media (max-width: 992px) {
        .pdp-container {
            grid-template-columns: 1fr;
        }

        .pdp-details {
            position: relative;
            padding: 20px 0;
            top: 0;
        }
    }
</style>

<div class="pdp-container">

    <div class="pdp-gallery">
        <div class="main-image-frame">
            <img id="mainImage" src="<?= $main_image ?>" alt="Product Image" class="pdp-img">
        </div>

    </div>

    <div class="pdp-details">
        <div class="pdp-category"><?= htmlspecialchars($product['category_name']) ?></div>
        <h1 class="pdp-title"><?= htmlspecialchars($product['product_name']) ?></h1>
        <span class="pdp-price">$<?= number_format($product['price'], 2) ?></span>

        <div class="pdp-desc">
            <?= htmlspecialchars($product['description'] ?? 'Engineered for those who refuse to settle. This product combines premium materials with cutting-edge design to deliver specific performance and style.') ?>
        </div>

        <div class="variant-section">
            <span class="variant-label">Select Style (Hover to Preview)</span>
            <div class="color-grid">
                <div class="color-swatch active"
                    style="background-image: url('<?= $main_image ?>');"
                    data-img="<?= $main_image ?>">
                </div>

                <div class="color-swatch"
                    style="background-image: url('<?= $main_image ?>'); filter: hue-rotate(90deg);"
                    data-img="<?= $main_image ?>"
                    data-filter="hue-rotate(90deg)">
                </div>

                <div class="color-swatch"
                    style="background-image: url('<?= $main_image ?>'); filter: hue-rotate(180deg) invert(10%);"
                    data-img="<?= $main_image ?>"
                    data-filter="hue-rotate(180deg) invert(10%)">
                </div>
            </div>
        </div>

        <button class="btn-add-cart-large" onclick="addToCart(<?= $product['product_id'] ?>)">
            Add to Bag
        </button>

        <div style="margin-top: 30px; border-top: 1px solid #333; padding-top: 20px;">
            <p style="color: #666; font-size: 0.9rem;">
                <i class="fas fa-truck" style="margin-right: 10px;"></i> Free delivery on orders over $150
            </p>
        </div>
    </div>
</div>

<script>
    // HOVER LOGIC TO CHANGE IMAGE DISPLAY
    const mainImg = document.getElementById('mainImage');
    const swatches = document.querySelectorAll('.color-swatch');

    swatches.forEach(swatch => {
        // When hovering over a swatch
        swatch.addEventListener('mouseenter', function() {
            // 1. Update visual selection
            swatches.forEach(s => s.classList.remove('active'));
            this.classList.add('active');

            // 2. Change Main Image Source (if different)
            const newSrc = this.getAttribute('data-img');
            mainImg.src = newSrc;

            // 3. Apply CSS Filter (For demo purposes only, to simulate color change)
            // In a real database, you would just swap the 'src' to the real image URL.
            const filter = this.getAttribute('data-filter');
            if (filter) {
                mainImg.style.filter = filter;
            } else {
                mainImg.style.filter = 'none';
            }
        });
    });

    // Simple Add to Cart Mockup
    function addToCart(id) {
        const btn = document.querySelector('.btn-add-cart-large');
        const oldText = btn.innerText;
        btn.innerText = "Added";
        btn.style.background = "#D4AF37"; // Gold accent
        setTimeout(() => {
            btn.innerText = oldText;
            btn.style.background = "#fff";
        }, 2000);
        // Integrate with your existing script.js cart logic here
    }
</script>