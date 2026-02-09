<?php
$message = "";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/Css/supplier.css">
</head>

<body>

    <div class="container">
        <div class="left-panel">
            <div class="top-row">
                <div class="logo-icon"> <i class="fas fa-vr-cardboard"></i>
                </div>
                <a href="index.html" class="back-btn">Back to website &rarr;</a>
            </div>

            <div class="bottom-row">
                <div class="quote">
                    <h2>Where Malls,<br>Transcend Reality.</h2>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <h1>Login</h1>
            <p class="sub-text">Doesn't have an account? <a href="pricing.php">Create Account</a></p>

            <p id="message"> <?= $message; ?> </p>
            <form id="loginform" method="POST">
                <div class="row">
                    <div class="input-group">
                        <input autocomplete="off" type="text" name="name" id="name" placeholder="Name" required>
                    </div>
                </div>

                <div class="input-group">
                    <input autocomplete="off" type="email" name="email" id="email" placeholder="Email" required>
                </div>

                <div class="input-group password-container">
                    <input autocomplete="off" type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i id="togglePassword" class="fa-regular fa-eye eye-icon"></i>
                </div>
                
                <div style="text-align: right; margin-bottom: 15px;">
                    <a href="#" id="forgot-password-link" style="color: #666; text-decoration: none; font-size: 0.9rem;">Forgot Password?</a>
                </div>
                
                <button type="submit" name="submit" class="submit-btn">Login</button>
            </form>

            <div class="divider">
                <span>Or Login with</span>
            </div>

            <div class="social-login">
                <button type="button" class="social-btn" onclick="window.location.href='utils/google_oauth.php?type=supplier'">
                    <i class="fa-brands fa-google" style="color:#DB4437;"></i> Google
                </button>
                <button type="button" class="social-btn" onclick="window.location.href='utils/github_oauth.php?type=supplier'">
                    <i class="fa-brands fa-github" style="color:#fff;"></i> GitHub
                </button>
            </div>
            
            <!-- Forgot Password Modal -->
            <div id="forgot-password-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: linear-gradient(rgb(111 22 253 / 60%), rgb(73 73 120 / 90%)); padding: 30px; border-radius: 10px; max-width: 400px; width: 90%;">
                    <h3 style="margin-top: 0;">Reset Password</h3>
                    <p id="forgot-message" style="color: #666; font-size: 0.9rem;"></p>
                    <input type="email" id="forgot-email" placeholder="Enter your email" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px;">
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button onclick="document.getElementById('forgot-password-modal').style.display='none'" style="flex: 1; padding: 10px; background: #f5f5f5; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
                        <button onclick="handleForgotPassword()" style="flex: 1; padding: 10px; background: #000; color: white; border: none; border-radius: 5px; cursor: pointer;">Send Reset Link</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

<script>
    const form = document.getElementById('loginform');
    const message = document.getElementById('message');

    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        if (!name || !email || !password) {
            message.textContent = "All fields are required.";
            return;
        }

        message.className = '';

        login(name, email, password);
    });

    function login(name, email, password) {
        fetch('utils/supplierUtil.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, password })
        })
            .then(res => res.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    throw new Error("Invalid JSON from server");
                }

                if (data.success) {
                    window.location.href = 'suppliers/dashboard.php';

                } else {
                    message.classList.remove('success-msg')
                    message.classList.add('error-msg');
                    message.textContent = data.message || "Wrong Username, Email or Password";
                }
            })
            .catch(err => {
                console.error(err);
                message.textContent = "Server error. Please try again.";
            });
    }
</script>

<script>
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
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, type: 'supplier' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                messageEl.textContent = data.message;
                messageEl.style.color = '#388e3c';
                document.getElementById('forgot-email').value = '';
                setTimeout(() => {
                    document.getElementById('forgot-password-modal').style.display = 'none';
                }, 2000);
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
        
</script>

</html>