<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Login Status
$isLoggedIn = isset($_SESSION['customer_id']) ? 'true' : 'false';
$currentUrl = urlencode($_SERVER['REQUEST_URI']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['product_name']) ?></title>
    <link rel="stylesheet" href="../css/product_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Existing Styles */
        .qty-error { color: #ff4d4d; font-size: 0.9rem; margin-top: 8px; display: none; font-weight: 600; }
        .add-cart:disabled { background-color: #d1d1d1 !important; cursor: not-allowed; opacity: 0.7; }
        .stock-info { font-size: 0.9rem; color: #555; margin-top: 5px; font-weight: 500; }        
        /* Success Modal */
        .cart-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: none; justify-content: center; align-items: center; z-index: 9999; }
        .cart-modal { background: white; padding: 40px; border-radius: 10px; text-align: center; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: fadeIn 0.3s ease; }
        .success-icon { width: 80px; height: 80px; background-color: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; position: relative; }         
        .success-icon::after { content: ''; width: 60px; height: 60px; background-color: #2196f3; border-radius: 50%; position: absolute; }
        .success-icon i { color: white; font-size: 30px; z-index: 1; }
        .login-prompt-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4); 
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10001;
            backdrop-filter: blur(15px);
        }

        .login-prompt-card {
            background: rgba(255, 255, 255, 0.1); 
            width: 100%;
            max-width: 400px;
            padding: 45px 35px;
            border-radius: 28px;
            text-align: center;
            
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .login-prompt-card h2 { 
            color: #ffffff; font-size: 30px; margin-bottom: 12px; font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .login-prompt-card p { 
            color: #ffffff; opacity: 0.9; margin-bottom: 35px; font-size: 16px;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
        }
        
        .modal-action-btn {
            display: block; width: 100%; padding: 14px; margin-bottom: 15px;
            border-radius: 50px; font-size: 15px; font-weight: 600;
            text-decoration: none; transition: all 0.3s ease; border: none; cursor: pointer;
            text-align: center;
        }

        .btn-login-alt { 
            background: rgba(255, 255, 255, 0.15); color: #ffffff; 
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .btn-login-alt:hover { 
            background: #ffffff; color: #000000;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.4);
        }

        .btn-create-alt { 
            background: transparent; color: #ffffff; 
            border: 1px solid rgba(255, 255, 255, 0.3); 
        }

        .btn-create-alt:hover { border-color: #ffffff; background: rgba(255, 255, 255, 0.1); }
        .divider-container { 
            color: #ffffff; font-weight: 500; opacity: 0.7; display: flex; align-items: center; margin: 25px 0; 
        }

        .divider-container::before, .divider-container::after { content: ''; flex: 1; border-bottom: 1px solid rgba(255, 255, 255, 0.4); }
        .divider-container:not(:empty)::before { margin-right: 15px; }
        .divider-container:not(:empty)::after { margin-left: 15px; }      
    </style>
</head>

<body>
    <div class="login-prompt-overlay" id="loginPromptModal">
        <div class="login-prompt-card">
            <!-- <span class="close-login" onclick="toggleLoginModal(false)">&times;</span> -->
            <h2>Log back in</h2>
            <p>Choose an account to continue.</p>
            
            <div class="divider-container">OR</div>

            <div class="modal-buttons">
                <a href="../customerLogin.php?return_url=<?= $currentUrl ?>" class="modal-action-btn btn-login-alt">Log in to another account</a>
                <a href="../customerRegister.php" class="modal-action-btn btn-create-alt">Create account</a>
                <p onclick="toggleLoginModal(false)" style="cursor:pointer; margin-top:20px; font-size: 0.9rem; opacity: 0.5; transition: 0.3s;"
                    onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.5'">Cancel
                </p>
            </div>
        </div>
    </div>

    <div class="cart-modal-overlay" id="cartModal">
        <div class="cart-modal">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            <h2>Added to Cart</h2>
            <p>The item has been added.</p>
        </div>
    </div>

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
                        <span id="qtyErrorMessage" class="qty-error">Quantity is out of stock!</span>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="add-cart" id="addToCartBtn">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        const customerId = <?= isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 0 ?>;
        const ADD_TO_CART_API = "../utils/add_to_cart.php";
        const allVariants = <?= json_encode($variants) ?>;
        const supplierId = <?= isset($product['supplier_id']) ? $product['supplier_id'] : 0 ?>; 
                                
        const colorInputs = document.querySelectorAll('input[name="color"]');
        const sizeSelect = document.getElementById('sizeSelect');
        const qtyInput = document.getElementById('qtyInput');
        const addToCartBtn = document.getElementById('addToCartBtn');
        const qtyErrorMessage = document.getElementById('qtyErrorMessage');
        const stockDisplay = document.getElementById('stockDisplay');
        const cartModal = document.getElementById('cartModal');
        const loginPromptModal = document.getElementById('loginPromptModal');

        let currentVariant = null;

        function toggleLoginModal(show) {
            loginPromptModal.style.display = show ? 'flex' : 'none';
        }

        colorInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                const selectedColor = e.target.value.toString().trim().toLowerCase();
                const filtered = allVariants.filter(v => v.color.toString().trim().toLowerCase() === selectedColor);

                sizeSelect.innerHTML = '<option value="">Select Size</option>';
                sizeSelect.disabled = false;
                stockDisplay.textContent = '';
                qtyInput.value = 1;
                qtyErrorMessage.style.display = 'none';
                currentVariant = null;
                addToCartBtn.disabled = true;

                if (filtered.length === 0) {
                    sizeSelect.innerHTML = '<option value="">No sizes available</option>';
                    sizeSelect.disabled = true;
                } else {
                    filtered.forEach(v => {
                        const option = document.createElement('option');
                        option.value = v.size;
                        const stockQty = parseInt(v.quantity) || 0;
                        const isOutOfStock = stockQty <= 0;
                        option.textContent = `${v.size} ${isOutOfStock ? '(Out of Stock)' : ''}`;
                        if (isOutOfStock) option.disabled = true;
                        sizeSelect.appendChild(option);
                    });
                }
            });
        });

        sizeSelect.addEventListener('change', (e) => {
            const checkedColorInput = document.querySelector('input[name="color"]:checked');
            if (!checkedColorInput) return;

            const selectedColor = checkedColorInput.value.toString().trim().toLowerCase();
            const selectedSize = e.target.value;

            currentVariant = allVariants.find(v => 
                v.color.toString().trim().toLowerCase() === selectedColor && 
                v.size.toString() === selectedSize.toString()
            );

            if (currentVariant) {
                stockDisplay.textContent = `Stock available: ${currentVariant.quantity}`;
                validateStock(); 
            } else {
                stockDisplay.textContent = '';
                addToCartBtn.disabled = true;
            }
        });

        function validateStock() {
            if (!currentVariant) return;
            const requestedQty = parseInt(qtyInput.value) || 0;
            const availableQty = parseInt(currentVariant.quantity) || 0;

            if (requestedQty > availableQty) {
                qtyErrorMessage.textContent = `Only ${availableQty} items available.`;
                qtyErrorMessage.style.display = 'block';
                addToCartBtn.disabled = true;
            } else if (requestedQty < 1) {
                addToCartBtn.disabled = true;
            } else {
                qtyErrorMessage.style.display = 'none';
                addToCartBtn.disabled = false;
            }
        }

        function adjustQty(amount) {
            let val = parseInt(qtyInput.value) || 1;
            let newVal = val + amount;
            if (newVal >= 1) {
                qtyInput.value = newVal;
                validateStock();
            }
        }

        const cancelBtn = document.getElementById('cancelModalBtn'); 
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => toggleLoginModal(false));
        }
                                
        window.addEventListener('click', (e) => {
            if (e.target === loginPromptModal) {
                toggleLoginModal(false);
            }
        });

        document.getElementById('addToCartForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Check Login Status - Trigger Modal instead of Alert
            if (!customerId || customerId === 0) {
                toggleLoginModal(true);
                return;
            }

            if (!currentVariant) {
                alert("Please select both color and size.");
                return;
            }

            const formData = new FormData();
            formData.append('variant_id', currentVariant.variant_id);
            formData.append('supplier_id', supplierId);
            formData.append('quantity', qtyInput.value);

            fetch(ADD_TO_CART_API, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    cartModal.style.display = 'flex';
                    setTimeout(() => { cartModal.style.display = 'none'; }, 2000);
                } else {
                    alert(data.message || "Error adding to cart");
                }
            })
            .catch(err => {
                console.error('Error:', err);
            });
        });
                                
    </script>
</body>
</html>