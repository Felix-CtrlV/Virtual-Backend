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

$cart_items = [];
while ($item = mysqli_fetch_assoc($result)) {
    $cart_items[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart | NeoShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #7209b7;
            --gradient-1: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --gradient-2: linear-gradient(135deg, #7209b7 0%, #f72585 100%);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            --text-primary: #1a1a2e;
            --text-secondary: #6c757d;
            --bg-primary: #f8f9fa;
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --radius-lg: 20px;
            --radius-md: 12px;
            --radius-sm: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--bg-gradient);
            font-family: 'Outfit', sans-serif;
            color: var(--text-primary);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }
        
        .bg-gradient {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: float 20s infinite ease-in-out;
        }
        
        .gradient-1 {
            background: var(--gradient-1);
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .gradient-2 {
            background: var(--gradient-2);
            bottom: -100px;
            right: -100px;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -30px) scale(1.1); }
            66% { transform: translate(-30px, 40px) scale(0.9); }
        }
        
        /* Floating Particles */
        .particles {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.2;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        /* Glass Header */
        .glass-header {
            backdrop-filter: blur(20px);
            background: var(--glass-bg);
            border-bottom: 1px solid var(--glass-border);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            margin-bottom: 40px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Space Grotesk', monospace;
            font-size: 28px;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        
        .cart-info {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .cart-icon-wrapper {
            position: relative;
        }
        
        .cart-icon {
            width: 48px;
            height: 48px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 20px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .cart-icon:hover {
            transform: translateY(-2px);
            box-shadow: var(--glass-shadow);
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--gradient-2);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(242, 5, 133, 0.3);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateX(-4px);
            box-shadow: var(--glass-shadow);
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 40px;
            text-align: center;
        }
        
        .page-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 12px;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 18px;
            font-weight: 400;
        }
        
        /* Cart Layout */
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-bottom: 60px;
        }
        
        @media (max-width: 1024px) {
            .cart-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Glass Cards */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(31, 38, 135, 0.2);
        }
        
        /* Cart Items */
        .cart-items-container {
            position: relative;
        }
        
        .section-header {
            padding: 28px 32px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .title-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-1);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .items-count {
            font-size: 16px;
            color: var(--text-secondary);
            font-weight: 400;
        }
        
        .cart-items-list {
            padding: 8px 0;
        }
        
        /* Cart Item */
        .cart-item {
            padding: 24px 32px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .cart-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--gradient-1);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .cart-item:hover::before {
            opacity: 1;
        }
        
        .cart-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-md);
            object-fit: cover;
            margin-right: 24px;
            border: 1px solid var(--glass-border);
            background: white;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        
        .cart-item:hover .product-image {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .product-info {
            flex: 1;
            min-width: 0;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-variants {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        
        .variant-tag {
            padding: 6px 12px;
            background: rgba(67, 97, 238, 0.1);
            border: 1px solid rgba(67, 97, 238, 0.2);
            border-radius: 20px;
            font-size: 13px;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .item-actions {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .qty-btn {
            width: 36px;
            height: 36px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }
        
        .qty-btn:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }
        
        .qty-display {
            min-width: 50px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
        }
        
        .item-pricing {
            margin-left: auto;
            text-align: right;
        }
        
        .price-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }
        
        .price-amount {
            font-size: 22px;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .remove-btn {
            width: 40px;
            height: 40px;
            background: rgba(247, 37, 133, 0.1);
            border: 1px solid rgba(247, 37, 133, 0.2);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--danger);
            transition: all 0.3s ease;
            margin-left: 16px;
        }
        
        .remove-btn:hover {
            background: var(--danger);
            color: white;
            transform: rotate(90deg);
        }
        
        /* Order Summary */
        .order-summary-container {
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .summary-content {
            padding: 32px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .summary-label {
            color: var(--text-secondary);
            font-size: 15px;
        }
        
        .summary-value {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .total-row {
            margin: 32px 0;
            padding-top: 24px;
            border-top: 2px solid var(--glass-border);
        }
        
        .total-label {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .total-amount {
            font-size: 32px;
            font-weight: 800;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .checkout-action {
            margin-top: 32px;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 18px;
            background: var(--gradient-1);
            border: none;
            border-radius: var(--radius-md);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }
        
        .checkout-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .checkout-btn:hover::before {
            left: 100%;
        }
        
        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(67, 97, 238, 0.4);
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 16px;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--glass-shadow);
        }
        
        .empty-icon {
            font-size: 80px;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 24px;
            display: inline-block;
        }
        
        .empty-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text-primary);
        }
        
        .empty-description {
            color: var(--text-secondary);
            max-width: 400px;
            margin: 0 auto 32px;
            line-height: 1.6;
        }
        
        .explore-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 32px;
            background: var(--gradient-1);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .explore-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(67, 97, 238, 0.4);
        }
        
      
        
        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide-in {
            animation: slideIn 0.6s ease-out;
        }
        
        .stagger-item {
            opacity: 0;
            transform: translateX(-20px);
            animation: slideIn 0.5s ease-out forwards;
        }
        
        /* Loading Overlay */
        .glass-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(67, 97, 238, 0.1);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }
            
            .page-title {
                font-size: 36px;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .product-image {
                width: 100%;
                height: 200px;
                margin-right: 0;
            }
            
            .item-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .item-pricing {
                margin-left: 0;
            }
        }
        
        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: var(--gradient-2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(242, 5, 133, 0.3);
            transition: all 0.3s ease;
            z-index: 90;
            text-decoration: none;
        }
        
        .fab:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 12px 35px rgba(242, 5, 133, 0.4);
        }
    </style>
</head>
<body>

<div class="animated-bg">
    <div class="bg-gradient gradient-1"></div>
    <div class="bg-gradient gradient-2"></div>
</div>

<div class="particles" id="particlesContainer"></div>

<div class="glass-loading" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<div class="glass-header slide-in">
    <div class="container">
        <div class="header-content">
            <a href="#" class="logo"><?= htmlspecialchars($supplier['tags'] ?? '') ?></a>
            <div class="cart-info">
                <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Continue Shopping
                </a>
                <div class="cart-icon-wrapper">
                    <div class="cart-icon">
                        <i class="fas fa-shopping-bag"></i>
                        <?php if ($item_count > 0): ?>
                        <span class="cart-count"><?= $item_count ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="page-header slide-in">
        <h1 class="page-title">Your Shopping Cart</h1>
        <p class="page-subtitle">Review & manage your selected items</p>
    </div>

    <?php if ($item_count > 0): ?>
    <div class="cart-grid">
        <div class="cart-items-container">
            <div class="glass-card">
                <div class="section-header">
                    <div class="section-title">
                        <div class="title-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        Selected Items
                        <span class="items-count">(<?= $item_count ?>)</span>
                    </div>
                    <button class="back-btn" onclick="window.location.href='?supplier_id=<?= $supplier_id ?>&page=products'">
                        <i class="fas fa-plus"></i>
                        Add More
                    </button>
                </div>
                
                <div class="cart-items-list">
                    <?php 
                    $grand_total = 0;
                    foreach($cart_items as $index => $item): 
                        $subtotal = $item['price'] * $item['quantity'];
                        $grand_total += $subtotal;
                    ?>
                    <div class="cart-item stagger-item" style="animation-delay: <?= $index * 0.05 ?>s">
                        <img src="../uploads/products/<?= $item['product_id'] ?>_<?= $item['image'] ?>" 
                             class="product-image" 
                             alt="<?= htmlspecialchars($item['product_name']) ?>"
                             onerror="this.src='https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'">
                        
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($item['product_name']) ?></h3>
                            
                            <div class="product-variants">
                                <?php if ($item['size']): ?>
                                <span class="variant-tag">
                                    <i class="fas fa-ruler"></i>
                                    <?= $item['size'] ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($item['color']): ?>
                                <span class="variant-tag">
                                    <i class="fas fa-palette"></i>
                                    <?= $item['color'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-actions">
                                <div class="quantity-controls">
                                    <button class="qty-btn" onclick="updateCartQty(<?= $item['cart_id'] ?>, <?= $item['quantity'] - 1 ?>)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="qty-display"><?= $item['quantity'] ?></span>
                                    <button class="qty-btn" onclick="updateCartQty(<?= $item['cart_id'] ?>, <?= $item['quantity'] + 1 ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                
                                <div class="item-pricing">
                                    <div class="price-label">Subtotal</div>
                                    <div class="price-amount">$<?= number_format($subtotal, 2) ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <button class="remove-btn" onclick="handleRemove(<?= $item['cart_id'] ?>)">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="order-summary-container">
            <div class="glass-card slide-in" style="animation-delay: 0.2s">
                <div class="section-header">
                    <div class="section-title">
                        <div class="title-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        Order Summary
                    </div>
                </div>
                
                <div class="summary-content">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value">$<?= number_format($grand_total, 2) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Shipping</span>
                        <span class="summary-value" style="color: var(--success);">
                            <i class="fas fa-shipping-fast"></i> Free
                        </span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Tax</span>
                        <span class="summary-value">$<?= number_format($grand_total * 0.08, 2) ?></span>
                    </div>
                    
                    <div class="summary-row total-row">
                        <span class="total-label">Total</span>
                        <span class="total-amount">$<?= number_format($grand_total * 1.08, 2) ?></span>
                    </div>
                    
                    <div class="checkout-action">
                        <a href="../utils/accessCheckout.php?supplier_id=<?= $supplier_id ?>" style="text-decoration: none;">
                            <button class="checkout-btn">
                                <i class="fas fa-lock"></i>
                               Secure Checkout
                            </button>
                        </a>
                        
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>256-bit SSL Encryption</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="empty-state slide-in">
        <div class="empty-icon">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <h2 class="empty-title">Your Cart is Empty</h2>
        <p class="empty-description">
            Looks like you haven't added any items to your cart yet. 
            Start shopping to fill it with amazing products!
        </p>
        <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="explore-btn">
            <i class="fas fa-store"></i>
            Explore Products
        </a>
    </div>
    <?php endif; ?>
    
   

<a href="?supplier_id=<?= $supplier_id ?>&page=products" class="fab">
    <i class="fas fa-store"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Create floating particles
function createParticles() {
    const container = document.getElementById('particlesContainer');
    const particleCount = 30;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        // Random position
        particle.style.left = Math.random() * 100 + 'vw';
        particle.style.top = Math.random() * 100 + 'vh';
        
        // Random size
        const size = Math.random() * 3 + 2;
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';
        
        // Random color
        const colors = ['#4361ee', '#3a0ca3', '#7209b7', '#f72585'];
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];
        
        // Random animation
        const duration = Math.random() * 20 + 10;
        particle.style.animation = `float ${duration}s infinite ease-in-out`;
        
        container.appendChild(particle);
    }
}

