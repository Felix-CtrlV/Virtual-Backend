<?php
// ၁။ PHP Session နှင့် User Data ဆွဲထုတ်ခြင်း
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['customer_id']);
$supplier_id = $_GET['supplier_id'] ?? (isset($supplier_id) ? $supplier_id : 1);
$base_url = "?supplier_id=" . $supplier_id;
$page = $_GET['page'] ?? 'home';

// Default Values
$user_name = "Guest";
$user_email = "";
$first_letter = "G";

if ($isLoggedIn) {
    $c_id = $_SESSION['customer_id'];
    // $conn ရှိမှ Query စစ်မည်
    if(isset($conn)) {
        $user_query = mysqli_query($conn, "SELECT * FROM customers WHERE customer_id = '$c_id'");
        if($user_row = mysqli_fetch_assoc($user_query)) {
            $user_name = $user_row['customer_name'] ?? $user_row['name']; 
            $user_email = $user_row['customer_email'] ?? $user_row['email'];
            // နာမည်၏ ပထမစာလုံးကို ယူခြင်း
            $first_letter = strtoupper(substr($user_name, 0, 1));
        }
    }
}

// Cart Count (အစ်ကို့ Session logic အတိုင်း)
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $qty) { $cart_count += $qty; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- General Styles --- */
        body { font-family: 'Inter', sans-serif; background-color: #fff; }
        .user-dropdown .dropdown-toggle::after { display: none; }
        .dropdown-menu { border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: none; overflow: hidden; }

        /* --- Google Style Avatar --- */
        .google-avatar-circle {
            width: 35px; height: 35px; border-radius: 50%;
            background-color: #1a73e8; color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 15px; text-transform: uppercase;
        }

        /* --- Back to Mall Button --- */
        .shopping-back {
            display: inline-flex; align-items: center; padding: 8px 18px;
            background-color: #ffffff; color: #333 !important;
            text-decoration: none !important; border-radius: 50px;
            font-weight: 600; font-size: 13px; transition: 0.3s; border: 1px solid #eee;
        }
        .shopping-back:hover { background-color: #2c3e50; color: #fff !important; transform: translateX(-3px); }

        /* --- Profile Info Box (Google Style) --- */
        .profile-info-box { padding: 16px; min-width: 240px; background: #fff; }
        .user-full-name { font-weight: 700; color: #202124; font-size: 0.95rem; margin-bottom: 2px; }
        .user-full-email { color: #5f6368; font-size: 0.85rem; margin-bottom: 0; }
        .logout-link { color: #d93025 !important; font-weight: 500; transition: 0.2s; }
        .logout-link:hover { background-color: #fff5f5; }

        /* --- Login Alert Modal --- */
        .login-alert-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(8px);
            display: none; align-items: center; justify-content: center; z-index: 9999;
        }
        .login-alert-box {
            background: #fff; padding: 40px 30px; border-radius: 24px;
            width: 90%; max-width: 380px; text-align: center; position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); animation: slideUp 0.4s ease;
        }
        .icon-wrapper { width: 70px; height: 70px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 28px; position: relative; }
        .pulse-ring { position: absolute; width: 100%; height: 100%; border: 2px solid #2d3436; border-radius: 50%; animation: pulse 2s infinite; }
        .login-alert-btns { display: flex; gap: 10px; margin-top: 25px; }
        .btn-login { flex: 1; padding: 12px; background: #1a1a1a; color: #fff; text-decoration: none; border-radius: 12px; font-weight: 600; }
        .btn-cancel { flex: 1; padding: 12px; border: 1.5px solid #eee; background: #fff; color: #666; border-radius: 12px; cursor: pointer; }

        /* --- Animations --- */
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes pulse { 0% { transform: scale(1); opacity: 0.5; } 100% { transform: scale(1.4); opacity: 0; } }

        /* --- Mobile Responsive --- */
        @media (max-width: 991px) {
            .google-avatar-circle { width: 40px; height: 40px; }
            .nav-auth-section { display: none; }
            .mobile-user-profile { background: #f8f9fa; border-radius: 12px; padding: 12px; margin-bottom: 15px; display: flex; align-items: center; gap: 12px; }
        }
    </style>
</head>
<body>

<nav class="main-nav navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container-fluid px-3 px-lg-4">
        <a href="<?= $base_url ?>&page=home" class="navbar-brand py-0 d-flex align-items-center">
            <?php if (!empty($shop_assets['logo'])): ?>
                <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>" 
                     class="rounded-circle me-2" style="height: 40px; width: 40px; object-fit: cover;">
            <?php endif; ?>
            <div class="header-text">
                <h1 class="fs-6 fw-bold mb-0"><?= htmlspecialchars($supplier['tags'] ?? 'Shop Name') ?></h1>
                <?php if (!empty($supplier['tagline'])): ?>
                    <p class="mb-0 text-muted" style="font-size: 0.65rem;"><?= htmlspecialchars($supplier['tagline']) ?></p>
                <?php endif; ?>
            </div>
        </a>

        <a href="../customer/index.html" class="shopping-back ms-3 d-none d-md-flex">
            <i class="fas fa-arrow-left me-2"></i> Back to the Mall
        </a>

        <div class="nav-cart ms-auto me-3 d-lg-none">
            <a href="javascript:void(0)" onclick="handleCartClick(<?= $isLoggedIn ? 'true' : 'false' ?>)" class="position-relative text-dark">
                <i class="fas fa-shopping-basket fa-lg"></i>
                <span class="cart-badge-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                      style="font-size: 0.6rem; min-width: 18px; height: 18px; <?= $cart_count > 0 ? '' : 'display:none;' ?>">
                    <?= $cart_count ?>
                </span>
            </a>
        </div>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <div class="d-lg-none mt-3">
                <?php if ($isLoggedIn): ?>
                    <div class="mobile-user-profile">
                        <div class="google-avatar-circle"><?= $first_letter ?></div>
                        <div>
                            <p class="user-full-name mb-0"><?= htmlspecialchars($user_name) ?></p>
                            <small class="user-full-email"><?= htmlspecialchars($user_email) ?></small>
                        </div>
                        <a href="../utils/logout.php?supplier_id=<?= $supplier_id ?>" class="ms-auto text-danger"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-2 mb-3">
                        <a href="../customerLogin.php" class="btn btn-outline-dark w-50">Login</a>
                        <a href="../customerRegister.php" class="btn btn-dark w-50">Register</a>
                    </div>
                <?php endif; ?>
            </div>

            <ul class="navbar-nav mx-auto gap-lg-4">
                <?php foreach (['home' => 'Home', 'products' => 'Products', 'about' => 'About Us', 'contact' => 'Contact', 'review' => 'Review'] as $key => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page === $key) ? 'active fw-bold text-dark' : 'text-muted' ?>" href="<?= $base_url ?>&page=<?= $key ?>"><?= $label ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-none d-lg-flex align-items-center gap-3">
                <div class="nav-cart me-2">
                    <a href="javascript:void(0)" onclick="handleCartClick(<?= $isLoggedIn ? 'true' : 'false' ?>)" class="position-relative text-dark">
                        <i class="fas fa-shopping-basket fa-lg"></i>
                        <span class="cart-badge-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                              style="font-size: 0.6rem; min-width: 18px; height: 18px; <?= $cart_count > 0 ? '' : 'display:none;' ?>">
                            <?= $cart_count ?>
                        </span>
                    </a>
                </div>

                <div class="nav-auth-section border-start ps-3">
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown user-dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" href="#" data-bs-toggle="dropdown">
                                <div class="google-avatar-circle me-2"><?= $first_letter ?></div>
                                <small class="fw-medium"><?= htmlspecialchars($user_name) ?></small>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li>
                                    <div class="profile-info-box text-center">
                                        <div class="google-avatar-circle mx-auto mb-2" style="width: 50px; height: 50px; font-size: 20px;"><?= $first_letter ?></div>
                                        <p class="user-full-name"><?= htmlspecialchars($user_name) ?></p>
                                        <p class="user-full-email"><?= htmlspecialchars($user_email) ?></p>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider m-0"></li>
                                <li><a class="dropdown-item logout-link py-2" href="../utils/logout.php?supplier_id=<?= $supplier_id ?>">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="nav-auth-buttons d-flex gap-2">
                            <a href="../customerLogin.php" class="btn btn-outline-dark btn-sm rounded-pill">Login</a>
                            <a href="../customerRegister.php" class="btn btn-dark btn-sm rounded-pill">Register</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>

<div id="loginAlertModal" class="login-alert-overlay">
    <div class="login-alert-box">
        <button class="close-x" onclick="closeLoginAlert()">&times;</button>
        <div class="icon-wrapper"><div class="pulse-ring"></div><i class="fas fa-user-shield"></i></div>
        <h3>Login Required</h3>
        <p>To access your shopping cart and enjoy a seamless experience, please sign in to your account.</p>
        <div class="login-alert-btns">
            <button onclick="closeLoginAlert()" class="btn-cancel">Later</button>
            <a href="../customerLogin.php" class="btn-login">Login Now</a>
        </div>
    </div>
</div>

<script>
    function handleCartClick(isLoggedIn) {
        if (isLoggedIn) {
            window.location.href = "<?= $base_url ?>&page=cart";
        } else {
            document.getElementById('loginAlertModal').style.display = 'flex';
        }
    }

    function closeLoginAlert() {
        document.getElementById('loginAlertModal').style.display = 'none';
    }

    // Cart Count Update (JS)
    const count = <?= $cart_count ?>;
    document.querySelectorAll('.cart-badge-count').forEach(el => {
        el.innerText = count;
        el.style.display = count > 0 ? 'block' : 'none';
    });
</script>

</body>
</html>