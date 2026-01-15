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
$item_count = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
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
        
        .selection-card:hover {
            transform: translateY(-2px);
        }
        
        .supplier-badge {
            background: linear-gradient(135deg, var(--accent), #2980b9);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid var(--border);
            transition: transform 0.3s ease;
        }
        
        .product-image:hover {
            transform: scale(1.05);
        }
        
        .quantity-badge {
            background: var(--light-bg);
            border: 2px solid var(--accent);
            color: var(--accent);
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .remove-btn {
            color: var(--danger);
            background: none;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .remove-btn:hover {
            background: rgba(231, 76, 60, 0.1);
            transform: scale(1.05);
        }
        
        .checkout-btn {
            background: linear-gradient(135deg, var(--primary), #1a2530);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        
        .checkout-btn:hover {
            background: linear-gradient(135deg, var(--accent), #2980b9);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }
        
        .checkout-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #7f8c8d;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .price-tag {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 4px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .variant-info {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }
        
        .variant-badge {
            background: var(--light-bg);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            color: #5d6d7e;
        }
        
        .header-gradient {
            background-color: gainsboro;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        
        .floating-action {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        
        @media (max-width: 768px) {
            .product-image {
                width: 80px;
                height: 80px;
            }
            
            .header-gradient {
                padding: 20px 0;
                margin-bottom: 20px;
            }
            
            .floating-action {
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>

<div class="header-gradient">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
              <h1 class="mb-2 text-white" style="font-size: 2.5rem;">My Selection</h1>
                <div class="d-flex align-items-center gap-3">
                    <!--<span class="supplier-badge">
                        <i class="fas fa-store"></i>
                        Supplier #<?= $supplier_id ?>
                    </span>-->
                    <span class="text-black-60">
                        <i class="fas fa-shopping-bag me-2"></i>
                        <?= mysqli_num_rows($result) ?> items
                    </span>
                </div>
            </div>
            <a href="javascript:history.back()" class="btn btn-light rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <!-- Items List -->
        <div class="col-lg-8">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="selection-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">Selected Items</h3>
                        <span class="price-tag">
                            <i class="fas fa-tag me-2"></i>
                            <?= mysqli_num_rows($result) ?> Products
                        </span>
                    </div>
                    
                    <div class="row g-4">
                        <?php while ($item = mysqli_fetch_assoc($result)): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $grand_total += $subtotal;
                            $item_count++;
                        ?>
                        <div class="col-12">
                            <div class="d-flex align-items-center p-3 border rounded-3 bg-white position-relative">
                                <!-- Product Image -->
                                <div class="position-relative me-3">
                                    <img src="../uploads/products/<?= $item['product_id'] ?>_<?= $item['image'] ?>" 
                                         class="product-image" 
                                         alt="<?= htmlspecialchars($item['product_name']) ?>">
                                    <div class="quantity-badge position-absolute top-0 start-100 translate-middle">
                                        <?= $item['quantity'] ?>
                                    </div>
                                </div>
                                
                                <!-- Product Details -->
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($item['product_name']) ?></h5>
                                    
                                    <div class="variant-info">
                                        <?php if ($item['size']): ?>
                                            <span class="variant-badge">
                                                <i class="fas fa-ruler me-1"></i><?= $item['size'] ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($item['color']): ?>
                                            <span class="variant-badge">
                                                <i class="fas fa-palette me-1"></i><?= $item['color'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="h5 fw-bold text-accent mb-0">
                                            $<?= number_format($item['price'], 2) ?>
                                            <small class="text-muted d-block fs-6 fw-normal">
                                                Each
                                            </small>
                                        </div>
                                        
                                        <div class="h5 fw-bold mb-0">
                                            $<?= number_format($subtotal, 2) ?>
                                            <small class="text-muted d-block fs-6 fw-normal text-end">
                                                Total
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Remove Button -->
                                <button onclick="handleRemove(<?= $item['cart_id'] ?>)" 
                                        class="remove-btn position-absolute top-0 end-0 m-3">
                                    <i class="fas fa-trash"></i>
                                    <span class="d-none d-md-inline">Remove</span>
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="selection-card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <h3 class="mb-3">Your Selection is Empty</h3>
                        <p class="text-muted mb-4">No items found from this supplier.</p>
                       <!-- <a href="products.php" class="btn btn-primary px-4 py-2 rounded-pill">
                            <i class="fas fa-store me-2"></i>
                            Browse Products
                        </a>-->
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Summary -->
       <div class="col-lg-4">
    <div class="selection-card p-4 sticky-top" style="top: 20px; z-index: 0;">
        <h4 class="fw-bold mb-4 pb-3 border-bottom">
            <i class="fas fa-receipt me-2"></i>
            Order Summary
        </h4>
        
        </div>

                
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Items (<?= $item_count ?>)</span>
                        <span class="fw-semibold">$<?= number_format($grand_total, 2) ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Shipping</span>
                        <span class="fw-semibold text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Calculated at checkout
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <span class="text-muted">Tax</span>
                        <span class="fw-semibold">$0.00</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
                        <span class="h5 fw-bold">Total Amount</span>
                        <span class="h3 fw-bold text-primary">$<?= number_format($grand_total, 2) ?></span>
                    </div>
                </div>
                
                <button class="checkout-btn mb-3" 
                        onclick="location.href='checkout.php?supplier_id=<?= $supplier_id ?>'"
                        <?= $grand_total == 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-lock"></i>
                    PROCEED TO SECURE CHECKOUT
                </button>
                
             </div>


<!-- Floating Action Button for Mobile -->
<?php if ($grand_total > 0): ?>
<div class="floating-action d-lg-none">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold">Total:</span>
                <span class="h5 mb-0 fw-bold text-primary">$<?= number_format($grand_total, 2) ?></span>
            </div>
            <button class="checkout-btn" 
                    onclick="location.href='checkout.php?supplier_id=<?= $supplier_id ?>'">
                <i class="fas fa-bolt"></i>
                CHECKOUT
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function handleRemove(cartId) {
        Swal.fire({
            title: 'Remove Item?',
            text: "This item will be removed from your selection",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            background: '#fff',
            iconColor: '#e74c3c'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('cart_id', cartId);
                formData.append('supplier_id', <?= $supplier_id ?>);

                fetch('../utils/removeFromCart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show success message
                        Swal.fire({
                            title: 'Removed!',
                            text: 'Item has been removed',
                            icon: 'success',
                            confirmButtonColor: '#27ae60',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Something went wrong', 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Network error occurred', 'error');
                });
            }
        });
    }
    
    // Add animation on scroll
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.selection-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate__animated',);
        });
    });
</script>

<!-- Add animate.css for animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

</body>
</html>