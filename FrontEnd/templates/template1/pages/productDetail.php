<?php
if (!isset($_GET['product_id'])) {
    echo "<p>Product not found.</p>";
    exit;
}

$product_id = (int) $_GET['product_id'];

// 1. Fetch Product Details
$stmt = mysqli_prepare(
    $conn,
    "SELECT p.*, c.category_name
     FROM products p
     LEFT JOIN category c ON p.category_id = c.category_id
     WHERE p.product_id = ? AND p.supplier_id = ? AND p.status != 'unavailable'
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "ii", $product_id, $supplier_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}

// 2. Fetch Variants including Quantity for Stock Check
$variant_stmt = mysqli_prepare(
    $conn,
    "SELECT variant_id, color, size, quantity FROM product_variant WHERE product_id = ?"
);
mysqli_stmt_bind_param($variant_stmt, "i", $product_id);
mysqli_stmt_execute($variant_stmt);
$variant_result = mysqli_stmt_get_result($variant_stmt);

$variants = [];
$colors = [];

while ($row = mysqli_fetch_assoc($variant_result)) {
    $variants[] = $row;
    if (!in_array($row['color'], $colors)) {
        $colors[] = $row['color'];
    }
}
mysqli_stmt_close($variant_stmt);
?>

<div class="page">
    <div class="product-detail-wrapper"
        style="display: flex; gap: 30px; align-items: flex-start; justify-content: center; width: 100%; max-width: 1000px; margin: 0 auto; padding: 20px; flex-wrap: wrap;">

        <div class="detail_card" style="margin: 0; flex: 1; min-width: 400px;">
            <div class="img_border">
                <div class="detail_product_image">
                    <?php if (!empty($product['image'])): ?>
                        <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                            alt="<?= htmlspecialchars($product['product_name']) ?>">
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail_product" style="position: relative; padding-top: 20px;">
    <a href="?supplier_id=<?= $supplier_id ?>&page=products" 
       style="position: absolute; top: 15px; right: 15px; text-decoration: none; z-index: 10; display: flex; align-items: center; justify-content: center;" 
       title="Back to Shop">
        <svg width="40" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <line x1="50" y1="16" x2="13" y2="16" stroke="black" stroke-width="1" stroke-linecap="round"/>
            <path d="M19 10L13 16L19 22" stroke="black" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>

    <div class="detail_product_category"><?= htmlspecialchars($product['category_name'] ?? ' ') ?></div>
    <h1 class="p-title"><?= htmlspecialchars($product['product_name']) ?></h1>
    <div class="p-price">$<?= number_format($product['price'], 2) ?></div>

                <?php if (empty($colors)): ?>
                    <p style="color: #ff4444; font-weight: bold; margin-top: 10px;">Currently Unavailable</p>
                <?php else: ?>
                    <p class="detail_desc"><?= htmlspecialchars($product['description'] ?? 'No description available.') ?>
                    </p>

                    <div class="options">
                        <label>Color</label>
                        <div class="colors" id="color-options">
                            <?php foreach ($colors as $color): ?>
                                <div class="color" data-color="<?= htmlspecialchars($color) ?>"
                                    style="background-color: <?= htmlspecialchars($color) ?>;"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="options">
                        <label>Size</label>
                        <div class="sizes" id="size-buttons">
                            <span id="size-placeholder">Select color first</span>
                        </div>
                        <div id="stock-display" style="font-size: 0.85rem; color: #666; margin-top: 5px; font-weight: 600;">
                        </div>
                    </div>

                    <div id="qtyErrorMessage"
                        style="display:none; color: #c62828; font-size: 0.8rem; margin-top: 8px; font-weight: bold;">
                   Quantity exceeds available stock!
                    </div>

                    <?php if (($product['status'] ?? 'available') !== 'unavailable'): ?>
                        <div class="quantity-add">
                            <div class="quantity-selector">
                                <button class="qty-btn" id="decrease">-</button>
                                <span id="qty">1</span>
                                <button class="qty-btn" id="increase">+</button>
                            </div>
                            <button class="addtobag_btn" id="addToCartBtn">ADD TO BAG</button>
                        </div>
                    <?php else: ?>
                        <div class="unavailable-message"
                            style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; text-align: center; font-weight: bold; border: 1px solid #ef9a9a; margin-top: 20px;">
                            <i class="fas fa-exclamation-circle"></i> This product is currently unavailable.
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const variants = <?= json_encode($variants) ?>;
    const colorEls = document.querySelectorAll('.color');
    const sizeButtons = document.getElementById('size-buttons');
    const stockDisplay = document.getElementById('stock-display');
    const qtyErrorMessage = document.getElementById('qtyErrorMessage');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const qtySpan = document.getElementById('qty');

    let selectedSize = null;
    let selectedColor = null;
    let currentVariant = null;

    colorEls.forEach(colorEl => {
        colorEl.addEventListener('click', () => {
            colorEls.forEach(c => c.classList.remove('active'));
            colorEl.classList.add('active');
            selectedColor = colorEl.dataset.color;
            const filteredVariants = variants.filter(v => v.color === selectedColor);

            sizeButtons.innerHTML = '';
            stockDisplay.textContent = '';
            qtyErrorMessage.style.display = 'none';
            selectedSize = null;
            currentVariant = null;
            qtySpan.textContent = "1";

            filteredVariants.forEach(v => {
                const btn = document.createElement('button');
                btn.className = 'size-btn';
                btn.textContent = v.size;
                if (v.quantity <= 0) {
                    btn.style.opacity = '0.4';
                    btn.style.cursor = 'not-allowed';
                }
                btn.addEventListener('click', () => {
                    if (v.quantity <= 0) return;
                    document.querySelectorAll('.sizes button').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    selectedSize = v.size;
                    currentVariant = v;
                    stockDisplay.textContent = `In Stock: ${v.quantity}`;
                    validateStock();
                });
                sizeButtons.appendChild(btn);
            });
        });
    });

    function validateStock() {
        if (!currentVariant) return;
        const requestedQty = parseInt(qtySpan.textContent);
        const availableQty = parseInt(currentVariant.quantity);
        qtyErrorMessage.style.display = requestedQty > availableQty ? 'block' : 'none';
        addToCartBtn.disabled = requestedQty > availableQty;
        addToCartBtn.style.opacity = requestedQty > availableQty ? '0.5' : '1';
    }

    document.getElementById('increase').addEventListener('click', () => {
        if (!currentVariant) return;
        qtySpan.textContent = parseInt(qtySpan.textContent) + 1;
        validateStock();
    });

    document.getElementById('decrease').addEventListener('click', () => {
        let val = parseInt(qtySpan.textContent);
        if (val > 1) {
            qtySpan.textContent = val - 1;
            validateStock();
        }
    });

    // FIXED ADD TO BAG HANDLER
  addToCartBtn.addEventListener('click', function () {

    // 🔐 GUEST CHECK — reuse review popup
    if (!window.IS_LOGGED_IN) {
        openAuthModal();   // SAME popup as review page
        return;
    }

    if (!currentVariant) {
        showNotification("Please select a color and size first.", "danger");
        return;
    }

    const requestedQty = parseInt(qtySpan.textContent);
    const availableStock = parseInt(currentVariant.quantity);

    fetch(`../utils/get_cart_data.php?supplier_id=<?= $supplier_id ?>`)
        .then(res => res.json())
        .then(data => {
            const existingItem = data.items.find(item =>
                item.name === <?= json_encode($product['product_name']) ?> &&
                item.size === selectedSize
            );

            const currentInBag = existingItem ? parseInt(existingItem.qty) : 0;

            if (currentInBag + requestedQty > availableStock) {
                showNotification(
                    `Limit reached! You have ${currentInBag} in bag. Only ${availableStock} available.`,
                    "danger"
                );
                return;
            }

            const formData = new FormData();
            formData.append('variant_id', currentVariant.variant_id);
            formData.append('supplier_id', <?= $supplier_id ?>);
            formData.append('quantity', requestedQty);

            return fetch('../utils/add_to_cart.php', {
                method: 'POST',
                body: formData
            });
        })
        .then(response => response ? response.json() : null)
        .then(data => {
            if (data && data.status === 'success') {
                showNotification(data.message, "success");
                refreshCartDrawer(<?= $supplier_id ?>);
            }
        })
        .catch(err => console.error(err));
});

