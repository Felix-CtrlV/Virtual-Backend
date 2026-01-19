<?php
if (!isset($_GET['product_id'])) {
    echo "<p>Product not found.</p>";
    exit;
}

$product_id = (int) $_GET['product_id'];

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

$variant_stmt = mysqli_prepare(
    $conn,
    "SELECT variant_id, color, size
     FROM product_variant
     WHERE product_id = ?"
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

<script>
    const variants = <?= json_encode($variants) ?>;
</script>

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

            <div class="detail_product">
                <div class="detail_product_category"><?= htmlspecialchars($product['category_name'] ?? ' ') ?></div>
                <h1 class="detail_product_name"><?= htmlspecialchars($product['product_name']) ?></h1>
                <div class="detail_price">$<?= number_format($product['price'], 2) ?></div>
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
            </div>
        </div>
    </div>
</div>

<script>
    // 1. VARIANT SELECTION LOGIC
    const colorEls = document.querySelectorAll('.color');
    const sizeButtons = document.getElementById('size-buttons');
    let selectedSize = null;
    let selectedColor = null;

    colorEls.forEach(colorEl => {
        colorEl.addEventListener('click', () => {
            colorEls.forEach(c => c.classList.remove('active'));
            colorEl.classList.add('active');
            selectedColor = colorEl.dataset.color;

            const filteredVariants = variants.filter(v => v.color === selectedColor);
            const uniqueSizes = [...new Set(filteredVariants.map(v => v.size))];

            sizeButtons.innerHTML = '';
            uniqueSizes.forEach(size => {
                const btn = document.createElement('button');
                btn.className = 'size-btn';
                btn.textContent = size;
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.sizes button').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    selectedSize = size;
                });
                sizeButtons.appendChild(btn);
            });
        });
    });

    // 2. QUANTITY LOGIC
    const qtySpan = document.getElementById('qty');
    document.getElementById('increase').addEventListener('click', () => {
        qtySpan.textContent = parseInt(qtySpan.textContent) + 1;
    });
    document.getElementById('decrease').addEventListener('click', () => {
        let val = parseInt(qtySpan.textContent);
        if (val > 1) qtySpan.textContent = val - 1;
    });

    // 3. ADD TO BAG CLICK HANDLER
    document.getElementById('addToCartBtn').addEventListener('click', function () {
        const qty = parseInt(qtySpan.textContent);
        const selection = variants.find(v => v.color === selectedColor && v.size === selectedSize);

        if (!selection) {
            if (typeof showNotification === "function") {
                showNotification("Please select a color and size first.", "error");
            } else {
                alert("Please select a color and size first.");
            }
            return;
        }

        const formData = new FormData();
        formData.append('variant_id', selection.variant_id);
        formData.append('supplier_id', <?= $supplier_id ?>);
        formData.append('quantity', qty);

        fetch('../utils/add_to_cart.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // 1. Show the success notification only
                    if (typeof showNotification === "function") {
                        showNotification(data.message, "success");
                    }

                    // 2. Refresh cart data (updates the badge/icon) without opening the drawer
                    if (typeof refreshCartDrawer === "function") {
                        refreshCartDrawer(<?= $supplier_id ?>);
                    }

                    // --- AUTO-OPEN LOGIC REMOVED FROM HERE ---

                } else {
                    if (typeof showNotification === "function") {
                        showNotification("Error: " + data.message, "error");
                    } else {
                        alert("Error: " + data.message);
                    }
                }
            })
            .catch(err => {
                console.error('Error:', err);
                if (typeof showNotification === "function") {
                    showNotification("Something went wrong. Please try again.", "error");
                }
            });
    });

    window.onload = function () {
        if (typeof refreshCartDrawer === "function") {
            refreshCartDrawer(<?= $supplier_id ?>);
        }
    };
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

            if (!isset($_GET['category_id'])) {
                // Add AND status != 'unavailable'
                $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND status != 'unavailable' ORDER BY created_at DESC");
                if ($products_stmt) {
                    mysqli_stmt_bind_param($products_stmt, "i", $supplier_id);
                    mysqli_stmt_execute($products_stmt);
                    $products_result = mysqli_stmt_get_result($products_stmt);
                }
            } else {
                // Add AND status != 'unavailable'
                $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND category_id = ? AND status != 'unavailable' ORDER BY created_at DESC");
                if ($products_stmt) {
                    mysqli_stmt_bind_param($products_stmt, "ii", $supplier_id, $_GET['category_id']);
                    mysqli_stmt_execute($products_stmt);
                    $products_result = mysqli_stmt_get_result($products_stmt);
                }
            }
            if ($products_result && mysqli_num_rows($products_result) > 0) {
                while ($product = mysqli_fetch_assoc($products_result)) {

                    ?>
                    <div class="related_product">
                        <div class="related_product_image">
                            <?php if (!empty($product['image'])): ?>
                                <img
                                    src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>">
                            <?php endif; ?>
                        </div>

                        <div class="related_product_card-body">
                            <div class="related_product-info">
                                <span
                                    class="related_product_card_title"><?= htmlspecialchars($product['product_name']) ?></span>
                                <span class="related_product_price">$<?= number_format($product['price'], 2) ?></span>
                            </div>

                            <button class="related_product_add-to-cart" title="Add to cart">+</button>
                        </div>
                        <a class="detail-link"
                            href="?supplier_id=<?= $supplier_id ?>&page=productDetail&product_id=<?= $product['product_id'] ?>">
                            <button class="detail-btn">VIEW DETAILS</button>
                        </a>
                    </div>


                    <?php
                }
                if (isset($products_stmt)) {
                    mysqli_stmt_close($products_stmt);
                }
            } else {
                ?>
                <div class="col-12">
                    <p class="text-center">No products available at the moment.</p>
                </div>
            <?php } ?>
        </div>
    </div>

</section>