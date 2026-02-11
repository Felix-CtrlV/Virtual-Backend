<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['customer_id'])) {
   
    header("Location: ../utils/customerLogin.php"); 
    exit(); 
}

$customer_id = $_SESSION['customer_id'];
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php
require_once __DIR__ . '/../../../utils/Ordered.php'; 

$customer_id = $_SESSION['customer_id'];
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$company_id = isset($supplier['company_id']) ? (int)$supplier['company_id'] : 0;
if ($company_id <= 0 && $supplier_id > 0) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_id FROM companies WHERE supplier_id = $supplier_id LIMIT 1"));
    $company_id = $r ? (int)$r['company_id'] : 0;
}

if (isset($_GET['payment_status']) && $_GET['payment_status'] === 'success') {
    $is_ordered = placeOrder($conn, $customer_id, $company_id);
    
    if ($is_ordered) {
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position:'center', 
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'success',
                title: 'Order placed successfully!'
            }).then(() => {
                window.location.href = '?supplier_id=$supplier_id&page=cart';
            });
        });
    </script>";
    exit();
    }
}

$cart_query = "SELECT c.cart_id, c.quantity, p.product_name, p.price, p.image, p.product_id, 
                       v.color, v.size, v.quantity AS stock_limit 
                FROM cart c 
                JOIN product_variant v ON c.variant_id = v.variant_id 
                JOIN products p ON v.product_id = p.product_id 
                WHERE c.customer_id = ? AND c.company_id = ?"; 

$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "ii", $customer_id, $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cart_count = mysqli_num_rows($result);
$total_price = 0;
?>


