<?php
$message = "";

// --- LOGIC TO DETERMINE RETURN URL ---
// 1. Default destination
$redirectUrl = 'index.php';

// 2. Check if a specific return URL was passed in the link (e.g. customerLogin.php?return_url=cart.php)
if (isset($_GET['return_url']) && !empty($_GET['return_url'])) {
    $redirectUrl = $_GET['return_url'];
}
// 3. If not, try to get the previous page from the server headers
elseif (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    // Prevent infinite redirect loop
    $referer = $_SERVER['HTTP_REFERER'];
    if (strpos($referer, 'customerLogin.php') === false && strpos($referer, 'customerRegister.php') === false) {
        $redirectUrl = $referer;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/Css/customerAuth.css">
</head>
<body>
    <div class="container">
        <div class="left-panel login-visual">
            <div class="top-row">
                <div class="logo-icon"><i class="fas fa-shopping-bag"></i></div>
                <a href="index.html" class="back-btn">Back to Home &rarr;</a>
            </div>
            <div class="bottom-row">
                <div class="quote">
                    <h2>Experience Shopping,<br>Beyond Limits.</h2>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <h1>Welcome Back</h1>
            <p class="sub-text">New here? <a href="customerRegister.php">Create Customer Account</a></p>
            <p id="message" class="<?= !empty($message) ? 'error-msg' : '' ?>"><?= $message; ?></p>

            <form id="loginform" method="POST">
                <input type="hidden" id="return_url" value="<?= htmlspecialchars($redirectUrl) ?>">

                <div class="input-group">
                    <input autocomplete="off" type="email" name="email" id="email" placeholder="Email Address" required>
                </div>

                <div class="input-group password-container">
                    <input autocomplete="off" type="password" id="password" name="password" placeholder="Password" required>
                    <i id="togglePassword" class="fa-regular fa-eye eye-icon"></i>
                </div>

                <div style="text-align: right; margin-bottom: 15px;">
                    <a href="#" id="forgot-password-link" style="color: #666; text-decoration: none; font-size: 0.9rem;">Forgot Password?</a>
                </div>

                <button type="submit" class="submit-btn">Login</button>
            </form>

            <div class="divider">
                <span>Or continue with</span>
            </div>

            <div class="social-login">
                <button type="button" class="social-btn" onclick="window.location.href='utils/google_oauth.php?type=customer&return_url=<?= urlencode($redirectUrl) ?>'">
                    <i class="fa-brands fa-google" style="color:#DB4437;"></i> Google
                </button>
                <button type="button" class="social-btn" onclick="window.location.href='utils/github_oauth.php?type=customer&return_url=<?= urlencode($redirectUrl) ?>'">
                    <i class="fa-brands fa-github"></i> GitHub
                </button>
            </div>

            <div id="forgot-password-modal" style="display: none; position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background: linear-gradient(rgb(111 22 253 / 60%), rgb(73 73 120 / 90%)); padding:30px; border-radius:10px; max-width:400px; width:90%;">
                    <h3 style="margin-top:0;">Reset Password</h3>
                    <p id="forgot-message" style="color: #666; font-size: 0.9rem;"></p>
                    <input type="email" id="forgot-email" placeholder="Enter your email" style="width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:5px;">
                    <div style="display:flex; gap:10px; margin-top:15px;">
                        <button type="button" onclick="document.getElementById('forgot-password-modal').style.display='none'" style="flex:1; padding:10px; background:#f5f5f5; border:none; border-radius:5px; cursor:pointer;">Cancel</button>
                        <button type="button" onclick="handleForgotPassword()" style="flex:1; padding:10px; background:#000; color:white; border:none; border-radius:5px; cursor:pointer;">Send Reset Link</button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Password toggle
        const password = document.getElementById('password');
        const toggle = document.getElementById('togglePassword');
        toggle.addEventListener('click', () => {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        });

        // Forgot Password
        document.getElementById('forgot-password-link').addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('forgot-password-modal').style.display = 'flex';
        });

        function handleForgotPassword() {
            const email = document.getElementById('forgot-email').value.trim();
            const messageEl = document.getElementById('forgot-message');

            if (!email) {
                messageEl.textContent = 'Please enter your email';
                messageEl.style.color = '#d32f2f';
                return;
            }

            fetch('utils/forgot_password.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({email: email, type: 'customer'})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    messageEl.textContent = data.message;
                    messageEl.style.color = '#388e3c';
                    document.getElementById('forgot-email').value = '';
                    setTimeout(() => {document.getElementById('forgot-password-modal').style.display='none';},2000);
                } else {
                    messageEl.textContent = data.message;
                    messageEl.style.color = '#d32f2f';
                }
            })
            .catch(err => {
                messageEl.textContent = 'Error sending reset link. Please try again.';
                messageEl.style.color = '#d32f2f';
            });
        }

        // AJAX login logic (UPDATED TO HANDLE BANNED REDIRECTS)
        const form = document.getElementById('loginform');
        const message = document.getElementById('message');

        form.addEventListener('submit', (e)=>{
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const returnUrl = document.getElementById('return_url').value;

            fetch('utils/customerLoginUtil.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({email, password, return_url: returnUrl})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    // Redirect to the return URL from backend
                    window.location.href = data.return_url || 'index.php';
                } else {
                    // Check if we need to redirect to Banned Page
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        // Standard Error
                        message.classList.add('error-msg');
                        message.textContent = data.message || "Invalid Credentials";
                    }
                }
            })
            .catch(err=>{
                console.error(err);
                message.textContent = "Server error. Please try again.";
            });
        });
    </script>
</body>
</html>