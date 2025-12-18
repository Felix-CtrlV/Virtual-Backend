<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.12.21/build/spline-viewer.js"></script>
</head>
<!-- <spline-viewer class="model" url="https://prod.spline.design/YWvYFF2tEZVrT0X0/scene.splinecode"></spline-viewer> -->

<body class="adminlogincontainer">
    <spline-viewer class="robotmodel"
        url="https://prod.spline.design/6qRF8rgLp7IgQBCX/scene.splinecode"></spline-viewer>

    <div class="adminloginbox">
        <h1>Admin Login</h1>
        <form class="loginform" action="login.php" method="post">
            <div class="row">
                <label for="username">Username :</label>
                <input type="text" id="username" name="username" required><br><br>
            </div>
            <div class="row">
                <label for="password">Password :</label>
                <input type="password" id="password" name="password" required><br><br>
            </div>
            <input type="submit" value="Login">
        </form>
    </div>

</body>

</html>