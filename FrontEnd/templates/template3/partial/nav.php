<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$sql = "SELECT c.*, t.total_items 
        FROM cart c
        CROSS JOIN (SELECT COUNT(*) AS total_items FROM cart WHERE customer_id = 1 AND supplier_id = ?) t
        WHERE c.customer_id = 1 AND c.supplier_id = ?";

$stmt = $conn->prepare($sql);
$supplier_id = $_GET["supplier_id"];
$stmt->bind_param("ii", $supplier_id, $supplier_id);

$stmt->execute();
$result = $stmt->get_result();
$items = [];
$cart_count = 0;

while ($row = $result->fetch_assoc()) {
    $cart_count = $row["total_items"];
    $items[] = $row;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<nav class="main-nav navbar navbar-expand-lg">
    <div class="container-fluid px-0">
        <div class="header-wrapper">            
            <div class="logo-container">
                <?php if (!empty($shop_assets['logo'])): ?>
                    <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                        alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo" class="NFlogo">
                        <h1 class="site-title"><?= htmlspecialchars($supplier['company_name']) ?></h1>
            </div>
            <?php endif; ?>
            <div class="header-text">                
                <?php if (!empty($supplier['tagline'])): ?>
                    <p class="site-tagline"><?= htmlspecialchars($supplier['tagline']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <ul class="navbar-nav">
            <?php
            $base_url = "?supplier_id=" . $supplier_id;
            ?>
            <li class="nav-item">
                <a class="navlink <?= $page === 'home' ? 'active' : '' ?>" href="<?= $base_url ?>&page=home">Home</a>
            </li>
            <li class="nav-item">
                <a class="navlink <?= $page === 'about' ? 'active' : '' ?>" href="<?= $base_url ?>&page=about">About Us</a>
            </li>
            <li class="nav-item">
                <a class="navlink <?= $page === 'collection' ? 'active' : '' ?>" href="<?= $base_url ?>&page=collection">Collection</a>
            </li>
            <li class="nav-item">
                <a class="navlink <?= $page === 'contact' ? 'active' : '' ?>" href="<?= $base_url ?>&page=contact">Contact</a>
            </li>
            <li class="nav-item">
                <a class="navlink <?= $page === 'review' ? 'active' : '' ?>" href="<?= $base_url ?>&page=review">Review</a>
            </li>

            <li class="nav-item">
                <a class="navlink exit-btn" href="/malltiverse/frontend/customer">
                    <span class="exit-icon-wrapper">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </span>
                </a>
            </li>

            <li class="nav-item ms-lg-3">
                <div class="nav-icons">
                    <a href="?supplier_id=<?= $supplier_id ?>&page=cart" class="cart-link nav-link">
                        <i class="fa-solid fa-bag-shopping fs-4"></i>
                        <?php if ($cart_count > 0): ?>
                            <span id="cart-count-badge" class="badge rounded-pill bg-danger cart-badge">
                                <?= $cart_count ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>

<style>
    .cart-link {
        color: #0b0101;
        position: relative;
        display: inline-block;
        padding: 0.5rem;
        transform: scale(1.1); 
        margin-right: 10px;
        transition: color 0.3s ease;
    }

    .cart-link:hover {
        color: #3e3c3c;
    }

    .cart-badge {
        position: absolute;
        top: 2px;
        right: -2px;
        font-size: 0.6rem;
        padding: 0.3em 0.5em;
        transform: translate(20%, -20%);
    }
</style>