// Show loading
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Hide loading
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Animate button click
function animateButton(button) {
    button.style.transform = 'scale(0.95)';
    setTimeout(() => {
        button.style.transform = '';
    }, 150);
}

// Update quantity
async function updateCartQty(cartId, newQty) {
    if (newQty < 1) {
        handleRemove(cartId);
        return;
    }
    
    const button = event.target.closest('.qty-btn');
    if (button) animateButton(button);
    
    showLoading();
    
    try {
        const formData = new FormData();
        formData.append('cart_id', cartId);
        formData.append('quantity', newQty);
        
        const response = await fetch('../utils/update_cart_qty.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        hideLoading();
        
        if (data.status === 'success') {
            // Animate quantity display
            const qtyDisplay = button.closest('.quantity-controls').querySelector('.qty-display');
            qtyDisplay.style.transform = 'scale(1.3)';
            qtyDisplay.style.color = 'var(--primary)';
            
            setTimeout(() => {
                qtyDisplay.style.transform = 'scale(1)';
                qtyDisplay.style.color = '';
            }, 300);
            
            // Show success animation
            Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: 'Quantity updated successfully',
                showConfirmButton: false,
                timer: 1500,
                background: 'var(--glass-bg)',
                backdropFilter: 'blur(10px)',
                color: 'var(--text-primary)',
                customClass: {
                    popup: 'glass-card'
                }
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to update quantity',
                confirmButtonColor: 'var(--primary)',
                background: 'var(--glass-bg)',
                color: 'var(--text-primary)'
            });
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Unable to connect to server',
            confirmButtonColor: 'var(--primary)'
        });
    }
}

