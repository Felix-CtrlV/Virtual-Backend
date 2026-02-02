
<?php
// 1. LOGIC: Handle Successful Payment Return
if (isset($_GET['payment_status']) && $_GET['payment_status'] === 'success') {
    $is_ordered = placeOrder($conn, $customer_id, $supplier_id);

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
        position: absolute;
        right: 20px;
        top: 21px;
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
        right: 120px; /* Positioned between cart and exit buttons */
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

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .smart-header {
            padding: 15px 5%;
            flex-wrap: wrap;
            position: relative;
            height: 70px; /* Fixed height for mobile header */
        }

        .menu-toggle {
            display: block;
            right: 120px; /* Position between cart and exit */
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
            gap: 30px;
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

        .nav-menu li:nth-child(1) { animation-delay: 0.1s; }
        .nav-menu li:nth-child(2) { animation-delay: 0.2s; }
        .nav-menu li:nth-child(3) { animation-delay: 0.3s; }
        .nav-menu li:nth-child(4) { animation-delay: 0.4s; }
        .nav-menu li:nth-child(5) { animation-delay: 0.5s; }

        .nav-menu a {
            font-size: 1.2rem;
            padding: 10px 20px;
            color: #333;
        }

        .nav-menu a:hover {
            color: #0070f3;
        }

        .logo-container {
            left: 5%;
            width: 40px;
            height: 40px;
            top: 50%;
            transform: translateY(-50%);
        }

        .logo-container img {
            height: 40px;
        }

        .auth-buttons {
            position: absolute;
            right: 5%;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-icon-btn {
            left: 0;
            position: relative;
        }

        .exit-btn {
            position: relative;
            right: 0;
            margin-left: 0;
            top: 2px;
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

        /* Close button for mobile menu */
        .menu-close {
            position: absolute;
            top: 30px;
            right: 30px;
            background: none;
            border: none;
            font-size: 2rem;
            color: #333;
            cursor: pointer;
            z-index: 1002;
            display: none;
        }

        .nav-menu.active + .menu-close {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* Adjust spacing for very small screens */
        @media (max-width: 480px) {
            .menu-toggle {
                right: 110px;
            }
            
            .auth-buttons {
                right: 3%;
            }
            
            .cart-icon-btn svg {
                width: 20px;
                height: 20px;
            }
            
            .exit-btn lord-icon {
                width: 30px !important;
                height: 30px !important;
            }
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

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
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
        <a href="../customer/index.html" class="exit-btn" title="Exit Shop">
            <lord-icon
                src="https://cdn.lordicon.com/vfiwitrm.json"
                trigger="hover"
                stroke="bold"
                colors="primary:#121331,secondary:#000000"
                style="width:35px;height:35px">
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
</script>