<style>
    :root {
        --font-primary: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        --font-secondary: 'Space Grotesk', sans-serif;
        --color-bg: #f0f2f9;
        --color-surface: rgba(255, 255, 255, 0.85);
        --color-surface-solid: #ffffff;
        --color-primary: #6366f1;
        --color-primary-light: #818cf8;
        --color-primary-dark: #4f46e5;
        --color-secondary: #8b5cf6;
        --color-accent: #10b981;
        --color-danger: #ef4444;
        --color-text: #1f2937;
        --color-text-light: #6b7280;
        --color-text-lighter: #9ca3af;
        --color-border: rgba(209, 213, 219, 0.3);
        --color-shadow: rgba(0, 0, 0, 0.05);
        --radius-sm: 10px;
        --radius-md: 16px;
        --radius-lg: 24px;
        --radius-xl: 32px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        --shadow-sm: 0 2px 8px var(--color-shadow);
        --shadow-md: 0 8px 30px var(--color-shadow);
        --shadow-lg: 0 20px 60px var(--color-shadow);
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.4);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: var(--font-primary);
        background: var(--color-bg);
        color: var(--color-text);
        min-height: 100vh;
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

    .floating-shape {
        position: absolute;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--color-primary-light), var(--color-secondary));
        opacity: 0.1;
        filter: blur(40px);
        animation: float 20s infinite ease-in-out;
    }

    .shape-1 {
        width: 500px;
        height: 500px;
        top: -100px;
        left: -100px;
        animation-delay: 0s;
    }

    .shape-2 {
        width: 400px;
        height: 400px;
        bottom: -100px;
        right: -100px;
        animation-delay: -5s;
        background: linear-gradient(135deg, var(--color-accent), var(--color-primary));
    }

    .shape-3 {
        width: 300px;
        height: 300px;
        top: 50%;
        left: 70%;
        animation-delay: -10s;
        background: linear-gradient(135deg, var(--color-secondary), var(--color-primary-light));
    }

    @keyframes float {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        33% { transform: translate(30px, -50px) rotate(120deg); }
        66% { transform: translate(-20px, 20px) rotate(240deg); }
    }

    /* Main Container */
    .modern-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
        position: relative;
    }

    /* Header */
    .modern-header {
        margin-bottom: 50px;
        text-align: center;
    }

    .modern-header h1 {
        font-family: var(--font-secondary);
        font-size: 3.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
        letter-spacing: -0.5px;
    }

    .header-subtitle {
        color: var(--color-text-light);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Cart Stats */
    .cart-stats-modern {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 40px;
        flex-wrap: wrap;
        margin-bottom:80px;
    }

    .stat-card {
        background: var(--color-surface);
        backdrop-filter: blur(20px);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 25px 40px;
        text-align: center;
        min-width: 160px;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .stat-value {
        font-family: var(--font-secondary);
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--color-primary);
        margin-bottom: 5px;
    }

    .stat-label {
        color: var(--color-text-light);
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Main Layout */
    .modern-layout {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 40px;
        align-items: start;
    }

    /* Products Panel */
    .products-panel-modern {
        background: var(--color-surface);
        backdrop-filter: blur(20px);
        border-radius: var(--radius-xl);
        border: 1px solid var(--color-border);
        padding: 40px;
        box-shadow: var(--shadow-lg);
        position: relative;
        overflow: hidden;
    }

    .products-panel-modern::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, var(--color-primary-light) 0%, transparent 70%);
        opacity: 0.05;
        filter: blur(40px);
    }

    .panel-header-modern {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 25px;
        border-bottom: 2px solid rgba(99, 102, 241, 0.1);
    }

    .panel-title-modern {
        font-family: var(--font-secondary);
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--color-text);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .panel-title-modern i {
        color: var(--color-primary);
        background: rgba(99, 102, 241, 0.1);
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    /* Cart Items */
    .modern-item {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 30px;
        padding: 30px;
        margin-bottom: 20px;
        background: var(--color-surface-solid);
        border-radius: var(--radius-lg);
        border: 1px solid var(--color-border);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .modern-item:hover {
        transform: translateX(5px);
        box-shadow: var(--shadow-md);
        border-color: rgba(99, 102, 241, 0.2);
    }

    .item-image-modern {
        width: 120px;
        height: 120px;
        border-radius: var(--radius-md);
        overflow: hidden;
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        position: relative;
    }

    .item-image-modern img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: var(--transition-slow);
    }

    .modern-item:hover .item-image-modern img {
        transform: scale(1.05);
    }

    .item-image-modern::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        mix-blend-mode: overlay;
    }

    .item-content {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .item-header h3 {
        font-family: var(--font-secondary);
        font-size: 1.4rem;
        font-weight: 600;
        margin-bottom: 12px;
        color: var(--color-text);
    }

    .item-meta {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .meta-badge {
        padding: 6px 16px;
        background: rgba(99, 102, 241, 0.08);
        border-radius: 20px;
        font-size: 0.85rem;
        color: var(--color-primary);
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid rgba(99, 102, 241, 0.2);
    }

    .color-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .item-actions-modern {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    /* Quantity Controls */
    .quantity-controls-modern {
        display: flex;
        align-items: center;
        background: var(--color-bg);
        border-radius: 50px;
        padding: 6px;
        width: fit-content;
    }

    .qty-btn-modern {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        background: white;
        color: var(--color-primary);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }

    .qty-btn-modern:hover {
        background: var(--color-primary);
        color: white;
        transform: scale(1.1);
    }

    .qty-display-modern {
        width: 50px;
        text-align: center;
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--color-text);
    }

    /* Price Section */
    .price-section-modern {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: space-between;
        min-width: 150px;
    }

    .price-total-modern {
        font-family: var(--font-secondary);
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--color-primary);
        margin-bottom: 5px;
    }

    .price-unit-modern {
        font-size: 0.9rem;
        color: var(--color-text-light);
    }

    .item-actions-right {
        display: flex;
        gap: 10px;
    }

    .action-btn-modern {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 1px solid var(--color-border);
        background: white;
        color: var(--color-text-light);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }

    .action-btn-modern:hover {
        background: var(--color-danger);
        color: white;
        border-color: var(--color-danger);
       
    }

    .action-btn-modern.save:hover {
        background: var(--color-accent);
        border-color: var(--color-accent);
    }

    /* Order Summary */
    .summary-panel-modern {
        background: var(--color-surface);
        backdrop-filter: blur(20px);
        border-radius: var(--radius-xl);
        border: 1px solid var(--color-border);
        padding: 40px;
        box-shadow: var(--shadow-lg);
        position: sticky;
        top: 40px;
    }

    .summary-header {
        margin-bottom: 35px;
        text-align: center;
    }

    .summary-title {
        font-family: var(--font-secondary);
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--color-text);
        margin-bottom: 10px;
    }

    .summary-subtitle {
        color: var(--color-text-light);
        font-size: 0.95rem;
    }

    /* Summary Items */
    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .summary-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .summary-item.total {
        margin-top: 20px;
        padding-top: 25px;
        border-top: 2px solid rgba(99, 102, 241, 0.1);
        font-weight: 700;
        font-size: 1.3rem;
    }

    .summary-label {
        color: var(--color-text);
        font-weight: 500;
    }

    .summary-value {
        font-family: var(--font-secondary);
        font-weight: 600;
        color: var(--color-text);
    }

    .summary-value.total {
        color: var(--color-primary);
        font-size: 1.8rem;
    }

    /* Checkout Button */
    .checkout-btn-modern {
        display: block;
        width: 100%;
        padding: 20px;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: white;
        border: none;
        border-radius: var(--radius-lg);
        font-family: var(--font-secondary);
        font-size: 1.2rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        margin-top: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        position: relative;
        overflow: hidden;
        text-decoration: none
    }

    .checkout-btn-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s ease;
    }

    .checkout-btn-modern:hover::before {
        left: 100%;
    }

    .checkout-btn-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
    }

    .checkout-btn-modern:active {
        transform: translateY(-1px);
    }

    /* Security Badge */
    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
        color: var(--color-text-light);
        font-size: 0.9rem;
    }

    .security-badge i {
        color: var(--color-accent);
    }

    /* Empty State */
    .empty-state-modern {
        grid-column: 1 / -1;
        text-align: center;
        padding: 80px 40px;
        background: var(--color-surface);
        backdrop-filter: blur(20px);
        border-radius: var(--radius-xl);
        border: 1px solid var(--color-border);
        box-shadow: var(--shadow-lg);
    }

    .empty-icon-modern {
        font-size: 4rem;
        color: var(--color-primary-light);
        margin-bottom: 30px;
        opacity: 0.7;
    }

    .empty-title-modern {
        font-family: var(--font-secondary);
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--color-text);
        margin-bottom: 15px;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .empty-subtitle-modern {
        color: var(--color-text-light);
        font-size: 1.1rem;
        max-width: 500px;
        margin: 0 auto 40px;
    }

    /* Continue Shopping */
    .continue-shopping {
        text-align: center;
        margin-top: 40px;
        grid-column: 1 / -1;
    }

    .continue-link {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 15px 30px;
        background: white;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        color: var(--color-primary);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
    }

    .continue-link:hover {
        background: var(--color-primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .modern-layout {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .summary-panel-modern {
            position: static;
        }
    }

    @media (max-width: 768px) {
        .modern-header h1 {
            font-size: 2.5rem;
        }
        
        .cart-stats-modern {
            gap: 20px;
        }
        
        .stat-card {
            min-width: 140px;
            padding: 20px 30px;
        }
        
        .modern-item {
            grid-template-columns: 1fr;
            gap: 20px;
            text-align: center;
        }
        
        .item-image-modern {
            margin: 0 auto;
        }
        
        .item-actions-modern {
            justify-content: center;
        }
        
        .price-section-modern {
            align-items: center;
            text-align: center;
        }
        
        .item-actions-right {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .modern-container {
            padding: 20px 15px;
        }
        
        .modern-header h1 {
            font-size: 2rem;
        }
        
        .stat-card {
            min-width: 120px;
            padding: 15px 20px;
        }
        
        .stat-value {
            font-size: 2rem;
        }
        
        .products-panel-modern,
        .summary-panel-modern {
            padding: 25px 20px;
        }
    }

    /* Animation for items */
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modern-item {
        animation: slideUp 0.5s ease-out forwards;
        opacity: 0;
    }

    .modern-item:nth-child(1) { animation-delay: 0.1s; }
    .modern-item:nth-child(2) { animation-delay: 0.2s; }
    .modern-item:nth-child(3) { animation-delay: 0.3s; }
    .modern-item:nth-child(4) { animation-delay: 0.4s; }
    .modern-item:nth-child(5) { animation-delay: 0.5s; }

    .custom-swal-popup {
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}
.modern-layout {
    display: grid;
    grid-template-columns: 1fr 400px; 
    gap: 40px;
    align-items: start;
}

.summary-panel-modern {
    background: var(--color-surface);
    backdrop-filter: blur(20px);
    border-radius: var(--radius-xl);
    border: 1px solid var(--color-border);
    padding: 40px;
    box-shadow: var(--shadow-lg);
    position: sticky; 
    top: 40px;
}


@media (max-width: 1200px) {
    .modern-layout {
        grid-template-columns: 1fr;
    }
    .summary-panel-modern {
        position: static;
        width: 100%;
    }
}
div.swal2-container.swal2-top-end,
div.swal2-container.swal2-top {
    z-index: 99999 !important; 
}

@media (max-width: 768px) {
   
    div.swal2-container.swal2-top-end {
        top: 80px !important; 
        left: 50% !important;
        transform: translateX(-50%) !important;
        width: 90% !important;
    }
}
</style>

<!-- Animated Background -->
<div class="animated-bg">
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
</div>

<div class="modern-container">
    <?php if ($cart_count > 0): 
        
        $items = [];
        $total_price = 0;
        $total_quantity = 0;

       
        while ($item = mysqli_fetch_assoc($result)): 
            $subtotal = $item['price'] * $item['quantity'];
            $total_price += $subtotal;
            $total_quantity += $item['quantity']; 
            $items[] = $item;
        endwhile;
        
        
        $shipping = $total_price > 100 ? 0 : 9.99;
        $grand_total = $total_price + $shipping;
    ?>
        
        <div class="modern-header">
            <h1>Your Shopping Cart</h1>
            <p class="header-subtitle">Review your items and proceed to checkout</p>

    <div class="cart-stats-modern">
   <div class="stat-card">
    <div class="stat-value" id="total-qty-stat"><?= $total_quantity ?></div>
    <div class="stat-label">Total Items</div>
</div>
<div class="stat-card">
    <div class="stat-value" id="total-subtotal-stat">$<?= number_format($total_price, 2) ?></div>
    <div class="stat-label">Subtotal</div>
</div>

</div>

    <div class="modern-layout">
        <div class="products-panel-modern">
           <div class="panel-header-modern">
    <h2 class="panel-title-modern">
        <i class="fas fa-shopping-bag"></i>
        Cart Items
    </h2>
    
    <span id="total-qty-header" style="color: var(--color-primary); font-weight: 600;">
        <?= $total_quantity ?> item<?= $total_quantity > 1 ? 's' : '' ?>
    </span>
</div>

            
            <?php foreach ($items as $item): 
                $item_subtotal = $item['price'] * $item['quantity']; 
            ?>
                <div class="modern-item" data-price="<?= $item['price'] ?>">
                    <div class="item-image-modern">
                        <img src="../uploads/products/<?= $item['product_id'] ?>_<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                    </div>
                    
                    <div class="item-content">
                        <div class="item-header">
                            <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                            <div class="item-meta">
                                <span class="meta-badge"><i class="fas fa-ruler"></i> <?= htmlspecialchars($item['size']) ?></span>
                                <span class="meta-badge">
                                    <span class="color-indicator" style="background-color: <?= $item['color'] ?>;"></span>
                                    <?= htmlspecialchars($item['color']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="item-actions-modern">
                            <div class="quantity-controls-modern">
                                <button class="qty-btn-modern" onclick="updateModernQuantity(<?= $item['cart_id'] ?>, parseInt(document.getElementById('qty-<?= $item['cart_id'] ?>').innerText) - 1, <?= $item['stock_limit'] ?>)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <div class="qty-display-modern" id="qty-<?= $item['cart_id'] ?>"><?= $item['quantity'] ?></div>
                                <button class="qty-btn-modern" onclick="updateModernQuantity(<?= $item['cart_id'] ?>, parseInt(document.getElementById('qty-<?= $item['cart_id'] ?>').innerText) + 1, <?= $item['stock_limit'] ?>)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="price-section-modern">
                        <div>
                            <div class="price-total-modern" id="subtotal-<?= $item['cart_id'] ?>">$<?= number_format($item_subtotal, 2) ?></div>
                            <div class="price-unit-modern">$<?= number_format($item['price'], 2) ?> each</div>
                        </div>
                        <div class="item-actions-right">
                            <button class="action-btn-modern" onclick="confirmModernRemove(<?= $item['cart_id'] ?>)" title="Remove item">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div> 

       <div class="summary-panel-modern">
    <div class="summary-header">
        <h2 class="summary-title">Order Summary</h2>
        <p class="summary-subtitle">Complete your purchase</p>
    </div>
    
    <?php 
    $display_subtotal = ($cart_count > 0) ? $total_price : 0;
    $display_shipping = ($cart_count > 0) ? $shipping : 0;
    $display_total = ($cart_count > 0) ? $grand_total : 0;
    ?>

    <div class="summary-item">
        <span class="summary-label">Subtotal</span>
        <span class="summary-value" id="summary-subtotal">$<?= number_format($display_subtotal, 2) ?></span>
    </div>
    
    <div class="summary-item">
        <span class="summary-label">Shipping</span>
        <span class="summary-value" id="summary-shipping">
            <?= ($display_shipping > 0) ? '$' . number_format($display_shipping, 2) : ($cart_count > 0 ? 'FREE' : '$0.00') ?>
        </span>
    </div>
    
    <div class="summary-item total">
        <span class="summary-label">Total</span>
        <span class="summary-value total" id="summary-total">$<?= number_format($display_total, 2) ?></span>
    </div>
    
    <a href="../utils/accessCheckout.php?supplier_id=<?= $supplier_id ?>" 
       id="checkout-btn" 
       class="checkout-btn-modern <?= ($cart_count <= 0) ? 'disabled' : '' ?>"
       style="<?= ($cart_count <= 0) ? 'pointer-events: none; opacity: 0.5;' : '' ?>">
        <i class="fas fa-lock"></i> Secure Checkout
    </a>
</div>
        
    <div class="continue-shopping">
        <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="continue-link">
            <i class="fas fa-arrow-left"></i>
            Continue Shopping
        </a>
    </div>

    <?php else: ?>
        <div class="empty-state-modern">
            <div class="empty-icon-modern">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2 class="empty-title-modern">Your Cart is Empty</h2>
            <p class="empty-subtitle-modern">
                Looks like you haven't added any items to your cart yet. 
                Start shopping to discover amazing products!
            </p>
            <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="checkout-btn-modern" style="width: auto; display: inline-flex; padding: 15px 40px;">
                <i class="fas fa-store"></i>
                Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
// --- 1. MODAL & TOAST CONFIG (SweetAlert2) ---
const modernAlert = Swal.mixin({
    customClass: {
        popup: 'modern-swal-popup',
        confirmButton: 'modern-confirm-btn',
        cancelButton: 'modern-cancel-btn',
        title: 'modern-swal-title',
        htmlContainer: 'modern-swal-content'
    },
    buttonsStyling: false,
    background: 'rgba(255, 255, 255, 0.95)',
    backdrop: 'rgba(0, 0, 0, 0.2)',
    showClass: { popup: 'animate__animated animate__fadeInUp' },
    hideClass: { popup: 'animate__animated animate__fadeOutDown' }
});

const modernToast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true,
    background: 'rgba(255, 255, 255, 0.95)',
    color: '#6366f1',
    customClass: { popup: 'modern-toast' }
});

// Styles Injection
const modernStyle = document.createElement('style');
modernStyle.innerHTML = `
    .modern-swal-popup { border-radius: 24px !important; box-shadow: 0 20px 60px rgba(0,0,0,0.1) !important; font-family: 'Poppins', sans-serif !important; }
    .modern-confirm-btn { background: linear-gradient(135deg, #6366f1, #8b5cf6) !important; color: white !important; padding: 12px 32px !important; border-radius: 12px !important; margin: 8px !important; cursor: pointer; border: none; font-weight: 600; }
    .modern-cancel-btn { background: white !important; color: #6b7280 !important; border: 1px solid #e5e7eb !important; padding: 12px 32px !important; border-radius: 12px !important; margin: 8px !important; cursor: pointer; font-weight: 600; }
    .qty-display-modern { transition: opacity 0.3s ease, transform 0.2s ease; display: inline-block; }
    .modern-toast { border-radius: 12px !important; border: 1px solid rgba(209,213,219,0.3) !important; backdrop-filter: blur(20px); }
`;
document.head.appendChild(modernStyle);

// --- 2. GLOBAL VARIABLES ---
let updateTimer; 
let deleteTimeouts = {}; 

// --- 3. INITIALIZATION ON LOAD ---
document.addEventListener('DOMContentLoaded', function() {
   
    Object.keys(localStorage).forEach(key => {
        if (key.startsWith('pending_delete_')) {
            const cartId = key.replace('pending_delete_', '');
            const item = document.querySelector(`#qty-${cartId}`)?.closest('.modern-item');
            if (item) item.style.display = 'none';
            finalizeDelete(cartId);
        }
    });
    recalculateCart();
});

function updateModernQuantity(cartId, newQty, maxStock) {
    if (newQty > maxStock) {
        modernAlert.fire({
            icon: 'warning',
            title: 'Stock Alert',
            text: `Maximum ${maxStock} items only`,
            confirmButtonColor: '#6366f1'
        });
        return;
    }
    if (newQty < 1) {
        confirmModernRemove(cartId);
        return;
    }

    const qtyElement = document.getElementById('qty-' + cartId);
    
    if (qtyElement) {
        qtyElement.style.opacity = '0.3';
        qtyElement.style.transform = 'scale(0.8)';
        qtyElement.innerText = newQty;
        recalculateCart(); // မျက်စိရှေ့တင် စျေးနှုန်းအရင်တွက်မယ်
    }

    clearTimeout(updateTimer);
    updateTimer = setTimeout(() => {
        // ⚠️ ဒီလမ်းကြောင်းက ဖိုင်နာမည်ကို သေချာပြန်စစ်ပါ (qty လား quantity လား)
        const rootPath = '../utils/update_cart_qty.php'; 
        
        fetch(rootPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ 'cart_id': cartId, 'quantity': newQty })
        })
        .then(res => res.json())
        .then(data => {
            if (qtyElement) {
                qtyElement.style.opacity = '1';
                qtyElement.style.transform = 'scale(1)';
            }

            if (data.status === 'success') {
                modernToast.fire({
                    icon: 'success',
                    title: 'Quantity updated'
                });

                // 🌟 ဒီနေရာမှာ အပေါ်က Badge ကို update လုပ်တဲ့ function ခေါ်မယ်
                if (typeof refreshBag === 'function') {
                    refreshBag();
                }
            } else {
                // Error ဖြစ်ရင် (ဥပမာ stock မလောက်ရင်) reload မလုပ်ဘဲ error ပြရုံပဲလုပ်ပါ
                modernAlert.fire({ icon: 'error', title: 'Fail', text: data.message });
                location.reload(); 
            }
        })
        .catch((err) => {
            console.error(err);
            if (qtyElement) { 
                qtyElement.style.opacity = '1'; 
                qtyElement.style.transform = 'scale(1)'; 
            }
        });
    }, 500);
}

