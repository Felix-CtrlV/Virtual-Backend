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



                <div class="quantity-add">

                    <div class="quantity-selector">

                        <button class="qty-btn" id="decrease">-</button>

                        <span id="qty">1</span>

                        <button class="qty-btn" id="increase">+</button>

                    </div>

                    <button class="addtobag_btn" id="addToCartBtn">ADD TO BAG</button>

                </div>

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



    // 3. AJAX BAG UPDATE LOGIC

    function refreshBag() {

        const container = document.getElementById('cartItemsContainer');

        const footer = document.getElementById('cartFooter');

        const badge = document.querySelector('.cart-badge'); // Select the navbar badge

        const supplierId = <?= $supplier_id ?>;



        fetch(`../utils/fetch_cart_drawer.php?supplier_id=${supplierId}`)

            .then(res => res.json())

            .then(data => {

                container.innerHTML = data.html;

                footer.innerHTML = data.footer;



                // Update the Navbar Badge count

                if (badge) {

                    badge.textContent = data.count; // We need to add 'count' to the JSON response

                    badge.style.display = data.count > 0 ? 'inline-block' : 'none';

                }

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

                    alert("Successful: " + data.message);

                    openBag();

                } else {

                    alert("Error: " + data.message);

                }

            })


            //catch error


    });



    // Initialize bag on page load

    window.onload = refreshBag;

</script>

</body>



</html>