// Handle item removal
async function handleRemove(cartId) {
    const itemElement = event.target.closest('.cart-item');
    const removeBtn = event.target.closest('.remove-btn');
    
    if (removeBtn) animateButton(removeBtn);
    
    Swal.fire({
        title: 'Remove Item?',
        html: `
            <div style="font-size: 48px; color: var(--danger); margin-bottom: 20px;">
                <i class="fas fa-trash-alt"></i>
            </div>
            <p>This item will be removed from your cart.</p>
        `,
        showCancelButton: true,
        confirmButtonColor: 'var(--danger)',
        cancelButtonColor: 'var(--text-secondary)',
        confirmButtonText: 'Remove',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        background: 'var(--glass-bg)',
        backdropFilter: 'blur(10px)',
        color: 'var(--text-primary)',
        customClass: {
            popup: 'glass-card'
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            // Store item data before removal for calculations
            const itemPrice = parseFloat(itemElement.querySelector('.price-amount').textContent.replace('$', ''));
            const itemQuantity = parseInt(itemElement.querySelector('.qty-display').textContent);
            const itemSubtotal = itemPrice;
            
            // Animate removal
            itemElement.style.opacity = '0';
            itemElement.style.transform = 'translateX(-50px)';
            itemElement.style.height = '0';
            itemElement.style.padding = '0';
            itemElement.style.margin = '0';
            itemElement.style.border = 'none';
            
            setTimeout(() => {
                itemElement.style.display = 'none';
            }, 300);
            
            showLoading();
            
            try {
                const formData = new FormData();
                formData.append('cart_id', cartId);
                
                const response = await fetch('../utils/removeFromCart.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                hideLoading();
                
                if (data.status === 'success') {
                    // Update cart count
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        let count = parseInt(cartCount.textContent) - 1;
                        if (count > 0) {
                            cartCount.textContent = count;
                            cartCount.style.transform = 'scale(1.5)';
                            setTimeout(() => {
                                cartCount.style.transform = 'scale(1)';
                            }, 300);
                            
                            // Update order summary
                            updateOrderSummary(-itemSubtotal);
                        } else {
                            // Last item removed - hide cart and show empty state
                            cartCount.remove();
                            showEmptyCartState();
                        }
                    }
                    
                    // Success message
                   /* Swal.fire({
                        icon: 'success',
                        title: 'Removed',
                        text: 'Item removed from cart',
                        showConfirmButton: false,
                        timer: 1500,
                        background: 'var(--glass-bg)',
                        color: 'var(--text-primary)'
                    });*/
                }
            } catch (error) {
                hideLoading();
                
                // Restore item if error
                itemElement.style.display = '';
                itemElement.style.opacity = '1';
                itemElement.style.transform = '';
                itemElement.style.height = '';
                itemElement.style.padding = '';
                itemElement.style.margin = '';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to remove item',
                    confirmButtonColor: 'var(--danger)'
                });
            }
        }
    });
}

