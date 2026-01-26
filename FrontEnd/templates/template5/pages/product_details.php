<?php

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    exit("<div class='container mt-5 text-center'><h4>Invalid Product ID.</h4><a href='index.php' class='btn btn-outline-dark'>Back to Shop</a></div>");
}


$stmt = mysqli_prepare($conn, "
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN category c ON p.category_id = c.category_id 
    WHERE p.product_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    exit("<div class='container mt-5 text-center'><h4>Product not found.</h4><a href='index.php' class='btn btn-outline-dark'>Back to Shop</a></div>");
}


$stmt2 = mysqli_prepare($conn, "SELECT variant_id, color, size, quantity FROM product_variant WHERE product_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $product_id);
mysqli_stmt_execute($stmt2);
$variants_result = mysqli_stmt_get_result($stmt2);

$variants_data = [];
$sizes = [];
$colors = []; 

while ($v = mysqli_fetch_assoc($variants_result)) {
    $variants_data[] = $v;
    if (!empty($v['size'])) $sizes[] = $v['size'];
    if (!empty($v['color'])) $colors[] = $v['color']; 
}
$sizes = array_unique($sizes);
$colors = array_unique($colors); 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div class="container mt-5">
    <div class="row g-5">
        <div class="col-lg-7 mb-4">
            <div class="product-image-box shadow-sm">
                <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                     class="img-fluid w-100" alt="<?= htmlspecialchars($product['product_name']) ?>">
            </div>
        </div>

        <div class="col-lg-5 ps-lg-5">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb bg-transparent p-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-secondary text-uppercase small fw-semibold">Shop</a></li>
                    <li class="breadcrumb-item active text-uppercase small" aria-current="page">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </li>
                </ol>
            </nav>

            <h1 class="display-6 fw-bold mb-2 text-dark"><?= htmlspecialchars($product['product_name']) ?></h1>
            <h3 class="price-tag mb-4 text-primary">$<?= number_format($product['price'], 2) ?></h3>
            
            <div class="mb-4">
                <label class="fw-bold small text-uppercase text-muted mb-2">Description</label>
                <p class="text-muted lh-base" style="font-size: 0.95rem;">
                    <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
                </p>
            </div>

            <div class="mb-4">
                <label class="fw-bold small text-uppercase text-muteadd-to-carAd mb-2">Select Color</label>
                <div class="d-flex align-items-center">
                    <?php foreach ($colors as $color): ?>
                        <?php $uniqueId = 'color_' . preg_replace('/[^a-zA-Z0-9]/', '', $color); ?>
                        <input type="radio" name="color_option" id="<?= $uniqueId ?>" value="<?= htmlspecialchars($color) ?>" class="color-radio">
                        <label for="<?= $uniqueId ?>" class="color-label" style="background-color: <?= htmlspecialchars($color) ?>;" title="<?= htmlspecialchars($color) ?>"></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-7">
                    <label class="fw-bold small text-uppercase text-muted mb-2">Select Size</label>
                    
                    <select id="sizeSelect" class="form-select shadow-sm" onchange="displayStock()">
                       <option value="">Select Color First</option>
                    </select>
                    <div id="stockDisplay" class="mt-1 small fw-bold text-secondary"></div>
                </div>

                <div class="col-5">
                    <label class="fw-bold small text-uppercase text-muted mb-2">Quantity</label>
                    <div class="qty-container">
                        <button type="button" class="btn-qty" onclick="changeQty(-1)">-</button>
                        <input type="number" id="qtyInput" value="1" min="1" readonly>
                        <button type="button" class="btn-qty" onclick="changeQty(1)">+</button>
                    </div>
                    <div id="qtyErrorMessage" class="text-danger small mt-2" style="display: none; font-weight: 500;">
                        Quantity is out of our stock!
                    </div>
                </div>
            </div>

            <input type="hidden" id="supplier_id" value="<?= htmlspecialchars($product['supplier_id']) ?>">
            
       <button id="addToCartBtn" 
        data-stock="<?= $product['stock_available'] ?? 0 ?>"
        class="add-to-cart-btn btn btn-dark w-100 py-3">
    ADD TO CART
</button>
        </div>
    </div>
</div>

<script>
    const allVariants = <?= json_encode($variants_data) ?>;
    const sizeSelect = document.getElementById('sizeSelect');
    const colorRadios = document.querySelectorAll('input[name="color_option"]');
    let currentVariant = null;

    window.addEventListener('DOMContentLoaded', () => {
        sizeSelect.disabled = true;
        refreshBag();
    });

    
    sizeSelect.addEventListener('mousedown', function(e) {
        const colorInput = document.querySelector('input[name="color_option"]:checked');
        if (!colorInput) {
            e.preventDefault(); 
            this.blur(); 
            Swal.fire({
                icon: 'info',
                title: 'Note',
                text: 'Please choose your color first!',
                confirmButtonColor: '#212529'
            });
        }
    });

    function updateSizes() {
        const colorInput = document.querySelector('input[name="color_option"]:checked');
        if (!colorInput) return;

        
        sizeSelect.disabled = false;

        const selectedColor = colorInput.value;
        sizeSelect.innerHTML = '<option value="" selected disabled>Choose your size</option>';

        const availableVariants = allVariants.filter(v => v.color === selectedColor);

        availableVariants.forEach(v => {
            const option = document.createElement('option');
            option.value = v.size;
            option.textContent = v.size;
            sizeSelect.appendChild(option);
        });

        document.getElementById('stockDisplay').innerText = "";
        document.getElementById('qtyInput').value = 1;
        document.getElementById('qtyErrorMessage').style.display = 'none';
        currentVariant = null;
    }

    /*pyin */
   function displayStock() {
    const selectedSize = sizeSelect.value;
    const colorInput = document.querySelector('input[name="color_option"]:checked');
    const stockDisplay = document.getElementById('stockDisplay');
    const addToCartBtn = document.getElementById('addToCartBtn');

    if (colorInput && selectedSize) {
        
        currentVariant = allVariants.find(v => 
            String(v.size).trim() === String(selectedSize).trim() && 
            String(v.color).trim() === String(colorInput.value).trim()
        );

        if (currentVariant) {
           
            fetch(`../utils/get_variant_stock.php?id=${currentVariant.variant_id}`)
            .then(res => res.json())
            .then(data => {
                const realStock = data.stock;
                
                currentVariant.quantity = realStock; 

                stockDisplay.innerHTML = `<i class="fas fa-box-open me-1"></i> Stock available: ${realStock}`;
                
                if (realStock <= 0) {
                    stockDisplay.className = "mt-1 small fw-bold text-danger";
                    stockDisplay.innerText = "Out of Stock";
                    addToCartBtn.disabled = true;
                    addToCartBtn.innerText = "OUT OF STOCK";
                } else {
                    stockDisplay.className = "mt-1 small fw-bold text-secondary";
                    addToCartBtn.disabled = false;
                    addToCartBtn.innerText = "ADD TO CART";
                }
                validateQty();
            });
        }
    }
}

    function changeQty(amount) {
        const qtyInput = document.getElementById('qtyInput');
        let currentVal = parseInt(qtyInput.value) || 1;
        let newVal = currentVal + amount;
        
        if (newVal >= 1) {
            qtyInput.value = newVal;
            validateQty();
        }
    }

    function validateQty() {
        const qtyInput = document.getElementById('qtyInput');
        const errorMsg = document.getElementById('qtyErrorMessage');
        const qty = parseInt(qtyInput.value) || 1;
        let safeStock = (currentVariant && parseInt(currentVariant.quantity) > 0) ? parseInt(currentVariant.quantity) : 1;

        if (qty > safeStock) {
            errorMsg.style.display = 'block';
        } else {
            errorMsg.style.display = 'none';  
        }
    }

    document.getElementById('addToCartBtn').addEventListener('click', function () {
        const qty = parseInt(document.getElementById('qtyInput').value) || 1;
        const supplierId = document.getElementById('supplier_id').value;

        if (!currentVariant) {
            Swal.fire({ 
                icon: 'warning', 
                title: 'Selection Missing', 
                text: 'Please select color and size.' 
            });
            return;
        }

        let checkStock = (parseInt(currentVariant.quantity) > 0) ? parseInt(currentVariant.quantity) : 1;

     if (qty > checkStock) {
    Swal.fire({
        icon: 'error',
        title: 'Limited Availability',
        text: `We only have ${checkStock} pieces left in stock for this products.`,
        
        customClass: {
            popup: 'swal-frost-popup',
            title: 'swal-frost-title',
            htmlContainer: 'swal-frost-content',
            confirmButton: 'swal-frost-btn'
        },
        buttonsStyling: false,
        showClass: {
            popup: 'animate__animated animate__fadeIn' 
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOut'
        }
    });
    return;
}
        const formData = new FormData();
        formData.append('variant_id', currentVariant.variant_id);
        formData.append('supplier_id', supplierId);
        formData.append('quantity', qty);

        fetch('../utils/add_to_cart.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Added!', text: 'Item added to selection.', timer: 1500, showConfirmButton: false });
                refreshBag();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        })
        .catch(err => console.error("Error:", err));
    });

    colorRadios.forEach(radio => radio.addEventListener('change', updateSizes));

    function refreshBag() {
        const supplierId = document.getElementById('supplier_id').value;
        const cartContainer = document.getElementById('cartitem');
        fetch(`../utils/fetch_cart_drawer.php?supplier_id=${supplierId}&t=${new Date().getTime()}`)
            .then(res => res.json())
            .then(data => {
                const cartBadge = document.getElementById('cart-badge-count');
                if (cartBadge) {
                    const count = parseInt(data.total_count) || 0;
                    cartBadge.innerText = count;
                    cartBadge.style.display = count > 0 ? 'inline-block' : 'none';
                }
                if (cartContainer) {
                    cartContainer.innerHTML = data.drawer_html || '<p class="text-center text-muted">Empty</p>';
                }
                const totalElement = document.getElementById('cart-subtotal');
                if (totalElement) {
                    const total = parseFloat(data.total) || 0;
                    totalElement.textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2 });
                }
            });
    }
</script>
<script>
function checkCurrentStock(variantId) {
    if(!variantId) return;

    
    fetch(`../../frontend/utils/get_variant_stock.php?id=${variantId}`)
    .then(res => res.json())
    .then(data => {
        const stockDisplay = document.querySelector('.stock-display'); 
        if (stockDisplay) {
            stockDisplay.innerText = `Stock available: ${data.stock}`;
            
           
            const addToCartBtn = document.querySelector('.add-to-cart-btn');
            if (data.stock <= 0) {
                stockDisplay.style.color = 'red';
                if(addToCartBtn) addToCartBtn.disabled = true;
            } else {
                stockDisplay.style.color = '';
                if(addToCartBtn) addToCartBtn.disabled = false;
            }
        }
    });
}</script>
