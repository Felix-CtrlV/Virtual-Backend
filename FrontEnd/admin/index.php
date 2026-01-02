<?php
include("../../BackEnd/config/dbconfig.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/style.css">
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.12.21/build/spline-viewer.js"></script>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
</head>

<body class="adminlogincontainer">
    <!-- <spline-viewer class="robotmodel" url="https://prod.spline.design/6qRF8rgLp7IgQBCX/scene.splinecode"></spline-viewer>-->
    <spline-viewer class="robotmodel"
        url="https://prod.spline.design/RhUHenI1aUGPujeh/scene.splinecode"></spline-viewer>

    <div class="adminloginbox">

        <h1>Admin Login</h1>

        <form class="loginform" action="" method="post">
            <div class="row">
                <label for="username">Username :</label>
                <input autocomplete="off" type="text" id="username" name="username" required><br><br>
            </div>
            <div class="row">
                <label for="password">Password :</label>
                <input autocomplete="off" type="password" id="password" name="password" required><br><br>
            </div>
            <span class="showerror">Username or Password is Incorrect</span>
            <button type="submit" name="submit" class="login-btn">
                <span class="btn-text">Login</span>
                <lord-icon id="loadingIcon" src="https://cdn.lordicon.com/izqdfqdl.json" trigger="loop"
                    state="loop-queue" colors="primary:#000000" style="width:30px;height:30px">
                </lord-icon>
            </button>

        </form>
    </div>
</body>

<script>
    const form = document.querySelector(".loginform");
    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        document.querySelector(".btn-text").style.display = "none";
        document.getElementById("loadingIcon").style.display = "block";

        login(username, password);
    });
</script>



<script>
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
                buttontext.style.display = 'block';
                loadingicon.style.display = 'none';
            });
    }

</script>

</html>