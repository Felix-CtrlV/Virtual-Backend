
<?php

include '../../BackEnd/config/dbconfig.php';

$customer_id = $_SESSION['customer_id'] ?? 1; 
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 10;

$query = "SELECT c.cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id, v.size, v.color 
          FROM cart c 
          JOIN product_variant v ON c.variant_id = v.variant_id 
          JOIN products p ON v.product_id = p.product_id 
          WHERE c.customer_id = ? AND c.supplier_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $customer_id, $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$grand_total = 0;
$item_count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Selection</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --accent: #3498db;
            --light-bg: #f8f9fa;
            --border: #eaeaea;
            --success: #27ae60;
            --danger: #e74c3c;
        }
        
        body {
            background-color: #fafafa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .selection-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            transition: transform 0.2s ease;
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid var(--border);
        }
        
        .quantity-badge {
            background: var(--light-bg);
            border: 2px solid var(--accent);
            color: var(--accent);
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .remove-btn {
            color: var(--danger);
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            transition: 0.2s;
        }

        .remove-btn:hover { transform: scale(1.1); }
        
        .checkout-btn {
            background: linear-gradient(135deg, var(--primary), #1a2530);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }
        
        .checkout-btn:hover {
            background: linear-gradient(135deg, var(--accent), #2980b9);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .header-gradient {
            background: #e9ecef;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 25px 25px;
        }

        .variant-badge {
            background: #f0f2f5;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.75rem;
            color: #666;
            
        }
    </style>
</head>
<body>

<div class="header-gradient">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1 fw-bold">My Selection</h1>
                <span class="text-muted">
                    <i class="fas fa-shopping-bag me-2"></i><?= $item_count ?> items in your bag
                </span>
            </div>
            <a href="javascript:history.back()" class="btn btn-white shadow-sm rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <?php if ($item_count > 0): ?>
                <div class="selection-card p-4">
                    <h4 class="fw-bold mb-4">Selected Items</h4>
                    <div class="row g-3">
                        <?php while ($item = mysqli_fetch_assoc($result)): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $grand_total += $subtotal;
                        ?>
                        <div class="col-12 border-bottom pb-3 mb-3">
                            <div class="d-flex align-items-center position-relative">
                                <div class="position-relative me-3">
                                    <img src="../uploads/products/<?= $item['product_id'] ?>_<?= $item['image'] ?>" class="product-image">
                                    <div class="quantity-badge position-absolute top-0 start-100 translate-middle shadow-sm">
                                        <?= $item['quantity'] ?>
                                    </div>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>
                                    <div class="d-flex gap-2 mb-2">
                                        <?php if ($item['size']): ?> <span class="variant-badge">Size: <?= $item['size'] ?></span> <?php endif; ?>
                                        <?php if ($item['color']): ?> <span class="variant-badge">Color: <?= $item['color'] ?></span> <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center border rounded-pill bg-light">
                                            <button onclick="updateCartQty(<?= $item['cart_id'] ?>, <?= $item['quantity'] - 1 ?>)" class="btn btn-sm px-3"><i class="fas fa-minus small"></i></button>
                                            <span class="fw-bold px-2"><?= $item['quantity'] ?></span>
                                            <button onclick="updateCartQty(<?= $item['cart_id'] ?>, <?= $item['quantity'] + 1 ?>)" class="btn btn-sm px-3"><i class="fas fa-plus small"></i></button>
                                        </div>
                                        <div class="text-end">
                                            <span class="text-muted small d-block">Subtotal</span>
                                            <span class="fw-bold text-primary">$<?= number_format($subtotal, 2) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <button onclick="handleRemove(<?= $item['cart_id'] ?>)" class="remove-btn ms-3">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="selection-card p-5 text-center">
                    <i class="fas fa-shopping-cart fa-3x text-light mb-3"></i>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted">Looks like you haven't added anything yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <div class="selection-card p-4 sticky-top" style="z-index:0;">
                <h4 class="fw-bold mb-4 pb-2 border-bottom">Order Summary</h4>
                
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Subtotal (<?= $item_count ?> items)</span>
                    <span class="fw-bold">$<?= number_format($grand_total, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Shipping</span>
                    <span class="text-success small">Calculated at checkout</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="h5 fw-bold mb-0">Total</span>
                    <span class="h4 fw-bold text-primary mb-0">$<?= number_format($grand_total, 2) ?></span>
                </div>
                
                <button class="checkout-btn mb-2" 
                        onclick="location.href='checkout.php?supplier_id=<?= $supplier_id ?>'"
                        <?= $grand_total == 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-shield-alt"></i>CheckOut
                </button>
                
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>

<script>
function updateCartQty(cartId, newQty) {
    if (newQty < 1) {
        handleRemove(cartId);
        return;
    }

    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', newQty);

    // Path ကို သေချာစစ်ပါ။ /malltiverse/frontend/ ဆိုတာ Project folder အမည်ဖြစ်ရပါမယ်။
    // အကယ်၍ 404 ပြနေသေးရင် 'utils/update_cart_qty.php' (သို့) '/malltiverse/frontend/utils/update_cart_qty.php' စမ်းကြည့်ပါ
    fetch('../utils/update_cart_qty.php', {
        method: 'POST',
        body: formData
    })
    .then(res => {
        if(!res.ok) throw new Error('File not found (404)');
        return res.json();
    })
    .then(data => {
        if (data.status === 'success') {
            location.reload(); 
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        console.error("Error details:", err);
        Swal.fire('Error', 'Could not update quantity. Please check if the file exists.', 'error');
    });
}

function handleRemove(cartId) {
    Swal.fire({
        title: 'Remove Item?',
        text: "Are you sure you want to remove this?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        confirmButtonText: 'Yes, remove it'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('cart_id', cartId);
            fetch('../utils/removeFromCart.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                }
            });
        }
    });
}
</script>

</body>
</html>
<!DOCTYPE html><!--Toast Notification-->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Selection</title>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
     
        .swal2-popup { font-family: 'Segoe UI', sans-serif !important; }
        :root {
            --primary: #2c3e50;
            --accent: #3498db;
            --light-bg: #f8f9fa;
            --border: #eaeaea;
            --success: #27ae60;
            --danger: #e74c3c;
        }
      
        body { background-color: #fafafa; font-family: 'Segoe UI', sans-serif; }
        .selection-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .product-image { width: 100px; height: 100px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border); }
        .quantity-badge { background: var(--light-bg); border: 2px solid var(--accent); color: var(--accent); width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem; }
        .checkout-btn { background: linear-gradient(135deg, var(--primary), #1a2530); color: white; border: none; padding: 16px; border-radius: 12px; font-weight: 600; width: 100%; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .header-gradient { background: #e9ecef; padding: 40px 0; margin-bottom: 30px; border-radius: 0 0 25px 25px; }
        .variant-badge { background: #f0f2f5; padding: 3px 8px; border-radius: 5px; font-size: 0.75rem; color: #666; }
        .remove-btn { color: var(--danger); background: none; border: none; cursor: pointer; }
    </style>
</head>
<body>

<script>

function updateCartQty(cartId, newQty) {
    
    if (newQty < 1) {
       
        handleRemove(cartId);
        return;
    }

   
    Swal.fire({
        title: 'Updating...',
        text: 'Please wait a moment',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', newQty);

    fetch('../utils/update_cart_qty.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 800,
                timerProgressBar: true
            });

            Toast.fire({
                icon: 'success',
                title: 'Updated!'
            }).then(() => {
                location.reload();
                
                
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Connection failed', 'error');
    });
}
// ၂။ Remove Item Function
function handleRemove(cartId) {
    Swal.fire({
        title: 'Remove Item?',
        text: "Are you sure you want to delete this?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#2c3e50',
        confirmButtonText: 'Yes, remove it'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.showLoading(); 
            const formData = new FormData();
            formData.append('cart_id', cartId);

            fetch('../utils/removeFromCart.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    Swal.fire('Error', 'Could not remove item', 'error');
                }
            });
        }
    });
}
</script>
</body>
</html>