<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<?php
// product_details.php - Logic Header
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    exit("<div class='container mt-5 text-center'><h4>Invalid Product ID.</h4><a href='index.php' class='btn btn-outline-dark'>Back to Shop</a></div>");
}

// Fetch Product and Category
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


$stmt2 = mysqli_prepare($conn, "SELECT variant_id, color, size FROM product_variant WHERE product_id = ?");
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

<div class="container mt-5">
    <div class="row g-5">
        <div class="col-lg-7 mb-4">
            <div class="product-image-box shadow-sm">
                <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                     class="img-fluid w-100" alt="<?= htmlspecialchars($product['product_name']) ?>">
            </div>
        </div>

        <div class="col-lg-5 ps-lg-5"> <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-secondary text-uppercase small fw-semibold">Shop</a></li>
            <li class="breadcrumb-item active text-uppercase small" aria-current="page">
                <?= htmlspecialchars($product['category_name']) ?>
            </li>
        </ol>
    </nav>

    <h1 class="display-6 fw-bold mb-2 text-dark"><?= htmlspecialchars($product['product_name']) ?></h1>
    <p class="price-tag mb-4 text-primary">$<?= number_format($product['price'], 2) ?></p>
    
    <div class="mb-4">
        <label class="fw-bold small text-uppercase text-muted mb-2">Description</label>
        <p class="text-muted lh-base" style="font-size: 0.95rem;">
            <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
        </p>
    </div>
    <div class="mb-4">
    <label class="fw-bold small text-uppercase text-muted mb-2">Select Color</label>
    <div class="d-flex align-items-center">
        <?php foreach ($colors as $color): ?>
            <?php $uniqueId = 'color_' . preg_replace('/[^a-zA-Z0-9]/', '', $color); ?>
            
            <input type="radio" name="color_option" id="<?= $uniqueId ?>" value="<?= htmlspecialchars($color) ?>" class="color-radio">
            
            <label for="<?= $uniqueId ?>" 
                   class="color-label" 
                   style="background-color: <?= htmlspecialchars($color) ?>;" 
                   title="<?= htmlspecialchars($color) ?>">
            </label>
        <?php endforeach; ?>
    </div>
</div>
    <br>
    <div class="row g-3 mb-4">
        <div class="col-7">
            <label class="fw-bold small text-uppercase text-muted mb-2">Select Size</label>
            <select id="sizeSelect" class="form-select shadow-sm">
                <option value="" selected disabled>Choose your size</option>
                <?php foreach ($sizes as $size): ?>
                    <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <br>
        <div class="col-5">
            <label class="fw-bold small text-uppercase text-muted mb-2">Quantity</label>
            <input type="number" id="qtyInput" class="form-control shadow-sm text-center" value="1" min="1">
        </div>
    </div>
    <br>

    <input type="hidden" id="supplier_id" value="<?= htmlspecialchars($product['supplier_id']) ?>">
    <br>
    <button id="addToCartBtn" class="btn btn-dark btn-lg w-100 shadow-sm border-0 py-3 mt-2">
        <i class="fas fa-shopping-bag me-2"></i> ADD TO CART
    </button>
</div>
       
                            
         
    
     <!--<div class="cart-header d-flex justify-content-between align-items-center">
        <span class="header-title">My Selection</span>
        <i class="fas fa-shopping-bag" style="color: var(--gold-dark);"></i>
    </div>

    <div id="cartitem" class="custom-scrollbar">
        <p class="text-center text-muted py-5 small" style="font-family: 'Inter', sans-serif; letter-spacing: 1px;">
            <i class="fas fa-spinner fa-spin me-2"></i>Loading selection...
        </p>
    </div>

    <div class="cart-footer">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <span class="subtotal-label">Subtotal</span>
            <h4 class="subtotal-amount">$<span id="cart-subtotal">0.00</span></h4>
        </div>
        
        <button class="btn btn-luxury w-100" onclick="location.href='checkout.php'">
            PROCEED TO CHECKOUT
        </button>
    </div>

</div>-->
                       
<script>
   
    const allVariants = <?= json_encode($variants_data) ?>;
    const sizeSelect = document.getElementById('sizeSelect');
    const colorRadios = document.querySelectorAll('input[name="color_option"]');

    
    function updateSizes() {
        const colorInput = document.querySelector('input[name="color_option"]:checked');
        if (!colorInput) return;

        const selectedColor = colorInput.value;
        
      
        sizeSelect.innerHTML = '<option value="" selected disabled>Choose your size</option>';

       
        const availableVariants = allVariants.filter(v => v.color === selectedColor);

        availableVariants.forEach(v => {
            const option = document.createElement('option');
            option.value = v.size;
            option.textContent = v.size;
            sizeSelect.appendChild(option);
        });

        if (availableVariants.length === 1) {
            sizeSelect.selectedIndex = 1;
        }
    }

      colorRadios.forEach(radio => {
        radio.addEventListener('change', updateSizes);
    });

   
    window.addEventListener('DOMContentLoaded', () => {
        updateSizes();
        refreshBag();
    });


    
    document.getElementById('addToCartBtn').addEventListener('click', function () {
        const selectedSize = sizeSelect.value;
        const colorInput = document.querySelector('input[name="color_option"]:checked');
        const selectedColor = colorInput ? colorInput.value : null;
        
        const qty = parseInt(document.getElementById('qtyInput').value) || 1;
        const supplierId = document.getElementById('supplier_id').value;

       
        if (!selectedColor) {
            Swal.fire({ icon: 'warning', title: 'Select Color', text: 'Please choose a color.' });
            return; 
        }

        if (!selectedSize) {
            Swal.fire({ icon: 'warning', title: 'Select Size', text: 'Please choose a size.' });
            return; 
        }

       
        const variant = allVariants.find(v => v.size === selectedSize && v.color === selectedColor);

        if (!variant) {
            Swal.fire({ icon: 'error', title: 'Not Available', text: 'This combination is out of stock.' });
            return;
        }

      
        const formData = new FormData();
        formData.append('variant_id', variant.variant_id);
        formData.append('supplier_id', supplierId);
        formData.append('quantity', qty);

        fetch('../utils/add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Cart',
                    text: 'The item has been added.',
                    showConfirmButton: false,
                    timer: 1500 
                });
                refreshBag(); 
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
        });
    });

    
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
            })
            .catch(err => console.error("Error fetching cart:", err));
    }


    
    function handleRemove(cartId) {
        Swal.fire({
            title: 'Are You Sure?',
            text: "Delete this item from your selection?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#212529',
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                const supplierId = document.getElementById('supplier_id').value;
                const formData = new FormData();
                formData.append('cart_id', cartId);
                formData.append('supplier_id', supplierId);

                fetch('../utils/removeFromCart.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        refreshBag(); // Update Badge & Drawer
                    }
                });
            }
        });
    }

    
    window.addEventListener('DOMContentLoaded', refreshBag);
</script>