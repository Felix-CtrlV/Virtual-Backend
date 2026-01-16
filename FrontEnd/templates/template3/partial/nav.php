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
    <div class="container">
        <div class="header-wrapper">
            <?php if (!empty($shop_assets['logo'])): ?>
                <div class="logo-container">
                    <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                        alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo" class="NFlogo">
                </div>
            <?php endif; ?>
            <div class="header-text">
                <h1 class="site-title"><?= htmlspecialchars($supplier['company_name']) ?></h1>
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
                <a class="navlink <?= $page === 'review' ? 'active' : '' ?>" href="<?= $base_url ?>&page=review">Review</a>
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
    color: #2A0001;
    position: relative;
    display: inline-block;
    padding: 0.5rem;
    transform: scale(1.2);
    margin-right: 10px;
    }

    .cart-badge {
        position: absolute;
        top: 0;
        right: 0;
        font-size: 0.65rem;
        padding: 0.35em 0.65em;
        transform: translate(25%, -25%);
    }
</style>