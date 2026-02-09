<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Database connection & User ID
$customer_id = $_SESSION["customer_id"] ?? 0;
$supplier_id = $_GET["supplier_id"] ?? 0;

// 2. Fetch User Data (Only if logged in)
if ($customer_id > 0) {
    // Ensure $conn is defined in your included files before this script runs
    if (isset($conn)) {
        $u_stmt = $conn->prepare("SELECT name, email, image FROM customers WHERE customer_id = ?");
        $u_stmt->bind_param("i", $customer_id);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result()->fetch_assoc();

        $user_name = $u_res['name'] ?? 'User';
        $user_email = $u_res['email'] ?? 'No email';

        $has_image = !empty($u_res['image']) && file_exists("../assets/customer_profiles/" . $u_res['image']);
        $user_image_path = $has_image ? "../assets/customer_profiles/" . $u_res['image'] : "";
        $user_initial = strtoupper(substr($user_name, 0, 1));
    }
}

// 3. Cart Count Logic
$cart_count = 0;
if (isset($conn) && isset($supplier)) {
    $company_id = isset($supplier['company_id']) ? (int) $supplier['company_id'] : 0;
    $sql_cart = "SELECT COUNT(*) AS total_items FROM cart WHERE customer_id = ? AND company_id = ?";
    $stmt_c = $conn->prepare($sql_cart);
    $stmt_c->bind_param("ii", $customer_id, $company_id);
    $stmt_c->execute();
    $cart_count = $stmt_c->get_result()->fetch_assoc()['total_items'] ?? 0;
}
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<nav class="main-nav navbar navbar-expand-lg">
    <div class="container-fluid px-0 nav-container">
        <div class="header-wrapper">
            <div class="logo-container d-flex align-items-center">
                <?php if (!empty($shop_assets['logo'])): ?>
                    <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                        class="NFlogo">
                    <h1 class="site-title"><?= htmlspecialchars($supplier['company_name']) ?></h1>
                <?php endif; ?>
            </div>

            <button class="navbar-toggler" id="navToggle" type="button">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navMenuContent">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php $base_url = "?supplier_id=" . $supplier_id; ?>
                <li class="nav-item"><a class="navlink <?= ($page === 'home') ? 'active' : '' ?>" href="<?= $base_url ?>&page=home">HOME</a></li>
                <li class="nav-item"><a class="navlink <?= ($page === 'product') ? 'active' : '' ?>" href="<?= $base_url ?>&page=product">PRODUCT</a></li>
                <li class="nav-item"><a class="navlink <?= ($page === 'about') ? 'active' : '' ?>" href="<?= $base_url ?>&page=about">ABOUT US</a></li>
                <li class="nav-item"><a class="navlink <?= ($page === 'contact') ? 'active' : '' ?>" href="<?= $base_url ?>&page=contact">CONTACT</a></li>
                <li class="nav-item"><a class="navlink <?= ($page === 'review') ? 'active' : '' ?>" href="<?= $base_url ?>&page=review">REVIEW</a></li>

                <li class="nav-item">
                    <a class="navlink exit-btn" href="/malltiverse/frontend/customer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </li>

                <li class="nav-item ms-lg-2">
                    <a href="<?= $base_url ?>&page=cart" class="cart-linkk">
                        <i class="fa-solid fa-bag-shopping fs-4"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="badge rounded-pill bg-danger cart-badge"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item ms-lg-3">
                    <?php if ($customer_id > 0): ?>
                        <div class="dropdown">
                            <a class="nav-link p-0 no-caret profile-trigger" href="#" role="button"
                                data-bs-toggle="dropdown">
                                <?php if ($has_image): ?>
                                    <img src="<?= $user_image_path ?>" class="rounded-circle nav-profile-img shadow-sm">
                                <?php else: ?>
                                    <div class="profile-initial-circle shadow-sm"><?= $user_initial ?></div>
                                <?php endif; ?>
                            </a>

                            <div class="dropdown-menu dropdown-menu-end glass-dropdown fade-animation">

                                <div
                                    class="user-card mt-3 mx-3 mb-3 p-3 d-flex align-items-center justify-content-center text-center">
                                    <div class="overflow-hidden">
                                        <h6 class="mb-1 fw-bold text-dark text-truncate user-name">
                                            <?= htmlspecialchars($user_name) ?></h6>
                                        <small
                                            class="text-muted text-truncate d-block user-email"><?= htmlspecialchars($user_email) ?></small>
                                    </div>
                                </div>

                                <div class="dropdown-divider mx-3" style="border-color: rgba(0,0,0,0.05);"></div>
                                <div class="p-3" style="display: flex; flex-direction: column; gap: 15px;">
                                    <a class="btn-logout-modern" href="/malltiverse/FrontEnd/customer_profile.php">
                                        <span>Edit Profile</span>
                                        <i class="fas fa-user-cog ms-2"></i>
                                    </a>

                                    <a class="btn-logout-modern" href="../utils/logout.php?supplier_id=<?= $supplier_id ?>">
                                        <span>Log out</span>
                                        <i class="fa-solid fa-arrow-right-from-bracket ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="../customerLogin.php" class="login-pill-btn" data-tooltip="LOGIN">
                            <i class="fa-regular fa-user"></i>
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    .main-nav {
        font-family: 'Poppins', sans-serif;
    }

    .profile-initial-circle {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #0d6efd, #0a58ca);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.2rem;
        cursor: pointer;
        transition: transform 0.2s ease;
        border: 2px solid #fff;
    }

    .nav-profile-img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        cursor: pointer;
        border: 2px solid #fff;
        transition: transform 0.2s ease;
    }

    .profile-trigger:hover .profile-initial-circle,
    .profile-trigger:hover .nav-profile-img {
        transform: scale(1.05);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15) !important;
    }

    .no-caret::after {
        display: none !important;
    }

    .glass-dropdown {
        width: 280px;
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        background: linear-gradient(145deg, var(--primary-p));
        backdrop-filter: blur(15px) saturate(180%);
        -webkit-backdrop-filter: blur(15px) saturate(180%);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        padding: 10px 0;
        margin-top: 12px;
    }

    .fade-animation {
        animation: fadeInUp 0.3s ease-out forwards;
        transform-origin: top right;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .user-card {
        background: rgba(255, 255, 255, 0.3) !important;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        padding-top: 30px !important;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .user-name {
        font-size: 0.9rem;
        color: #333;
    }

    .user-email {
        font-size: 0.85rem;
        color: #666;
    }

    .btn-logout-modern {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 90%;
        padding: 15px 0;
        background: rgba(255, 255, 255, 0.6);
        color: #dc3545;
        border: 1px solid #ffcccc;
        border-radius: 16px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        display: inline-block;
        padding: 0.5rem;
        transform: scale(1.1);
        margin-right: 5px;
        transition: color 0.3s ease;
        display: flex !important;
        margin: 0px auto !important;
        justify-content: center !important;
        align-items: center !important;
    }

    .btn-logout-modern:hover {
        width: 100%;
        background: rgba(94, 141, 199, 0.8);
        color: #fff;
        border-color: rgba(94, 141, 199, 0.8);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        transform: translateY(-2px);
    }

    .login-pill-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        background: rgba(220, 235, 255, 0.45);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #000000;
        font-size: 1.2rem;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .login-pill-btn::after {
        content: attr(data-tooltip);
        position: absolute;
        top: 115%;
        left: 50%;
        transform: translateX(-50%) translateY(5px);
        padding: 4px 10px;
        background: rgba(0, 0, 0, 0.85);
        color: #fff;
        font-size: 0.7rem;
        font-family: 'Poppins', sans-serif;
        border-radius: 6px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
    }

    .login-pill-btn:hover::after {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    .login-pill-btn:hover {
        background: rgba(190, 215, 255, 0.7);
        transform: translateY(-2px);
        border-color: rgba(0, 0, 0, 0.1);
    }

    .login-pill-btn:active {
        transform: scale(0.95);
    }

    .btn-logout-modern i {
        transition: transform 0.3s ease;
    }

    .btn-logout-modern:hover i {
        transform: translateX(5px);
    }

    .cart-linkk {
        color: #0b0101;
        position: relative;
        display: inline-block;
        padding: 0.5rem;
        transform: scale(1.1);
        margin-right: 5px;
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

<script>
    const toggleBtn = document.getElementById('navToggle');
    const menu = document.getElementById('navMenuContent');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            menu.classList.toggle('show');
        });
    }
</script>