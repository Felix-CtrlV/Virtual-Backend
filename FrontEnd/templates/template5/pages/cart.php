<?php
include '../../BackEnd/config/dbconfig.php';

require_once __DIR__ . '/../../../utils/Ordered.php'; 

$customer_id = $_SESSION['customer_id'] ?? 1; 
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 10;

if (isset($_GET['payment_status']) && $_GET['payment_status'] === 'success') {    
    $is_ordered = placeOrder($conn, $customer_id, $supplier_id);
    
    if ($is_ordered) {
        echo "<script>alert('Your Orders Items Successfully!'); window.location.href='?supplier_id=$supplier_id&page=cart';</script>";
        exit();
    }
}



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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
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

        .swal2-popup { font-family: 'Segoe UI', sans-serif !important; }
        
        .selection-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
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
            cursor: pointer;
        }
        
        .checkout-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--accent), #2980b9);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .checkout-btn:disabled { opacity: 0.6; cursor: not-allowed; }

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
    <div class="container" style="max-width: 1140px; margin: auto; padding: 0 15px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="margin: 0; font-weight: bold;">My Selection</h1>
                <span style="color: #6c757d;">
                    <i class="fas fa-shopping-bag" style="margin-right: 8px;"></i><?= $item_count ?> items in your bag
                </span>
            </div>
            <a href="javascript:history.back()" style="text-decoration: none; background: white; padding: 10px 20px; border-radius: 50px; color: black; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="container" style="max-width: 1140px; margin: 0 auto 50px auto; padding: 0 15px;">
    <?php if ($item_count > 0): ?>
    <div style="display: flex; flex-wrap: wrap; gap: 30px;">
        <div style="flex: 2; min-width: 300px;">
            <div class="selection-card" style="padding: 25px;">
                <h4 style="font-weight: bold; margin-bottom: 25px;">Selected Items</h4>
                
                <?php while ($item = mysqli_fetch_assoc($result)): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $grand_total += $subtotal;
                ?>
                <div style="border-bottom: 1px solid var(--border); padding-bottom: 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; position: relative;">
                        <div style="position: relative; margin-right: 20px;">
                            <img src="../uploads/products/<?= $item['product_id'] ?>_<?= $item['image'] ?>" class="product-image">
                            <div class="quantity-badge" style="position: absolute; top: -10px; right: -10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <?= $item['quantity'] ?>
                            </div>
                        </div>
                        
                        <div style="flex-grow: 1;">
                            <h6 style="font-weight: bold; margin: 0 0 5px 0;"><?= htmlspecialchars($item['product_name']) ?></h6>
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <?php if ($item['size']): ?> <span class="variant-badge">Size: <?= $item['size'] ?></span> <?php endif; ?>
                                <?php if ($item['color']): ?> <span class="variant-badge">Color: <?= $item['color'] ?></span> <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 25px; background: #f8f9fa;">
                                    <button onclick="updateCartQty(<?= $item['cart_id'] ?>, <?= $item['quantity'] - 1 ?>)" style="border:none; background:none; padding: 5px 15px; cursor:pointer;"><i class="fas fa-minus small"></i></button>
                                    <span style="font-weight: bold; padding: 0 10px;"><?= $item['quantity'] ?></span>
                                    <button onclick="updateCartQty(<?= $item['cart_id'] ?>, <?= $item['quantity'] + 1 ?>)" style="border:none; background:none; padding: 5px 15px; cursor:pointer;"><i class="fas fa-plus small"></i></button>
                                </div>
                                <div style="text-align: right;">
                                    <span style="color: #6c757d; font-size: 0.8rem; display: block;">Subtotal</span>
                                    <span style="font-weight: bold; color: var(--accent);">$<?= number_format($subtotal, 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <button onclick="handleRemove(<?= $item['cart_id'] ?>)" class="remove-btn" style="margin-left: 20px;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <div style="flex: 1; min-width: 250px;">
            <div class="selection-card" style="padding: 25px; position: sticky; top: 20px;">
                <h4 style="font-weight: bold; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Order Summary</h4>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span style="color: #6c757d;">Subtotal (<?= $item_count ?> items)</span>
                    <span style="font-weight: bold;">$<?= number_format($grand_total, 2) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span style="color: #6c757d;">Shipping</span>
                    <span style="color: var(--success); font-size: 0.8rem;">Calculated at checkout</span>
                </div>
                <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <span style="font-size: 1.2rem; font-weight: bold;">Total</span>
                    <span style="font-size: 1.4rem; font-weight: bold; color: var(--accent);">$<?= number_format($grand_total, 2) ?></span>
                </div>

                <a href="../utils/accessCheckout.php?supplier_id=<?= $supplier_id ?>" style="text-decoration: none;">
                    <button class="checkout-btn" <?= $grand_total == 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-shield-alt"></i> Confirm Order & Checkout
                    </button>
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="selection-card" style="padding: 80px; text-align: center;">
            <i class="fas fa-shopping-cart fa-4x" style="color: #ddd; margin-bottom: 20px;"></i>
            <h3>Your selection is empty</h3>
            <p style="color: #6c757d; margin-bottom: 30px;">Please choose another more luxury watches.</p>

           <a href="?supplier_id=<?= $supplier_id ?>&page=products"
            class="checkout-btn" 
            style="display: inline-flex; width: auto; padding: 12px 30px; text-decoration: none;">
            Shop Now
            </a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            
            let timerInterval;
            Swal.fire({
                icon: 'success',
                title: 'Updated Successfully!',
                html: 'Refreshing in <b></b> milliseconds.',
                timer: 1000, 
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                    const b = Swal.getHtmlContainer().querySelector('b');
                    timerInterval = setInterval(() => {
                        b.textContent = Swal.getTimerLeft();
                    }, 100);
                },
                willClose: () => {
                    clearInterval(timerInterval);
                }
            }).then((result) => {
               
                if (result.dismiss === Swal.DismissReason.timer || result.isConfirmed) {
                    location.reload();
                }
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
            
            Swal.fire({
                title: 'Removing...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('cart_id', cartId);

            fetch('../utils/removeFromCart.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    
                    let timerInterval;
                    Swal.fire({
                        icon: 'success',
                        title: 'Removed!',
                        html: 'Item has been removed. Refreshing in <b></b> ms.',
                        timer: 1000, 
                        timerProgressBar: true,
                        didOpen: () => {
                            Swal.showLoading();
                            const b = Swal.getHtmlContainer().querySelector('b');
                            timerInterval = setInterval(() => {
                                b.textContent = Swal.getTimerLeft();
                            }, 100);
                        },
                        willClose: () => {
                            clearInterval(timerInterval);
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', 'Could not remove item', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Connection failed', 'error');
            });
        }
    });
}
</script>
</body>
</html>