<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Product Detail</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body class="productDetail">

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
     WHERE p.product_id = ? AND p.supplier_id = ?
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
        "SELECT color, size FROM product_variant WHERE product_id = ?"
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
        <div class="detail_card">
            <div class="img_border">
                <div class="detail_product_image ">
                    <?php if (!empty($product['image'])): ?>
                        <img
                            src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>">
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
                    <div class="colors">
                        <?php foreach ($colors as $color): ?>
                            <div class="color" data-color="<?= htmlspecialchars($color) ?>"
                                style="background-color: <?= htmlspecialchars($color) ?>;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="options">
                    <label>Size</label>
                    <div class="sizes" id="size-buttons">
                        <span id="size-placeholder">Select color first</span>
                    </div>
                </div>

                <div class="quantity-add">
                    <div class="quantity-selector">
                        <button class="qty-btn" id="decrease">-</button>
                        <span id="qty">1</span>
                        <button class="qty-btn" id="increase">+</button>
                    </div><button type="button" class="addtobag_btn" id="addToCartBtn">
                        ADD TO BAG
                    </button>

                    <form id="addToCartForm" action="/cart/add.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        <input type="hidden" name="quantity" id="cart-quantity" value="1">
                    </form>

                </div>

            </div>
        </div>
    </div>

    <script>
        const colorEls = document.querySelectorAll('.color');
        const sizeButtons = document.getElementById('size-buttons');

        colorEls.forEach(colorEl => {
            colorEls.forEach(c => c.classList.remove('active'));
            colorEl.addEventListener('click', () => {
                colorEls.forEach(c => c.classList.remove('active'));
                colorEl.classList.add('active');

                const selectedColor = colorEl.dataset.color;
                const sizes = variants.filter(v => v.color === selectedColor).map(v => v.size);
                const uniqueSizes = [...new Set(sizes)];

                sizeButtons.innerHTML = '';
                if (uniqueSizes.length === 0) {
                    sizeButtons.textContent = 'No sizes available';
                } else {
                    uniqueSizes.forEach(size => {
                        const btn = document.createElement('button');
                        btn.textContent = size;
                        btn.addEventListener('click', () => {
                            document.querySelectorAll('.sizes button').forEach(b => b.classList.remove('active'));
                            btn.classList.add('active');
                        });
                        sizeButtons.appendChild(btn);
                    });
                }
            });
        });

        sizeButtons.innerHTML = '<span id="size-placeholder">Select color first</span>';

        const qtySpan = document.getElementById('qty');
        document.getElementById('increase').addEventListener('click', () => {
            qtySpan.textContent = parseInt(qtySpan.textContent) + 1;
        });
        document.getElementById('decrease').addEventListener('click', () => {
            let val = parseInt(qtySpan.textContent);
            if (val > 1) qtySpan.textContent = val - 1;
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

                if (!isset($_GET['category_id'])) {
                    $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC");
                    if ($products_stmt) {
                        mysqli_stmt_bind_param($products_stmt, "i", $supplier_id);
                        mysqli_stmt_execute($products_stmt);
                        $products_result = mysqli_stmt_get_result($products_stmt);
                    } else {
                        $products_result = false;
                    }
                } else {
                    $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? and category_id = ? ORDER BY created_at DESC");
                    if ($products_stmt) {
                        mysqli_stmt_bind_param($products_stmt, "ii", $supplier_id, $_GET['category_id']);
                        mysqli_stmt_execute($products_stmt);
                        $products_result = mysqli_stmt_get_result($products_stmt);
                    } else {
                        $products_result = false;
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
</body>

</html>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const addToCartBtn = document.getElementById('addToCartBtn');
    const qtySpan = document.getElementById('qty');
    const cartQtyInput = document.getElementById('cart-quantity');

    if (!addToCartBtn) {
        console.error('ADD TO CART button not found');
        return;
    }

    addToCartBtn.addEventListener('click', () => {
        const selectedColor = document.querySelector('.color.active');
        const selectedSize = document.querySelector('.sizes button.active');

        if (!selectedColor) {
            alert('Please select a color');
            return;
        }

        if (!selectedSize) {
            alert('Please select a size');
            return;
        }

        cartQtyInput.value = qtySpan.textContent;
        document.getElementById('addToCartForm').submit();
    });
});
</script>
