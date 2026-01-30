<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

    $customer_id = $_SESSION["customer_id"] ?? 0;

$sql = "SELECT c.*, t.total_items 
        FROM cart c
        CROSS JOIN (SELECT COUNT(*) AS total_items FROM cart WHERE customer_id = ? AND supplier_id = ?) t
        WHERE c.customer_id = ? AND c.supplier_id = ?";

$stmt = $conn->prepare($sql);
$supplier_id = $_GET["supplier_id"];
$stmt->bind_param("iiii", $customer_id, $supplier_id, $customer_id, $supplier_id);

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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<nav class="main-nav navbar navbar-expand-lg">
    <div class="container-fluid px-0 nav-container">
        <div class="header-wrapper">            
            <div class="logo-container d-flex align-items-center">
                <?php if (!empty($shop_assets['logo'])): ?>
                    <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                        alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo" class="NFlogo">
                    <h1 class="site-title"><?= htmlspecialchars($supplier['company_name']) ?></h1>
                <?php endif; ?>
            </div>

            <div class="header-text">                
                <?php if (!empty($supplier['tagline'])): ?>
                    <p class="site-tagline"><?= htmlspecialchars($supplier['tagline']) ?></p>
                <?php endif; ?>
            </div>
            <button class="navbar-toggler border-0 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navMenuContent">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navMenuContent">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php $base_url = "?supplier_id=" . $supplier_id; ?>
                <li class="nav-item"><a class="navlink <?= $page === 'home' ? 'active' : '' ?>" href="<?= $base_url ?>&page=home">Home</a></li>
                <li class="nav-item"><a class="navlink <?= $page === 'about' ? 'active' : '' ?>" href="<?= $base_url ?>&page=about">About Us</a></li>
                <li class="nav-item"><a class="navlink <?= $page === 'collection' ? 'active' : '' ?>" href="<?= $base_url ?>&page=collection">Collection</a></li>
                <li class="nav-item"><a class="navlink <?= $page === 'contact' ? 'active' : '' ?>" href="<?= $base_url ?>&page=contact">Contact</a></li>
                <li class="nav-item"><a class="navlink <?= $page === 'review' ? 'active' : '' ?>" href="<?= $base_url ?>&page=review">Review</a></li>

                <li class="nav-item">
                    <a class="navlink exit-btn" href="/malltiverse/frontend/customer" style="display: flex; align-items: center;">
                        <span class="exit-anim-box" style="height: 25px;">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </span>
                    </a>
                </li>

                <li class="nav-item ms-lg-2">
                    <div class="nav-icons py-2 py-lg-0">
                        <a href="?supplier_id=<?= $supplier_id ?>&page=cart" class="cart-linkk">
                            <i class="fa-solid fa-bag-shopping fs-4"></i>
                            <?php if ($cart_count > 0): ?>
                                <span id="cart-count-badge" class="badge rounded-pill bg-danger cart-badge"><?= $cart_count ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    .cart-linkk {
        color: #0b0101;
        position: relative;
        display: inline-block;
        padding: 0.5rem;
        transform: scale(1.1); 
        margin-right: 10px;
        transition: color 0.3s ease;
    }

    .cart-linkk:hover {
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
