<?php
include("../../BackEnd/config/dbconfig.php");
// Start session to handle potential OAuth errors or messages
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.12.21/build/spline-viewer.js"></script>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>

</head>

<body class="adminlogincontainer">

    <spline-viewer class="robotmodel"
        url="https://prod.spline.design/4BgGmVOudYtxvUyp/scene.splinecode"></spline-viewer>

    <div class="adminloginbox">

        <h1>Welcome Back</h1>
        <p class="subtitle">Enter your credentials to access the admin panel.</p>

        <span class="showerror">Username or Password is Incorrect</span>

        <form class="loginform" action="" method="post">
            <div class="input-group">
                <label for="username">Username</label>
                <input autocomplete="off" type="text" id="username" name="username" placeholder="e.g. admin" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input autocomplete="off" type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" name="submit" class="login-btn">
                <span class="btn-text">Login to Dashboard</span>
                <lord-icon id="loadingIcon" src="https://cdn.lordicon.com/izqdfqdl.json" trigger="loop"
                    state="loop-queue" colors="primary:#ffffff" style="width:25px;height:25px">
                </lord-icon>
            </button>
        </form>

        <div class="divider">
            <span>OR CONTINUE WITH</span>
        </div>

        <div class="social-login">
            <a href="../utils/google_oauth.php?type=admin" class="social-btn google-btn">
                <i class="fab fa-google"></i> Google
            </a>
            <a href="../utils/github_oauth.php?type=admin" class="social-btn github-btn">
                <i class="fab fa-github"></i> GitHub
            </a>
        </div>
    </div>
</body>

<script>
    const form = document.querySelector(".loginform");

    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        // UI Loading State
        const btnText = document.querySelector(".btn-text");
        const loadingIcon = document.getElementById("loadingIcon");
        const errorText = document.querySelector('.showerror');

        btnText.style.display = "none";
        loadingIcon.style.display = "block";
        errorText.style.display = 'none';

        login(username, password);
    });

    function login(username, password) {
        const errortext = document.querySelector('.showerror');
        const buttontext = document.querySelector('.btn-text');
        const loadingicon = document.getElementById('loadingIcon');

        fetch('utils/admin_login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    errortext.style.display = 'block';
                    buttontext.style.display = 'block';
                    loadingicon.style.display = 'none';
                }
            })
            .catch(err => {
                console.error('Login error:', err);
                errortext.style.display = 'block';
                errortext.innerText = "Server connection failed.";
                buttontext.style.display = 'block';
                loadingicon.style.display = 'none';
            });
    }
</script>

</html>