// --- 5. REMOVE ITEM WITH UNDO ---
function confirmModernRemove(cartId) {
    modernAlert.fire({
        title: 'Remove Item?',
        text: "This item will be removed from your cart.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Keep it'
    }).then((result) => {
        if (result.isConfirmed) initiateRemove(cartId);
    });
}

function initiateRemove(cartId) {
    const itemElement = document.querySelector(`#qty-${cartId}`)?.closest('.modern-item');
    if (!itemElement) return;

    
    itemElement.style.transition = 'all 0.4s ease';
    itemElement.style.transform = 'translateX(100px)';
    itemElement.style.opacity = '0';

    
   
    
    
    
    const rootPath = window.location.origin + '/malltiverse/frontend/utils/removeFromCart.php'; 

    fetch(rootPath, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'Cache-Control': 'no-cache' 
        },
        body: new URLSearchParams({ 'cart_id': cartId })
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Network response was not ok');
        }
        return res.json();
    })
    .then(data => {
        if (data.status === 'success') {
            
            setTimeout(() => {
                itemElement.remove(); 
                recalculateCart(); 
            }, 300);

            modernToast.fire({ icon: 'success', title: 'Item removed successfully' });
            
          
            const remainingItems = document.querySelectorAll('.modern-item').length;
            if (remainingItems <= 1) { 
                setTimeout(() => location.reload(), 1000);
            }
        } else {
           
            itemElement.style.transform = 'translateX(0)';
            itemElement.style.opacity = '1';
            modernToast.fire({ icon: 'error', title: 'Failed to remove item' });
        }
    })
    .catch((error) => {
        console.error('Error:', error);
       
        itemElement.style.transform = 'translateX(0)';
        itemElement.style.opacity = '1';
        modernToast.fire({ icon: 'error', title: 'Connection error. Try again.' });
    });
}