// Update order summary dynamically
function updateOrderSummary(subtotalChange) {
    // Get current values
    const subtotalElement = document.querySelector('.summary-row:nth-child(1) .summary-value');
    const taxElement = document.querySelector('.summary-row:nth-child(3) .summary-value');
    const totalElement = document.querySelector('.total-amount');
    
    if (!subtotalElement || !taxElement || !totalElement) return;
    
    // Parse current values
    const currentSubtotal = parseFloat(subtotalElement.textContent.replace('$', ''));
    const currentTax = parseFloat(taxElement.textContent.replace('$', ''));
    const currentTotal = parseFloat(totalElement.textContent.replace('$', ''));
    
    // Calculate new values
    const newSubtotal = currentSubtotal + subtotalChange;
    const newTax = newSubtotal * 0.08;
    const newTotal = newSubtotal + newTax;
    
    // Animate the value changes
    animateValueChange(subtotalElement, currentSubtotal, newSubtotal, '$');
    animateValueChange(taxElement, currentTax, newTax, '$');
    animateValueChange(totalElement, currentTotal, newTotal, '$');
    
    // Also update the item count in section header
    const itemsCountElement = document.querySelector('.items-count');
    if (itemsCountElement) {
        const currentCountMatch = itemsCountElement.textContent.match(/\((\d+)\)/);
        if (currentCountMatch) {
            const currentCount = parseInt(currentCountMatch[1]);
            const newCount = currentCount - 1;
            if (newCount > 0) {
                itemsCountElement.textContent = `(${newCount})`;
                
                // Add animation effect
                itemsCountElement.style.transform = 'scale(1.3)';
                itemsCountElement.style.color = 'var(--primary)';
                setTimeout(() => {
                    itemsCountElement.style.transform = 'scale(1)';
                    itemsCountElement.style.color = '';
                }, 300);
            }
        }
    }
}

