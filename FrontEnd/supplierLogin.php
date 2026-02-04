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
    <style>
        /* Responsive CSS Overrides */
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }
        
        .container {
            display: flex;
            width: 100%;
            height: 100vh;
        }
        
        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, #1e1e24 0%, #2a2a35 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px;
            color: white;
        }
        
        .right-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px;
            background: #fff;
            overflow-y: auto;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f8f8f8;
            box-sizing: border-box;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .submit-btn:hover { background: #333; }

        .social-login {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .top-row { display: flex; justify-content: space-between; align-items: center; }
        .back-btn { color: white; text-decoration: none; opacity: 0.8; }
        .logo-icon { font-size: 30px; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .left-panel {
                display: none; /* Hide visual panel on mobile */
            }

            .right-panel {
                width: 100%;
                padding: 25px;
                height: 100%;
                justify-content: flex-start;
                padding-top: 60px;
                box-sizing: border-box;
            }
            
            @media (max-width: 400px) {
                .social-login { flex-direction: column; }
            }
        }
    </style>
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
                    <h2 style="font-size: 36px; line-height: 1.2;">Where Malls,<br>Transcend Reality.</h2>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <h1>Login</h1>
            <p class="sub-text" style="margin-bottom: 30px; color: #666;">Doesn't have an account? <a href="pricing.php" style="color: #000; font-weight: 600;">Create Account</a></p>

            <p id="message" style="color: red; margin-bottom: 10px;"> <?= $message; ?> </p>
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
                    <i id="togglePassword" class="fa-regular fa-eye eye-icon" style="position: absolute; right: 15px; top: 18px; cursor: pointer; color: #666;"></i>
                </div>
                
                <div style="text-align: right; margin-bottom: 15px;">
                    <a href="#" id="forgot-password-link" style="color: #666; text-decoration: none; font-size: 0.9rem;">Forgot Password?</a>
                </div>
                
                <button type="submit" name="submit" class="submit-btn">Login</button>
            </form>

            <div class="divider" style="text-align: center; margin: 25px 0; color: #999; font-size: 14px; position: relative;">
                <span>Or Login with</span>
            </div>

            <div class="social-login">
                <button type="button" class="social-btn" onclick="window.location.href='utils/google_oauth.php?type=supplier'">
                    <i class="fa-brands fa-google" style="color:#DB4437;"></i> Google
                </button>
                <button type="button" class="social-btn" onclick="window.location.href='utils/github_oauth.php?type=supplier'">
                    <i class="fa-brands fa-github" style="color:#333;"></i> GitHub
                </button>
            </div>
            
            <div id="forgot-password-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: #fff; padding: 30px; border-radius: 10px; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                    <h3 style="margin-top: 0; color: #333;">Reset Password</h3>
                    <p id="forgot-message" style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">Enter your email to receive a reset link.</p>
                    <input type="email" id="forgot-email" placeholder="Enter your email" style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button onclick="document.getElementById('forgot-password-modal').style.display='none'" style="flex: 1; padding: 10px; background: #f5f5f5; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">Cancel</button>
                        <button onclick="handleForgotPassword()" style="flex: 1; padding: 10px; background: #000; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">Send Link</button>
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