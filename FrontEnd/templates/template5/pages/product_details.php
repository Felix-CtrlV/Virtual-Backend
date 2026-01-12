
<?php
// product_details.php

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    echo "<div class='container mt-5 text-center'><h4>Invalid Product ID.</h4><a href='index.php' class='btn btn-outline-dark'>Back to Shop</a></div>";
    return; 
}

// Product & Category Query
$stmt = mysqli_prepare($conn, "
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN category c ON p.category_id = c.category_id 
    WHERE p.product_id = ?
");

mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo "<div class='container mt-5 text-center'><h4>Product not found.</h4><a href='index.php' class='btn btn-outline-dark'>Back to Shop</a></div>";
    return;
}

// Fetch Variants
$stmt2 = mysqli_prepare($conn, "SELECT variant_id, color, size FROM product_variant WHERE product_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $product_id);
mysqli_stmt_execute($stmt2);
$variants_result = mysqli_stmt_get_result($stmt2); 

$variants_data = []; 
$sizes = [];
while ($v = mysqli_fetch_assoc($variants_result)) {
    $variants_data[] = $v; 
    if (!empty($v['size']) && !in_array($v['size'], $sizes)) {
        $sizes[] = $v['size'];
    }
}
?>

<style>
    
    .product-image-box { border-radius: 8px; overflow: hidden; background: #f8f9fa; }
    .product-image-box img { transition: transform 0.3s ease; }
    .product-image-box img:hover { transform: scale(1.05); }
    .rolex-cart { border: 1px solid #eee; border-radius: 10px; background: #fff; position: sticky; top: 20px; }
    .breadcrumb { background: transparent; padding: 0; font-size: 0.9rem; }
    .price-tag { font-size: 1.8rem; color: #2c3e50; font-weight: 700; }
    .btn-checkout { background: #1a1a1a; color: white; border-radius: 0; }
    .btn-checkout:hover { background: #333; color: #fff; }
</style>

<div class="container mt-5">
    
    
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="product-image-box shadow-sm">
                <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                     class="img-fluid w-100" alt="<?= htmlspecialchars($product['product_name']) ?>">
            </div>
        </div>

        <div class="col-lg-6 col-md-12">
            
            <div class="row">
                <div class="col-12 mb-4">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Shop</a></li>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($product['category_name']) ?></li>
                        </ol>
                    </nav>

                    <h1 class="display-6 fw-bold mb-2"><?= htmlspecialchars($product['product_name']) ?></h1>
                    <p class="price-tag mb-3">$<?= number_format($product['price'], 2) ?></p>
                    
                    <hr>

                    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?></p>

                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="fw-bold mb-2 small text-uppercase">Select Size</label>
                            <select id="sizeSelect" class="form-select" required>
                                <option value="">Choose Size</option>
                                <?php foreach ($sizes as $size): ?>
                                    <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="fw-bold mb-2 small text-uppercase">Quantity</label>
                            <input type="number" id="qtyInput" class="form-control" value="1" min="1" max="99">
                        </div>
                    </div>

                    <input type="hidden" id="supplier_id" value="<?= htmlspecialchars($product['supplier_id']) ?>">

                    <button id="addToCartBtn" type="button" class="btn btn-dark btn-lg w-100 mt-2 mb-4">
                        <i class="fas fa-shopping-bag me-2"></i>ADD TO CART
                    </button>
                </div>

               
               <div class="sidebar rolex-cart shadow-sm p-4">
    <div class="head border-bottom pb-3 mb-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold text-uppercase" style="letter-spacing: 1px; color: #555;">My Selection</span>
        <i class="fas fa-shopping-cart text-muted"></i>
    </div>
    
    <div id="cartitem" style="max-height: 350px; overflow-y: auto; overflow-x: hidden; width: 100%;">
        <p class="empty-msg text-center text-muted small py-4 italic">Your selection is currently empty.</p>
    </div>

    <div class="subtotal-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold" style="color: #888;">SUBTOTAL</h5>
            <h4 class="mb-0 fw-bold">$ <span id="cart-subtotal">0.00</span></h4>
        </div>
        <button class="btn btn-checkout py-3 text-uppercase" onclick="window.location.href='checkout.php'">
            Continue to Checkout
        </button>
       
    </div>
</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                                </div>

<script>
const allVariants = <?= json_encode($variants_data) ?>;

// 1. Add to Cart Logic
document.getElementById('addToCartBtn').addEventListener('click', function () {
    const selectedSize = document.getElementById('sizeSelect').value;
    const qty = parseInt(document.getElementById('qtyInput').value) || 1;
    const supplierId = document.getElementById('supplier_id').value;

    const variant = allVariants.find(v => v.size === selectedSize);

    if (!selectedSize || !variant) {
        alert("Please select a size first!");
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
            refreshBag(); 
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
function refreshBag() {
   
    const supplierId = document.getElementById('supplier_id').value; 
    
    fetch(`../utils/fetch_cart_drawer.php?supplier_id=${supplierId}&t=${new Date().getTime()}`)
        .then(res => res.json())
        .then(data => {
           
            const cartItemContainer = document.getElementById('cartitem');
            if (cartItemContainer) {
                cartItemContainer.innerHTML = data.html;
            }
            
           
            const totalElement = document.getElementById('cart-subtotal'); 
            if (totalElement) {
            
                let totalValue = parseFloat(data.total_raw) || 0;
                totalElement.textContent = totalValue.toLocaleString('en-US', {
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 2
                });
            }
        })
        .catch(err => console.error("Error:", err));
}


window.onload = refreshBag;

</script>

