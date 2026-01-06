<?php
// product_detail.php - Full Fix for Dynamic Sizes and Availability

if (!isset($_GET['product_id'])) {
    echo "Product not found.";
    exit;
}

$product_id = intval($_GET['product_id']);

// 1. Fetch Product Basic Info
$stmt = mysqli_prepare($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN category c ON p.category_id = c.category_id WHERE p.product_id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo "Product not found.";
    exit;
}

// 2. UPDATED QUERY: Fetch ALL variants (even those with 0 quantity) 
// This ensures the size buttons exist but are "disabled" if out of stock.
$v_stmt = mysqli_prepare($conn, "SELECT * FROM product_variant WHERE product_id = ? ORDER BY size ASC");
mysqli_stmt_bind_param($v_stmt, "i", $product_id);
mysqli_stmt_execute($v_stmt);
$v_result = mysqli_stmt_get_result($v_stmt);

$variants = [];
$available_colors = [];

while ($row = mysqli_fetch_assoc($v_result)) {
    // Clean up size string (removes accidental spaces from DB)
    $row['size'] = trim($row['size']);
    $variants[] = $row;

    // Group unique colors
    if (!in_array($row['color'], $available_colors)) {
        $available_colors[] = $row['color'];
    }
}

$main_image = "../uploads/products/" . $product['product_id'] . "_" . htmlspecialchars($product['image']);
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap');

    :root {
        --bg-color: #0f0f0f;
        --text-main: #ffffff;
        --text-sub: #b0b0b0;
        --border-light: #2a2a2a;
        --accent: #ff6b35;
        --accent-dark: #d94124;
        --hover-color: #ff8555;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-main);
    }

    /* Back button */
    .btn-back {
        background: transparent;
        color: var(--text-main);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 8px 14px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 700;
        letter-spacing: 0.6px;
        margin-bottom: 18px;
        align-self: start;
        transition: all 0.18s ease-in-out;
    }

    .btn-back:hover {
        transform: translateY(-2px);
        border-color: rgba(255, 107, 53, 0.9);
        color: var(--accent);
    }

    .pdp-wrapper {
        display: grid;
        grid-template-columns: 1.3fr 1fr;
        gap: 60px;
        max-width: 1300px;
        margin: 50px auto;
        padding: 0 25px;
        color: var(--text-main);
        font-family: 'Nunito', 'Helvetica Neue', Arial, sans-serif;
    }

    /* Left: Image */
    .pdp-image-container {
        background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
        border-radius: 0px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-light);
        min-height: 500px;
        position: sticky;
        top: 20px;
    }

    .pdp-main-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    /* Right: Details */
    .pdp-info {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }

    .p-cat {
        color: var(--accent);
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.75rem;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .p-name {
        font-size: 3rem;
        font-weight: 800;
        margin: 0 0 15px;
        text-transform: uppercase;
        line-height: 1.1;
        letter-spacing: -1px;
    }

    .p-price {
        font-size: 1.75rem;
        margin-bottom: 35px;
        font-weight: 700;
        color: var(--accent);
    }

    /* Radio Style Color Swatches */
    .selector-label {
        font-weight: 700;
        margin-bottom: 18px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
    }

    .color-flex {
        display: flex;
        gap: 18px;
        margin-bottom: 35px;
    }

    .radio-swatch {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        background-clip: content-box;
        padding: 4px;
        border: 2px solid #333;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .radio-swatch:hover {
        border-color: var(--accent);
        transform: scale(1.08);
        box-shadow: 0 0 15px rgba(255, 107, 53, 0.2);
    }

    .radio-swatch.active {
        border-color: var(--accent);
        border-width: 2px;
        transform: scale(1.12);
        box-shadow: 0 0 20px rgba(255, 107, 53, 0.4);
    }

    /* Size Grid */
    .size-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 35px;
    }

    .size-btn {
        background: transparent;
        border: 2px solid var(--border-light);
        color: var(--text-main);
        padding: 16px 8px;
        border-radius: 0px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .size-btn:hover:not(:disabled) {
        border-color: var(--accent);
        color: var(--accent);
        transform: translateY(-2px);
    }

    .size-btn.active {
        background: var(--accent);
        color: #000;
        border-color: var(--accent);
        font-weight: 700;
    }

    /* Out of Stock Style */
    .size-btn:disabled {
        color: #555;
        background: #0a0a0a;
        border-color: #222;
        cursor: not-allowed;
        opacity: 0.5;
    }

    .btn-submit {
        background: var(--accent);
        color: #000;
        border: none;
        padding: 18px 40px;
        border-radius: 0px;
        font-weight: 700;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 1px;
        font-size: 1rem;
    }

    .btn-submit:hover {
        background: var(--hover-color);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
    }

    .btn-submit:disabled {
        background: #333;
        color: #666;
        cursor: not-allowed;
        transform: none;
    }

    #error-msg {
        color: #ff5555;
        font-size: 0.9rem;
        margin-top: 12px;
        display: none;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    @media (max-width: 768px) {
        .pdp-wrapper {
            grid-template-columns: 1fr;
            gap: 30px;
            margin: 30px auto;
        }

        .pdp-image-container {
            position: relative;
            top: auto;
            min-height: 400px;
        }

        .p-name {
            font-size: 2rem;
        }

        .size-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
</style>

<div class="pdp-wrapper">
    <div class="pdp-image-container">
        <img src="<?= $main_image ?>" id="main-product-img" class="pdp-main-img">
    </div>

    <div class="pdp-info">
        <button type="button" class="btn-back" onclick="goBack()">← Back</button>
        <span class="p-cat"><?= htmlspecialchars($product['category_name']) ?></span>
        <h1 class="p-name"><?= htmlspecialchars($product['product_name']) ?></h1>
        <div class="p-price">$<?= number_format($product['price'], 2) ?></div>

        <?php if (empty($available_colors)): ?>
            <p style="color: red;">Product is currently unavailable.</p>
        <?php else: ?>

            <span class="selector-label">Select Color</span>
            <div class="color-flex">
                <?php foreach ($available_colors as $index => $color): ?>
                    <div class="radio-swatch <?= $index === 0 ? 'active' : '' ?>"
                        style="background-color: <?= $color ?>;"
                        onclick="changeColor('<?= $color ?>', this)"
                        title="<?= $color ?>">
                    </div>
                <?php endforeach; ?>
            </div>

            <span class="selector-label">Select Size</span>
            <div class="size-grid" id="size-container">
            </div>

            <form id="cart-form">
                <input type="hidden" name="variant_id" id="selected-variant-id" value="">
                <button type="button" class="btn-submit" id="add-btn" onclick="handleAddToCart()">Add to Bag</button>
                <div id="error-msg">Please select a size.</div>
            </form>

        <?php endif; ?>

        <div style="margin-top: 40px; line-height: 1.6; color: var(--text-sub);">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
        </div>
    </div>
</div>

<script>
    // All variants from PHP/Database
    const variants = <?= json_encode($variants) ?>;
    let selectedColor = "<?= $available_colors[0] ?? '' ?>";
    let selectedVariantId = null;

    // Run on page load
    document.addEventListener("DOMContentLoaded", () => {
        if (selectedColor) {
            renderSizes(selectedColor);
        }
    });

    // 1. When user clicks a color
    function changeColor(color, element) {
        selectedColor = color;
        selectedVariantId = null; // Reset size selection
        document.getElementById('selected-variant-id').value = "";

        // UI updates
        document.querySelectorAll('.radio-swatch').forEach(el => el.classList.remove('active'));
        element.classList.add('active');

        // Update size grid
        renderSizes(color);
    }

    // Back navigation (used by the back button)
    function goBack() {
        if (document.referrer && document.referrer !== window.location.href) {
            window.history.back();
        } else {
            // Fallback: go to shop home
            window.location.href = '../index.php';
        }
    }

    // 2. Render Sizes for the selected color
    function renderSizes(color) {
        const container = document.getElementById('size-container');
        container.innerHTML = ""; // Clear

        // Filter variants that match the chosen color
        const filtered = variants.filter(v => v.color === color);

        filtered.forEach(v => {
            const btn = document.createElement('button');
            btn.type = "button";
            btn.className = "size-btn";
            btn.innerText = v.size;

            // Check availability (Quantity)
            if (parseInt(v.quantity) <= 0) {
                btn.disabled = true;
            } else {
                btn.onclick = () => {
                    selectSize(v.variant_id, btn);
                };
            }
            container.appendChild(btn);
        });
    }

    // 3. When user clicks a size
    function selectSize(id, btn) {
        selectedVariantId = id;
        document.getElementById('selected-variant-id').value = id;

        // UI updates
        document.querySelectorAll('.size-btn').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');

        document.getElementById('error-msg').style.display = 'none';
    }

    // 4. Submit logic
    function handleAddToCart() {
        if (!selectedVariantId) {
            document.getElementById('error-msg').style.display = 'block';
            return;
        }

        const btn = document.getElementById('add-btn');
        btn.innerText = "Adding...";

        // Simulate AJAX / Database update
        setTimeout(() => {
            alert("Success! Variant ID " + selectedVariantId + " added to cart.");
            btn.innerText = "Add to Bag";
        }, 800);
    }
</script>