<?php
// Removed session_start() since it's already started in shop/index.php
// session_start(); // REMOVED

// Check if user is logged in (assuming session is set from index/customer area)
$isLoggedIn = isset($_SESSION['customer_id']) || isset($_SESSION['user_id']);




// Get profile picture from session or database
$profilePic = '';
if (isset($_SESSION['customer_id']) && $isLoggedIn) {
    // Get profile picture path from your customer system
    $customer_id = $_SESSION['customer_id'];

    // Try to fetch from database (similar to customer_profile.php)
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT name, image FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $profilePic = $row['image'];
            $customerName = $row['name'];
        }
    }

    // Also check session if available (set during login)
    if (empty($profilePic) && isset($_SESSION['user_image'])) {
        $profilePic = $_SESSION['user_image'];
    }
}

if (!isset($company_id) || $company_id <= 0) {
    $company_id = isset($supplier['company_id']) ? (int)$supplier['company_id'] : 0;
    if ($company_id <= 0 && !empty($supplier_id) && isset($conn)) {
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_id FROM companies WHERE supplier_id = " . (int)$supplier_id . " LIMIT 1"));
        $company_id = $r ? (int)$r['company_id'] : 0;
    }
}
if (isset($_GET['payment_status']) && $_GET['payment_status'] === 'success' && !empty($customer_id)) {
    $is_ordered = placeOrder($conn, $customer_id, $company_id);

    if ($is_ordered) {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const Toast = Swal.mixin({
                  toast: true,
                  position: 'top-end',
                  showConfirmButton: false,
                  timer: 3000,
                  timerProgressBar: true
                });

                Toast.fire({
                  icon: 'success',
                  title: 'Ordered successfully!'
                }).then(() => {
                    window.location.href = '?supplier_id=$supplier_id&page=cart';
                });
            });
        </script>";
    }
}
?>
<link rel="stylesheet" href="../templates/template4/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.lordicon.com/lordicon.js"></script>
<style>
    /* Added styles for Exit Button */
    .exit-btn {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.3s;
        text-decoration: none;
        margin-left: 10px;
    }

    .exit-btn:hover {
        opacity: 0.7;
    }

    /* Mobile Menu Button */
    .menu-toggle {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        z-index: 1001;
        position: absolute;
        right: 100px;
        /* Adjusted position */
    }

    .menu-toggle span {
        display: block;
        width: 25px;
        height: 3px;
        background: #333;
        margin: 5px 0;
        transition: 0.3s;
        border-radius: 2px;
    }

    /* SIMPLIFIED Profile Styles - Better Horizontal Alignment */
    .profile-section {
        display: flex;
        align-items: center;
        position: relative;
        margin-left: 10px;
    }

    .profile-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 6px 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #333;
        font-family: inherit;
        font-size: 0.85rem;
        border-radius: 20px;
        transition: all 0.3s;
        border: 1px solid #e0e0e0;
    }

    .profile-btn:hover {
        background-color: #f5f5f5;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .profile-pic {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #667eea;
    }

    .profile-default {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        border: 2px solid #667eea;
    }

    .profile-name {
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 500;
    }

    .profile-dropdown {
        position: absolute;
        top: 45px;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        min-width: 120px;
        z-index: 10002;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        border: 1px solid #eaeaea;
        padding: 8px 0;
    }

    .profile-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        color: #333;
        text-decoration: none;
        transition: background-color 0.3s;
        gap: 8px;
        font-size: 0.85rem;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #667eea;
    }

    .dropdown-item i {
        width: 18px;
        text-align: center;
        color: #667eea;
        font-size: 0.9rem;
    }

    /* Auth Buttons for non-logged in users */
    .auth-buttons-container {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: 10px;
    }


    .auth-btn {
        padding: 6px 14px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.3s;
        white-space: nowrap;
        font-weight: 500;
    }

    .auth-btn.login {
        background: transparent;
        border: 1px solid #667eea;
        color: #667eea;
    }

    .auth-btn.signup {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
    }

    .auth-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }

    /* Main header layout adjustments */
    .smart-header {
        display: flex;
        align-items: center;
        padding: 15px 40px;
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        position: relative;
        z-index: 1000;
    }

    .auth-buttons {
        display: flex;
        align-items: center;
        margin-left: auto;
    }

    .cart-icon-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        position: relative;
        margin-right: 10px;
        left: 0px;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .smart-header {
            padding: 15px 20px;
            height: 60px;
        }

        .cart-icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            position: relative;
            margin-right: 10px;
            left: -40px;
        }

        .auth-buttons-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 5px;
            position: relative;
            right: 40px;
        }

        .menu-toggle {
            display: block;
            right: 80px;
            top: 50%;
            transform: translateY(-50%);
        }

        .nav-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 0;
            z-index: 1000;
            gap: 20px;
            transition: transform 0.3s ease-in-out;
        }

        .nav-menu.active {
            display: flex;
            animation: slideIn 0.3s ease-out;
        }

        .nav-menu li {
            margin: 0;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards;
        }

        .nav-menu li:nth-child(1) {
            animation-delay: 0.1s;
        }

        .nav-menu li:nth-child(2) {
            animation-delay: 0.2s;
        }

        .nav-menu li:nth-child(3) {
            animation-delay: 0.3s;
        }

        .nav-menu li:nth-child(4) {
            animation-delay: 0.4s;
        }

        .nav-menu li:nth-child(5) {
            animation-delay: 0.5s;
        }

        .nav-menu a {
            font-size: 1.1rem;
            padding: 10px 20px;
            color: #333;
        }

        .nav-menu a:hover {
            color: #667eea;
        }

        .logo-container {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .logo-container img {
            height: 35px;
        }

        .auth-buttons {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Profile section mobile adjustments */
        .profile-name {
            display: none;
        }

        .profile-dropdown {
            position: fixed;
            top: 60px;
            right: 20px;
            left: auto;
            min-width: 100px;
            width: auto;
        }

        /* Adjust spacing for very small screens */
        @media (max-width: 480px) {
            .smart-header {
                padding: 15px 15px;
            }

            .menu-toggle {
                right: 70px;
            }

            .auth-buttons {
                right: 15px;
            }

            .logo-container {
                left: 15px;
            }

            .logo-container img {
                height: 30px;
            }

            .auth-btn {
                padding: 5px 10px;
                font-size: 0.75rem;
            }

            .profile-btn {
                padding: 5px 8px;
                position: relative;
                right: 50px;
            }

            .profile-pic,
            .profile-default {
                width: 28px;
                height: 28px;
            }
        }

        /* Menu toggle animation */
        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Ensure cart popup stays on top */
    .cart-popup {
        z-index: 10003;
    }
</style>

<header class="smart-header">
    <div class="logo-container">
        <img src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['logo']) ?>"
            alt="<?= htmlspecialchars($supplier['company_name']) ?> Logo"
            class="site-logo">
    </div>

    <!-- Mobile Menu Button -->
    <button class="menu-toggle" id="menu-toggle" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <ul class="nav-menu" id="nav-menu">
        <?php
        $base_url = "?supplier_id=" . $supplier_id;
        ?>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'home' ? 'active' : '' ?>" href="<?= $base_url ?>&page=home">Home</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'products' ? 'active' : '' ?>" href="<?= $base_url ?>&page=products">Products</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'about' ? 'active' : '' ?>" href="<?= $base_url ?>&page=about">About</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'contact' ? 'active' : '' ?>" href="<?= $base_url ?>&page=contact">Contact</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'review' ? 'active' : '' ?>" href="<?= $base_url ?>&page=review">Review</a>
        </li>
    </ul>

    <div class="auth-buttons">
        <button class="cart-icon-btn" id="cart-icon-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span class="cart-badge" id="cart-badge">0</span>
        </button>

        <?php if ($isLoggedIn): ?>
            <!-- Profile Dropdown for logged in users -->
            <div class="profile-section">
                <button class="profile-btn" id="profile-btn">
                    <?php if (!empty($profilePic)): ?>
                        <?php
                        // Check if it's a full URL or just filename
                        if (strpos($profilePic, 'http') === 0 || strpos($profilePic, '/') === 0) {
                            // Full URL or absolute path
                            $picSrc = $profilePic;
                        } else {
                            // Just filename, use your customer profile path
                            $picSrc = '../assets/customer_profiles/' . $profilePic;
                        }
                        ?>
                        <img src="<?= htmlspecialchars($picSrc) ?>" alt="Profile" class="profile-pic">
                    <?php else: ?>
                        <div class="profile-default">
                            <?= strtoupper(substr($customerName, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <span class="profile-name"><?= htmlspecialchars($customerName) ?></span>
                    <i class="fas fa-chevron-down" style="font-size: 0.8rem;"></i>
                </button>
                <div class="profile-dropdown" id="profile-dropdown">
                    <a href="../customer_profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                    <a href="../utils/logout.php<?= isset($_GET['supplier_id']) ? '?supplier_id=' . $_GET['supplier_id'] : '' ?>" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Auth Buttons for non-logged in users -->
            <div class="auth-buttons-container">
                <a href="../customerLogin.php" class="auth-btn login">Login</a>
                <a href="../customerRegister.php" class="auth-btn signup">Sign Up</a>
            </div>
        <?php endif; ?>

        <a href="../customer/index.html" class="exit-btn" title="Exit Shop">
            <lord-icon
                src="https://cdn.lordicon.com/vfiwitrm.json"
                trigger="hover"
                stroke="bold"
                colors="primary:#121331,secondary:#000000"
                style="width:30px;height:30px">
            </lord-icon>
        </a>
    </div>

    <div class="cart-popup" id="cart-popup">
        <div class="cart-popup-content">
            <div class="cart-popup-header">
                <h3>Your Cart</h3>
                <button class="cart-close-btn" id="cart-close-btn">&times;</button>
            </div>
            <div class="cart-popup-body" id="cart-items-container">
                <div class="cart-empty">Your cart is empty</div>
            </div>
            <div class="cart-popup-footer" id="cart-footer" style="display: none;">
                <div class="cart-total">
                    <span>Total:</span>
                    <span id="cart-total-amount">$0.00</span>
                </div>
                <button class="cart-checkout-btn" onclick="window.location.href='?supplier_id=<?= $supplier_id ?>&page=cart'">Checkout</button>
            </div>
        </div>
    </div>

    <div class="minimal-alert" id="minimal-alert"></div>

</header>

<script>
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menu-toggle');
    const navMenu = document.getElementById('nav-menu');

    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');

            // Close profile dropdown when opening mobile menu
            const profileDropdown = document.getElementById('profile-dropdown');
            if (profileDropdown) profileDropdown.classList.remove('active');

            // Prevent body scroll when menu is open
            if (navMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        // Close menu when clicking on a link
        const navLinks = navMenu.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (navMenu.classList.contains('active') &&
                !navMenu.contains(event.target) &&
                !menuToggle.contains(event.target)) {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

    // Profile Dropdown Toggle
    const profileBtn = document.getElementById('profile-btn');
    const profileDropdown = document.getElementById('profile-dropdown');

    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');

            // Close mobile menu if open
            if (navMenu) {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (profileBtn && !profileBtn.contains(event.target) &&
                profileDropdown && !profileDropdown.contains(event.target)) {
                profileDropdown.classList.remove('active');
            }
        });

        // Close dropdown on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && profileDropdown) {
                profileDropdown.classList.remove('active');
            }
        });
    }
</script>