function finalizeDelete(cartId) {
    const rootPath = window.location.origin + '/malltiverse/frontend/utils/removeFromCart.php';
    fetch(rootPath, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ 'cart_id': cartId }),
        keepalive: true
    }).then(() => {
        localStorage.removeItem('pending_delete_' + cartId);
        delete deleteTimeouts[cartId];
    });
}


window.addEventListener('beforeunload', () => {
    Object.keys(deleteTimeouts).forEach(cartId => finalizeDelete(cartId));
});

// --- 6. CALCULATION ENGINE ---
function recalculateCart() {
    let grandTotal = 0;
    let totalQty = 0;
    let discount = 0; 

    document.querySelectorAll('.modern-item').forEach(item => {
        
        if (item.style.display !== 'none' && item.style.opacity !== '0') {
            const price = parseFloat(item.getAttribute('data-price')) || 0;
            const qtyElement = item.querySelector('.qty-display-modern');
            const qty = parseInt(qtyElement.innerText) || 0;
            
            const itemSubtotal = price * qty;
            grandTotal += itemSubtotal;
            totalQty += qty;

          
            const cartId = qtyElement.id.replace('qty-', '');
            const subDisplay = document.getElementById('subtotal-' + cartId);
            if (subDisplay) {
                subDisplay.innerText = '$' + itemSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
            }
        }
    });

    // --- Shipping Logic ---
    const shipping = (grandTotal > 100 || grandTotal === 0) ? 0 : 9.99;
    const finalTotal = (grandTotal + shipping) - discount;

    // --- Header & Stat Cards Update ---
    const qtyStat = document.getElementById('total-qty-stat');
    const subtotalStat = document.getElementById('total-subtotal-stat');
    const savingsStat = document.getElementById('total-savings-stat');
    const headerQtyText = document.getElementById('total-qty-header');

    if (qtyStat) qtyStat.innerText = totalQty;
    if (subtotalStat) subtotalStat.innerText = '$' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
    if (savingsStat) savingsStat.innerText = '$' + discount.toLocaleString(undefined, {minimumFractionDigits: 2});
    if (headerQtyText) headerQtyText.innerText = totalQty + (totalQty > 1 ? ' items' : ' item');

  
    const subtotalEl = document.getElementById('summary-subtotal') || document.querySelector('.summary-item:nth-child(2) .summary-value');
    const shippingEl = document.getElementById('summary-shipping') || document.querySelector('.summary-item:nth-child(3) .summary-value');
    const totalEls = document.querySelectorAll('.summary-value.total');

    if (subtotalEl) subtotalEl.innerText = '$' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
    if (shippingEl) shippingEl.innerText = (shipping === 0) ? (grandTotal === 0 ? '$0.00' : 'FREE') : '$' + shipping.toFixed(2);
    
    totalEls.forEach(el => {
        el.innerText = '$' + finalTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
    });

    // --- Checkout Button Logic & Layout Fix ---
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        if (totalQty <= 0) {
            checkoutBtn.classList.add('disabled');
            checkoutBtn.style.opacity = "0.5";
            checkoutBtn.style.pointerEvents = "none";
        } else {
            checkoutBtn.classList.remove('disabled');
            checkoutBtn.style.opacity = "1";
            checkoutBtn.style.pointerEvents = "auto";
        }
    }
}
function updateUI(tQty, sub, ship, disc, total, types) {
    // Statistics & Badge
    const stats = document.querySelectorAll('.stat-value');
    if (stats.length > 0) stats[0].innerText = tQty;

    document.querySelectorAll('.badge, .cart-count').forEach(b => {
        b.innerText = tQty;
        b.style.display = tQty > 0 ? 'flex' : 'none';
    });

    // Summary Labels
    const summaries = document.querySelectorAll('.summary-value');
    if (summaries.length >= 4) {
        summaries[0].innerText = '$' + sub.toLocaleString(undefined, {minimumFractionDigits: 2});
        summaries[1].innerText = ship === 0 ? 'FREE' : '$' + ship.toFixed(2);
        summaries[2].innerText = '-$' + disc.toLocaleString(undefined, {minimumFractionDigits: 2});
        
        document.querySelectorAll('.summary-value.total').forEach(el => {
            el.innerText = '$' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
        });
    }

    if (types === 0 && Object.keys(deleteTimeouts).length === 0) {
        setTimeout(() => location.reload(), 1000);
    }
}
</script>
    <!--Checkout disabled code -->
