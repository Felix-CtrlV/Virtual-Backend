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
            <p class="sub-text">Doesn't have an account? <a href="supplierRegister.php">Create Account</a></p>

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
                
                <button type="submit" name="submit" class="submit-btn">Login</button>
            </form>

            <div class="divider">
                <span>Or Login with</span>
            </div>

            <div class="social-login">
                <button class="social-btn">
                    <i class="fa-brands fa-google" style="color:#DB4437;"></i> Google
                </button>
                <button class="social-btn">
                    <i class="fa-brands fa-github" style="color:#fff;"></i> GitHub
                </button>
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

</script>


</html>7