</script>
<section class="related-section">
    <div class="related-section-header">
        <h2>Related Products</h2>
        <span class="related-section-line"></span>
    </div>
</section>

<section class="related-products-page">
    <div class="container">
        <div class="related_product_list_grid">
            <?php
            // Use the category_id from the current product to find related items
            $current_cat_id = $product['category_id'];

            // Query to fetch products from the same category, excluding the current product
            $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND category_id = ? AND product_id != ? AND status != 'unavailable' ORDER BY created_at DESC LIMIT 4");

            if ($products_stmt) {
                mysqli_stmt_bind_param($products_stmt, "iii", $supplier_id, $current_cat_id, $product_id);
                mysqli_stmt_execute($products_stmt);
                $products_result = mysqli_stmt_get_result($products_stmt);
            }

            if ($products_result && mysqli_num_rows($products_result) > 0) {
                while ($rel_product = mysqli_fetch_assoc($products_result)) {
                    ?>
                    <div class="related_product">
                        <div class="related_product_image">
                            <?php if (!empty($rel_product['image'])): ?>
                                <img
                                    src="../uploads/products/<?= $rel_product['product_id'] ?>_<?= htmlspecialchars($rel_product['image']) ?>">
                            <?php endif; ?>
                        </div>

                        <div class="related_product_card-body">
                            <div class="related_product-info">
                                <span
                                    class="related_product_card_title"><?= htmlspecialchars($rel_product['product_name']) ?></span>
                                <span class="related_product_price">$<?= number_format($rel_product['price'], 2) ?></span>
                            </div>
                        </div>
                        <a class="detail-link"
                            href="?supplier_id=<?= $supplier_id ?>&page=productDetail&product_id=<?= $rel_product['product_id'] ?>">
                            <button class="detail-btn">VIEW DETAILS</button>
                        </a>
                    </div>
                    <?php
                }
                mysqli_stmt_close($products_stmt);
            } else {
                ?>
                <div class="col-12">
                    <p class="text-center">No related products available at the moment.</p>
                </div>
            <?php } ?>
        </div>
    </div>
</section>