// Animate value changes with counting effect
function animateValueChange(element, oldValue, newValue, prefix = '') {
    const duration = 500; // ms
    const steps = 20;
    const stepValue = (newValue - oldValue) / steps;
    let currentStep = 0;
    
    element.style.color = newValue > oldValue ? 'var(--success)' : 'var(--danger)';
    element.style.fontWeight = '700';
    
    const timer = setInterval(() => {
        currentStep++;
        const currentValue = oldValue + (stepValue * currentStep);
        
        if (currentStep >= steps) {
            clearInterval(timer);
            element.textContent = `${prefix}${newValue.toFixed(2)}`;
            
            // Return to normal style
            setTimeout(() => {
                element.style.color = '';
                element.style.fontWeight = '';
            }, 300);
        } else {
            element.textContent = `${prefix}${currentValue.toFixed(2)}`;
        }
    }, duration / steps);
}

// Show empty cart state when last item is removed
function showEmptyCartState() {
    // Hide the cart grid
    const cartGrid = document.querySelector('.cart-grid');
    if (cartGrid) {
        cartGrid.style.opacity = '0';
        cartGrid.style.transform = 'translateY(20px)';
        cartGrid.style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            cartGrid.style.display = 'none';
            
            // Show empty state
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state slide-in';
            emptyState.innerHTML = `
                <div class="empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h2 class="empty-title">Your Cart is Empty</h2>
                <p class="empty-description">
                    Looks like you haven't added any items to your cart yet. 
                    Start shopping to fill it with amazing products!
                </p>
                <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="explore-btn">
                    <i class="fas fa-store"></i>
                    Explore Products
                </a>
            `;
            
            emptyState.style.opacity = '0';
            emptyState.style.transform = 'translateY(20px)';
            
            cartGrid.parentNode.insertBefore(emptyState, cartGrid.nextSibling);
            
            // Animate in the empty state
            setTimeout(() => {
                emptyState.style.opacity = '1';
                emptyState.style.transform = 'translateY(0)';
                emptyState.style.transition = 'all 0.6s ease';
            }, 100);
        }, 300);
    }
}
</script>

</body>
</html>