<?php
include("../../BackEnd/config/dbconfig.php");

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    die("Invalid product");
}

$stmt = mysqli_prepare($conn, "
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN category c ON p.category_id = c.category_id
    WHERE p.product_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Product not found");
}

$product = mysqli_fetch_assoc($result);

$colors = [];
$sizes  = [];

$stmt2 = mysqli_prepare($conn, "
    SELECT color, size
    FROM product_variant
    WHERE product_id = ?
");
mysqli_stmt_bind_param($stmt2, "i", $product_id);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);

while ($row = mysqli_fetch_assoc($result2)) {
    if (!in_array($row['color'], $colors)) {
        $colors[] = $row['color'];
    }
    if (!in_array($row['size'], $sizes)) {
        $sizes[] = $row['size'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($product['product_name']) ?></title>
    <link rel="stylesheet" href="../css/product_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="product-wrapper">
    <div class="product-left">
        <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
             class="product-image"
             alt="<?= htmlspecialchars($product['product_name']) ?>">
    </div>
    <div class="product-right">
        <p class="breadcrumb">
            <?= htmlspecialchars($product['category_name']) ?>
        </p>

        <h1 class="product-title">
            <?= htmlspecialchars($product['product_name']) ?>
        </h1>

        <div class="price">
            <span class="current">$<?= number_format($product['price'], 2) ?></span>
        </div>

        <form action="../utils/cart.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
            <div class="options">
                <div class="option-group">
                    <label>COLOR</label>
                    <div class="colors">
                        <?php foreach ($colors as $i => $color): ?>
                            <input type="radio"
                                   name="color"
                                   id="color<?= $i ?>"
                                   value="<?= htmlspecialchars($color) ?>"
                                   required>
                            <label for="color<?= $i ?>"
                                   style="background:<?= htmlspecialchars($color) ?>"></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="option-group">
                    <label>SIZE</label>
                    <select name="size" required>
                        <?php foreach ($sizes as $size): ?>
                            <option value="<?= htmlspecialchars($size) ?>">
                                <?= htmlspecialchars($size) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="option-group">
                    <label>QUANTITY</label>
                    <div class="qty">
                        <button type="button" onclick="this.nextElementSibling.stepDown()">-</button>
                        <input type="number" name="qty" value="1" min="1">
                        <button type="button" onclick="this.previousElementSibling.stepUp()">+</button>
                    </div>
                </div>
            </div>
            <div class="actions">
                <button type="submit" class="add-cart" id="addToCartBtn">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
            </div>
        </form>

        <p class="shipping">
            Standard delivery in 2–4 days or Premium delivery in 2–4 hours
        </p>
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

    // 3. AJAX BAG UPDATE LOGIC
    function refreshBag() {
        const container = document.getElementById('cartItemsContainer');
        const footer = document.getElementById('cartFooter');
        const supplierId = <?= $supplier_id ?>;

        fetch(`../utils/fetch_cart_drawer.php?supplier_id=${supplierId}`)
            .then(res => res.json())
            .then(data => {
                container.innerHTML = data.html;
                footer.innerHTML = data.footer;
            })
            .catch(err => {
                console.error('Error fetching bag:', err);
                container.innerHTML = '<p style="text-align:center;">Failed to load bag.</p>';
            });
    }

    // 4. ADD TO BAG CLICK HANDLER
    document.getElementById('addToCartBtn').addEventListener('click', function () {
        const qty = parseInt(qtySpan.textContent);
        const selection = variants.find(v => v.color === selectedColor && v.size === selectedSize);

        if (!selection) {
            alert("Please select a color and size first.");
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
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    // location.reload();
                    refreshBag(); // Update sidebar without page reload
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Check console: add_to_cart.php was not found or failed.");
            });
    });

    // Initialize bag on page load
    window.onload = refreshBag;
</script>
</body>
</html>
