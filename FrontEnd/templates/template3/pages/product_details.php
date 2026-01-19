<?php
include("../../BackEnd/config/dbconfig.php");

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
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
$sizes = [];
$variants = [];

$stmt2 = mysqli_prepare($conn, "
    SELECT variant_id, color, size, quantity
    FROM product_variant
    WHERE product_id = ?
");
mysqli_stmt_bind_param($stmt2, "i", $product_id);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);

while ($row = mysqli_fetch_assoc($result2)) {
    $variants[] = $row;
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
    <style>
        .qty-error { color: #ff4d4d; font-size: 0.9rem; margin-top: 8px; display: none; font-weight: 600; }
        .add-cart:disabled { background-color: #d1d1d1 !important; cursor: not-allowed; opacity: 0.7; }
        .stock-info { font-size: 0.9rem; color: #555; margin-top: 5px; font-weight: 500; }
    </style>
</head>

<body>
    <div class="product-wrapper">
        <div class="product-left">
            <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                class="product-image" alt="<?= htmlspecialchars($product['product_name']) ?>">
        </div>
        <div class="product-right">
            <p class="breadcrumb"><?= htmlspecialchars($product['category_name']) ?></p>

            <h1 class="product-title"><?= htmlspecialchars($product['product_name']) ?></h1>

            <div class="price">
                <span class="current">$<?= number_format($product['price'], 2) ?></span>
            </div>

            <form id="addToCartForm">
                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                <div class="options">
                    <div class="option-group">
                        <label>COLOR</label>
                        <div class="colors">
                            <?php foreach ($colors as $i => $color): ?>
                                <input type="radio" name="color" id="color<?= $i ?>" value="<?= htmlspecialchars($color) ?>" required>
                                <label for="color<?= $i ?>" style="background:<?= htmlspecialchars($color) ?>"></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="option-group">
                        <label>SIZE</label>
                        <select name="size" id="sizeSelect" required>
                            <option value="">Select Color First</option>
                        </select>
                        <div id="stockDisplay" class="stock-info"></div>
                    </div>

                    <div class="option-group">
                        <label>QUANTITY</label>
                        <div class="qty">
                            <button type="button" onclick="adjustQty(-1)">-</button>
                            <input type="number" name="qty" id="qtyInput" value="1" min="1">
                            <button type="button" onclick="adjustQty(1)">+</button>
                        </div>
                        <span id="qtyErrorMessage" class="qty-error">Quantity is out of our stock!</span>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="add-cart" id="addToCartBtn">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </div>
            </form>

            <p class="shipping">Standard delivery in 2–4 days or Premium delivery in 2–4 hours</p>
        </div>
    </div>

    <script>
        const allVariants = <?= json_encode($variants) ?>;
        const supplierId = <?= isset($product['supplier_id']) ? $product['supplier_id'] : 0 ?>;
        
        const colorInputs = document.querySelectorAll('input[name="color"]');
        const sizeSelect = document.getElementById('sizeSelect');
        const qtyInput = document.getElementById('qtyInput');
        const addToCartBtn = document.getElementById('addToCartBtn');
        const qtyErrorMessage = document.getElementById('qtyErrorMessage');
        const stockDisplay = document.getElementById('stockDisplay');

        let currentVariant = null;

        colorInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                const selectedColor = e.target.value;
                const filtered = allVariants.filter(v => v.color === selectedColor);

                sizeSelect.innerHTML = '<option value="">Select Size</option>';
                sizeSelect.disabled = false;
                stockDisplay.textContent = '';
                resetFormState();

                filtered.forEach(v => {
                    const option = document.createElement('option');
                    option.value = v.size;
                    option.textContent = `${v.size} ${v.quantity <= 0 ? '(Out of Stock)' : ''}`;
                    if(v.quantity <= 0) option.disabled = true;
                    sizeSelect.appendChild(option);
                });
            });
        });

        sizeSelect.addEventListener('change', (e) => {
            const selectedColor = document.querySelector('input[name="color"]:checked').value;
            const selectedSize = e.target.value;

            currentVariant = allVariants.find(v => v.color === selectedColor && v.size === selectedSize);

            if (currentVariant) {
                stockDisplay.textContent = `Stock available: ${currentVariant.quantity}`;
                validateStock(); 
            }
        });

        qtyInput.addEventListener('input', validateStock);

        function validateStock() {
            if (!currentVariant) return;

            const requestedQty = parseInt(qtyInput.value) || 0;
            const maxStock = parseInt(currentVariant.quantity);

            if (requestedQty > maxStock) {
                qtyErrorMessage.style.display = 'block';
                addToCartBtn.disabled = true;
            } else {
                qtyErrorMessage.style.display = 'none';
                addToCartBtn.disabled = false;
            }
        }

        function resetFormState() {
            qtyInput.value = 1;
            qtyErrorMessage.style.display = 'none';
            addToCartBtn.disabled = false;
            currentVariant = null;
        }

        function adjustQty(amount) {
            let val = parseInt(qtyInput.value) || 1;
            let newVal = val + amount;
            if (newVal >= 1) {
                qtyInput.value = newVal;
                validateStock();
            }
        }

        document.getElementById('addToCartForm').addEventListener('submit', function (e) {
            e.preventDefault();

            if (!currentVariant) {
                alert("Please select color and size.");
                return;
            }

            const formData = new FormData();
            formData.append('variant_id', currentVariant.variant_id);
            formData.append('supplier_id', supplierId);
            formData.append('quantity', qtyInput.value);

            fetch('../utils/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message || "Added to cart successfully!");
                } else {
                    alert("Error: " + (data.message || "Something went wrong"));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("System error. Check console.");
            });
        });
    </script>
</body>

</html>