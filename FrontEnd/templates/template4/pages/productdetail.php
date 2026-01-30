<?php
// product_detail.php - Modern "Famous Brand" Layout Redesign

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

// 2. Fetch ALL variants
$v_stmt = mysqli_prepare($conn, "SELECT * FROM product_variant WHERE product_id = ? ORDER BY size ASC");
mysqli_stmt_bind_param($v_stmt, "i", $product_id);
mysqli_stmt_execute($v_stmt);
$v_result = mysqli_stmt_get_result($v_stmt);

$variants = [];
$available_colors = [];

while ($row = mysqli_fetch_assoc($v_result)) {
    $row['size'] = trim($row['size']);
    $variants[] = $row;

    if (!in_array($row['color'], $available_colors)) {
        $available_colors[] = $row['color'];
    }
}

$main_image = "../uploads/products/" . $product['product_id'] . "_" . htmlspecialchars($product['image']);
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;800&display=swap');

    :root {
        --bg-color: #111111;
        --surface-color: #1a1a1a;
        --text-main: #ffffff;
        --text-muted: #888888;
        --border-color: #333333;
        --accent: #ffffff;
        /* High contrast white for primary actions */
        --accent-text: #000000;
        --highlight: #ff6b35;
        /* Keep your brand orange for highlights only */
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        margin: 0;
        overflow-x: hidden;
    }

    /* Layout Grid */
    .pdp-wrapper {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        /* 60% Image, 40% Info */
        gap: 6rem;
        max-width: 1400px;
        margin: 0 auto;
        padding: 120px 40px 60px;
        /* Top padding clears the fixed header */
        align-items: start;
    }

    /* Left: Immersive Image Section */
    .pdp-image-section {
        position: relative;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        /* Subtle background for the image area */
        background-color: var(--surface-color);
        border-radius: 2px;
        min-height: 600px;
    }

    .pdp-main-img {
        width: 100%;
        height: auto;
        max-height: 800px;
        object-fit: contain;
        display: block;
        background-color: #111111;  
        transition: transform 0.3s ease;
        border-radius: 30px;
    }

    .pdp-main-img:hover {
        transform: scale(1.02);
    }

    /* Right: Sticky Details Panel */
    .pdp-info-panel {
        position: sticky;
        top: 100px;
        /* Sticks to top when scrolling */
        display: flex;
        flex-direction: column;
        height: fit-content;
    }

    /* Typography */
    .breadcrumb {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: color 0.2s;
    }

    .breadcrumb:hover {
        color: var(--text-main);
    }

    .p-title {
        font-size: 3rem;
        /* Big & Bold */
        font-weight: 800;
        line-height: 1.1;
        margin: 0 0 1rem 0;
        letter-spacing: -0.02em;
    }

    .p-price {
        font-size: 1.5rem;
        font-weight: 500;
        color: var(--text-main);
        margin-bottom: 2.5rem;
    }

    /* Description */
    .p-desc {
        font-size: 1rem;
        line-height: 1.7;
        color: var(--text-muted);
        margin-top: 2rem;
        margin-bottom: 2rem;
        font-weight: 300;
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 2rem;
    }

    .label {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.8rem;
        display: flex;
        justify-content: space-between;
        color: var(--text-main);
    }

    /* Color Swatches - Modern Minimalist */
    .color-options {
        display: flex;
        gap: 12px;
    }

    .swatch {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.2s ease;
    }

    .swatch:hover {
        transform: scale(1.1);
    }

    .swatch.active {
        box-shadow: 0 0 0 2px var(--bg-color), 0 0 0 4px var(--text-main);
        /* Double ring effect */
        border-color: transparent;
    }

    /* Size Grid - Clean Boxes */
    .size-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .size-option {
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-main);
        padding: 14px 0;
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        border-radius: 4px;
    }

    .size-option:hover:not(:disabled) {
        border-color: var(--text-main);
    }

    .size-option.active {
        background: var(--text-main);
        color: var(--accent-text);
        border-color: var(--text-main);
    }

    .size-option:disabled {
        opacity: 0.3;
        background: rgba(255, 255, 255, 0.05);
        border-color: transparent;
        cursor: not-allowed;
        text-decoration: line-through;
    }

    /* Actions Row */
    .actions-row {
        display: grid;
        grid-template-columns: 100px 1fr;
        gap: 15px;
        margin-top: 1rem;
    }

    /* Quantity Input */
    .qty-control {
        display: flex;
        align-items: center;
        border: 1px solid var(--border-color);
        border-radius: 50px;
        /* Pill shape */
        padding: 0 10px;
        height: 56px;
    }

    .qty-btn {
        background: none;
        border: none;
        color: var(--text-main);
        font-size: 1.2rem;
        cursor: pointer;
        width: 30px;
    }

    .qty-val {
        width: 40px;
        text-align: center;
        background: none;
        border: none;
        color: var(--text-main);
        font-weight: 600;
        font-size: 1rem;
    }

    /* Add to Cart Button - The "Hero" Element */
    .btn-add {
        background-color: var(--accent);
        color: var(--accent-text);
        border: none;
        height: 56px;
        border-radius: 50px;
        /* Pill shape */
        font-size: 1rem;
        font-weight: 700;
        text-transform: uppercase;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        letter-spacing: 0.5px;
    }

    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(255, 255, 255, 0.15);
    }

    .btn-add:disabled {
        background-color: var(--border-color);
        color: var(--text-muted);
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .error-toast {
        color: #ff4444;
        font-size: 0.85rem;
        margin-top: 10px;
        font-weight: 500;
        display: none;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Mobile Responsive */
    @media (max-width: 900px) {
        .pdp-wrapper {
            grid-template-columns: 1fr;
            gap: 40px;
            padding-top: 100px;
        }

        .pdp-info-panel {
            position: static;
        }

        .p-title {
            font-size: 2.2rem;
        }

        .pdp-image-section {
            min-height: 400px;
        }
    }
</style>

<div class="pdp-wrapper">
    <div class="pdp-image-section">
        <img src="<?= $main_image ?>" id="main-product-img" class="pdp-main-img" alt="<?= htmlspecialchars($product['product_name']) ?>">
    </div>

    <div class="pdp-info-panel">
        <div class="breadcrumb" onclick="goBack()">
            &larr; Back to Shop
        </div>

        <h1 class="p-title"><?= htmlspecialchars($product['product_name']) ?></h1>
        <div class="p-price">$<?= number_format($product['price'], 2) ?></div>

        <?php if (empty($available_colors)): ?>
            <p style="color: #ff4444;">Currently Unavailable</p>
        <?php else: ?>

            <div class="form-group">
                <div class="label">Color: <span id="color-name-display" style="font-weight:400; color:var(--text-muted);"><?= ucfirst($available_colors[0]) ?></span></div>
                <div class="color-options">
                    <?php foreach ($available_colors as $index => $color): ?>
                        <div class="swatch <?= $index === 0 ? 'active' : '' ?>"
                            style="background-color: <?= $color ?>;"
                            onclick="changeColor('<?= $color ?>', this)"
                            data-color-name="<?= $color ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <div class="label">Select Size</div>
                <div class="size-grid" id="size-container">
                </div>
            </div>

            <form id="cart-form">
                <input type="hidden" name="variant_id" id="selected-variant-id" value="">
                <input type="hidden" name="supplier_id" id="supplier_id" value="<?= $supplier_id ?>">

                <div class="actions-row">
                    <div class="qty-control">
                        <button type="button" class="qty-btn" onclick="updateQty(-1)">&minus;</button>
                        <input type="number" id="qty-input" class="qty-val" value="1" min="1" readonly>
                        <button type="button" class="qty-btn" onclick="updateQty(1)">&plus;</button>
                    </div>

                    <button type="button" class="btn-add" id="add-btn" onclick="handleAddToCart()">
                        Add to Bag
                    </button>
                </div>
                <div id="error-msg" class="error-toast">Please select a size first.</div>
            </form>

        <?php endif; ?>

        <div class="p-desc">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
        </div>
    </div>
</div>

<script>
    const variants = <?= json_encode($variants) ?>;
    let selectedColor = "<?= $available_colors[0] ?? '' ?>";
    let selectedVariantId = null;
    let currentMaxStock = 100;

    document.addEventListener("DOMContentLoaded", () => {
        if (selectedColor) {
            renderSizes(selectedColor);
        }
    });

    function goBack() {
        // Smart back button
        if (document.referrer.indexOf(window.location.host) !== -1) {
            history.back();
        } else {
            window.location.href = '../index.php';
        }
    }

    function changeColor(color, element) {
        selectedColor = color;
        selectedVariantId = null;
        document.getElementById('selected-variant-id').value = "";

        // Update display text
        document.getElementById('color-name-display').innerText = color.charAt(0).toUpperCase() + color.slice(1);

        // Reset Qty
        currentMaxStock = 100;
        document.getElementById('qty-input').value = 1;

        // UI Active State
        document.querySelectorAll('.swatch').forEach(el => el.classList.remove('active'));
        element.classList.add('active');

        renderSizes(color);
    }

    function renderSizes(color) {
        const container = document.getElementById('size-container');
        container.innerHTML = "";

        const filtered = variants.filter(v => v.color === color);

        filtered.forEach(v => {
            const btn = document.createElement('button');
            btn.type = "button";
            btn.className = "size-option";
            btn.innerText = v.size;

            if (parseInt(v.quantity) <= 0) {
                btn.disabled = true;
                btn.title = "Out of Stock";
            } else {
                btn.onclick = () => selectSize(v, btn);
            }
            container.appendChild(btn);
        });
    }

    function selectSize(variantObj, btn) {
        selectedVariantId = variantObj.variant_id;
        document.getElementById('selected-variant-id').value = selectedVariantId;

        currentMaxStock = parseInt(variantObj.quantity);

        // Adjust current qty if needed
        const qtyInput = document.getElementById('qty-input');
        if (parseInt(qtyInput.value) > currentMaxStock) {
            qtyInput.value = currentMaxStock;
        }

        // UI Active State
        document.querySelectorAll('.size-option').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');

        document.getElementById('error-msg').style.display = 'none';
    }

    function updateQty(change) {
        const input = document.getElementById('qty-input');
        let currentVal = parseInt(input.value);
        let newVal = currentVal + change;

        if (newVal < 1) newVal = 1;
        if (selectedVariantId && newVal > currentMaxStock) newVal = currentMaxStock;

        input.value = newVal;
    }

    function handleAddToCart() {
        if (!selectedVariantId) {
            const err = document.getElementById('error-msg');
            err.innerText = "Please select a size first.";
            err.style.display = 'block';
            return;
        }

        const qtyValue = document.getElementById('qty-input').value;
        const btn = document.getElementById('add-btn');
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = "Processing...";

        const urlParams = new URLSearchParams(window.location.search);
        const supplierId = urlParams.get('supplier_id') || <?= $supplier_id ?? 'null' ?>;

        const formData = new FormData();
        formData.append('variant_id', selectedVariantId);
        formData.append('supplier_id', supplierId);
        formData.append('quantity', qtyValue);

        fetch('../utils/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (typeof window.showMinimalAlert === 'function') {
                        window.showMinimalAlert('Added to bag', 'success');
                    } else {
                        alert("Added to bag!");
                    }
                    if (typeof window.refreshCart === 'function') {
                        window.refreshCart(supplierId);
                    }
                } else {
                    if (typeof window.showMinimalAlert === 'function') {
                        window.showMinimalAlert(data.message || 'Error', 'error');
                    } else {
                        alert(data.message);
                    }
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => {
                btn.disabled = false;
                btn.innerText = originalText;
            });
    }
</script>