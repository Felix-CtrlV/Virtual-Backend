<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Database connection & User ID
$customer_id = $_SESSION["customer_id"] ?? 0;
$supplier_id = $_GET["supplier_id"] ?? 0;

// --- INITIALIZE VARIABLES TO PREVENT ERRORS ---
$user_name = 'Guest';
$user_email = '';
$user_initial = 'G';
$has_image = false;
$user_image_path = "";
// ----------------------------------------------

// 2. Fetch User Data (Only if logged in)
if ($customer_id > 0) {
    // Ensure $conn is defined in your included files before this script runs
    if (isset($conn)) {
        $u_stmt = $conn->prepare("SELECT name, email, image FROM customers WHERE customer_id = ?");
        $u_stmt->bind_param("i", $customer_id);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result()->fetch_assoc();

        if ($u_res) {
            $user_name = $u_res['name'] ?? 'User';
            $user_email = $u_res['email'] ?? '';

            $has_image = !empty($u_res['image']) && file_exists("../assets/customer_profiles/" . $u_res['image']);
            $user_image_path = $has_image ? "../assets/customer_profiles/" . $u_res['image'] : "";
            $user_initial = strtoupper(substr($user_name, 0, 1));
        }
    }
}

// 3. Cart Count Logic (Ensure $cart_count is always defined)
$cart_count = 0;
if (isset($conn)) {
    // Use supplier data if available, otherwise default to 0
    $company_id = isset($supplier['company_id']) ? (int) $supplier['company_id'] : 0;

    $sql_cart = "SELECT COUNT(*) AS total_items FROM cart WHERE customer_id = ? AND company_id = ?";
    $stmt_c = $conn->prepare($sql_cart);
    $stmt_c->bind_param("ii", $customer_id, $company_id);
    $stmt_c->execute();
    $cart_count = $stmt_c->get_result()->fetch_assoc()['total_items'] ?? 0;
}
?>
<nav class="main-nav navbar navbar-expand-lg" style="z-index: 100; position: relative;">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="?supplier_id=<?= $supplier_id ?>&page=home">
            <?php if (!empty($shop_assets['logo'])): ?>
                <div class="logo-container">
                    <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
                        alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo" class="site-logo">
                </div>
            <?php endif; ?>
            <div class="header-text">
                <h1 class="site-title mb-0"><?= htmlspecialchars($supplier['company_name']) ?></h1>
            </div>
        </a>

        <div class="d-flex align-items-center gap-3 ms-auto order-lg-last">
            <a href="javascript:void(0)" id="cartIconTrigger" class="position-relative cart-link text-white"
                style="margin: 0px 0px 10px 5px;">
                <i class="bi bi-cart fs-4" style="color: white !important; margin-bottom: 10px;"></i>
                <span class="badge rounded-pill bg-danger cart-badge" id="nav-cart-count"
                    style="font-size: 0.7rem; position: absolute; top: -2px; right: -4px; display: <?= ($cart_count > 0) ? 'flex' : 'none' ?>; align-items: center; justify-content: center; min-width: 18px; height: 18px; border: 2px solid white;">

            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                style="color: white; border-color: rgba(255,255,255,0.1);">
                <span class="navbar-toggler-icon" style="filter: invert(1); "></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=home">Home</a></li>
                <li class="nav-item"><a class="nav-link"
                        href="?supplier_id=<?= $supplier_id ?>&page=products">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=review">Review</a>
                </li>
                <li class="nav-item"><a class="nav-link"
                        href="?supplier_id=<?= $supplier_id ?>&page=contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="?supplier_id=<?= $supplier_id ?>&page=about">About</a>
                </li>
                <li class="nav-item"><a class="nav-link" href="/malltiverse/frontend/customer">Exit</a></li>

                <li class="nav-item ms-lg-3">
                    <?php if ($customer_id > 0): ?>
                        <div class="dropdown">
                            <a class="nav-link p-0 no-caret profile-trigger no-hover-line" href="#" role="button"
                                data-bs-toggle="dropdown">
                                <?php if ($has_image): ?>
                                    <img src="<?= $user_image_path ?>" class="rounded-circle nav-profile-img shadow-sm"
                                        style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #fff;">
                                <?php else: ?>
                                    <div class="profile-initial-circle shadow-sm"><?= $user_initial ?></div>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end glass-dropdown fade-animation">
                                <div class="user-card mx-3 mb-3 p-3 text-center">
                                    <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($user_name) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($user_email) ?></small>
                                </div>
                                <div class="dropdown-divider mx-3"></div>

                                <!-- INSERT EDIT PROFILE LINK HERE -->
                                <div class="logout-container mb-2">
                                    <a class="nav-auth-button-dark"
                                        href="/malltiverse/FrontEnd/customer_profile.php?tab=profile">
                                        Edit Profile <i class="fa-solid fa-user-pen ms-2"></i>
                                    </a>
                                </div>

                                <!-- LOGOUT BUTTON -->
                                <div class="logout-container">
                                    <a class="nav-auth-button-dark"
                                        href="../utils/logout.php?supplier_id=<?= $supplier_id ?>">
                                        Log out <i class="fa-solid fa-arrow-right-from-bracket ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="dropdown">
                            <i class="bi bi-person-circle" style="font-size: 1.8rem; color: #FFFFFF;"
                                data-bs-toggle="dropdown"></i>
                            <div class="dropdown-menu dropdown-menu-end glass-dropdown fade-animation border-0">
                                <div class="p-4 text-center">
                                    <h6 class="mb-3 fw-bold text-dark">Welcome, Guest</h6>

                                    <a class="btn btn-primary w-100 mb-3" href="../customerLogin.php">Login</a>

                                    <div class="register-section border-top pt-3">
                                        <small class="text-muted d-block mb-2">Don't have an account?</small>
                                        <a href="../customerRegister.php" class="btn btn-outline-primary w-100">
                                            Register Here
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>
<style>
    /* 1. Layout: Cart Sidebar & Overlay */
    .cart-sidebar {
        position: fixed;
        top: 0;
        right: -400px;
        width: 350px;
        height: 100%;
        background: white;
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
        display: flex;
        z-index: 9999;
        flex-direction: column;
        visibility: hidden;
        transition: right 0.3s ease, visibility 0.3s;
    }

    .cart-sidebar.open {
        right: 0 !important;
        visibility: visible !important;
    }

    .cart-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease;
        z-index: 9998;
    }

    .cart-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* 2. Profile Icons */
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
        border: 2px solid #fff;
    }

    .logged-out-circle {
        background: rgba(0, 0, 0, 0.1) !important;
        border: 1px solid rgba(0, 0, 0, 0.2) !important;
    }

    /* 3. The Dropdown (Cleaned Duplicates) */
    /* 3. The Dropdown (Solidified to prevent see-through) */
    .glass-dropdown {
        width: 260px;
        border-radius: 15px !important;
        background: #ffffff !important;
        /* Forces solid white */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid #ddd !important;
        padding: 15px !important;
        backdrop-filter: none !important;
        /* Removes the glass/blur effect */
        z-index: 10000 !important;
        /* Ensures it sits above product cards */
    }

    .fade-animation {
        animation: fadeInUp 0.3s ease-out forwards;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* 4. Auth Buttons (Login, Register, Logout) */
    /* Primary / Login / Logout */
    .glass-dropdown .btn-primary,
    .nav-auth-button-dark {
        background-color: #2c3136 !important;
        border: none !important;
        color: #ffffff !important;
        border-radius: 8px !important;
        padding: 10px !important;
        font-weight: 600 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        text-decoration: none !important;
        transition: all 0.3s ease;
    }

    .glass-dropdown .btn-primary:hover,
    .nav-auth-button-dark:hover {
        background-color: #000000 !important;
        transform: translateY(-1px);
    }

    /* Outline / Register */
    .glass-dropdown .btn-outline-primary {
        background-color: transparent !important;
        border: 1px solid #333 !important;
        color: #333 !important;
        border-radius: 8px !important;
        padding: 8px !important;
        font-weight: 600 !important;
        width: 100%;
        transition: all 0.3s ease;
    }

    .glass-dropdown .btn-outline-primary:hover {
        background-color: #333 !important;
        color: #fff !important;
    }

    /* 5. Utility Cleanup */
    .logout-container {
        padding: 10px 15px;
    }

    .no-caret::after {
        display: none !important;
    }

    .nav-item .nav-link:hover {
        text-decoration: none !important;
        border: none !important;
    }

    /* Add the hover underline to the Cart Icon to match nav links */
    #cartIconTrigger {
        text-decoration: none;
        position: relative;
        padding-bottom: 5px;
    }

    #cartIconTrigger::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -2px;
        left: 0;
        background-color: white;
        /* Matches your text color */
        transition: width 0.3s ease;
    }

    #cartIconTrigger:hover::after {
        width: 100%;
    }

    /* === FIX DROPDOWN SEE-THROUGH ISSUE COMPLETELY === */
    .dropdown-menu.glass-dropdown {
        background-color: #ffffff !important;
        opacity: 1 !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;

        position: relative;
        overflow: hidden;
        /* 🔑 blocks bleed-through */

        z-index: 1055 !important;
        /* above everything */
    }

    /* Kill any pseudo-elements causing transparency */
    .dropdown-menu.glass-dropdown::before,
    .dropdown-menu.glass-dropdown::after {
        content: none !important;
        display: none !important;
    }

    /* Extra safety: force solid background for children */
    .dropdown-menu.glass-dropdown * {
        background-color: transparent;
    }

    /* === HARD ISOLATION FIX FOR DROPDOWN === */
    .navbar,
    .main-nav {
        position: relative;
        z-index: 1100;
        isolation: isolate;
        /* 🔥 critical */
    }

    .dropdown-menu.glass-dropdown {
        position: absolute !important;
        background: #ffffff !important;
        opacity: 1 !important;

        transform: none !important;
        filter: none !important;

        isolation: isolate;
        /* 🔥 critical */
        contain: paint;
        /* 🔥 blocks bleed-through */

        overflow: hidden;
        z-index: 99999 !important;
    }

    /* === Cart badge position fix (closer to icon) === */
    #cartIconTrigger {
        position: relative;
    }

    #nav-cart-count {
        top: 0px !important;
        /* move down */
        right: -4px !important;
        /* move left */
    }
</style>



<div id="cartDrawer" class="cart-sidebar">
    <div class="cart-sidebar-header d-flex justify-content-between align-items-center">
        <h2 class="h4 mb-0 fw-bold">Your Bag</h2>
        <button id="closeCart" class="btn-close close-btn shadow-none" style="font-size: 0.8rem;"></button>
    </div>
    <hr class="my-3 opacity-25">
    <div id="cartItemsContainer" style="flex: 1; overflow-y: auto; padding-right: 5px;"></div>
    <div id="cartFooter" class="pt-3 border-top mt-auto"></div>
</div>

<div id="cartOverlay" class="cart-overlay"></div>