<script>
function showEmptyCartAlert() {
    Swal.fire({
        icon: 'warning',
        title: 'Your cart is empty!',
        text: 'Please add some items before checking out.',
        confirmButtonColor: '#6366f1',
    });
}</script>

<style>
.disabled-btn {
    background: #cccccc !important;
    cursor: not-allowed !important;
    opacity: 0.6;
    box-shadow: none !important;
}

.disabled-btn:hover {
    transform: none !important;
}


.checkout-btn-modern.disabled {
    background: #cccccc !important;
    cursor: not-allowed !important;
    pointer-events: none;
    box-shadow: none !important;
    transform: none !important;
}
/* Summary Panel container */
.summary-panel-modern {
    display: flex;
    flex-direction: column;
    height: auto !important; 
    min-height: fit-content;
    padding-bottom: 30px; 
}

/* Checkout Button */
.checkout-btn-modern {
    margin-top: auto; 
    display: block;
    width: 100%;
}

@media (max-width: 768px) {
    /* Reset the container to allow proper centering */
    div.swal2-container.swal2-top-end,
    div.swal2-container.swal2-top {
        top: 20px !important; 
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        height: auto !important;
        
        /* Use flexbox for perfect alignment */
        display: flex !important;
        justify-content: center !important;
        align-items: flex-start !important;
        
        /* Remove the conflicting transform */
        transform: none !important; 
        pointer-events: none; 
    }

    /* Style the actual toast box */
    div.swal2-popup.swal2-toast {
        width: auto !important;
        max-width: 90% !important; 
        margin: 0 auto !important;
        display: flex !important;
        align-items: center !important;
        pointer-events: auto; 
    }
    
    /* Ensure text is readable and centered */
    .swal2-html-container {
        white-space: normal !important;
        text-align: center !important;
        padding: 5px 10px !important;
    }
}
</style>