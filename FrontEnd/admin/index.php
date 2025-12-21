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
    const btnText = document.querySelector(".btn-text");
    const icon = document.getElementById("loadingIcon");

    form.addEventListener("submit", () => {
        btnText.style.display = "none";
        icon.style.display = "block";
    });
</script>

<?php
if (isset($_POST["submit"])) {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT * FROM admins WHERE name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $adminresult = $stmt->get_result();
    $admininfo = $adminresult->fetch_assoc();

    if (!$admininfo) {
        echo "<script>
            document.querySelector('.btn-text').style.display = 'block';
            document.getElementById('loadingIcon').style.display = 'none';
            document.querySelector('.showerror').style.display = 'block';
        </script>";
        exit();
    }


    if ($password === $admininfo['password']) {
        $_SESSION["admin_logged_in"] = true;
        $_SESSION["adminid"] = $admininfo['adminid'];

        header("Location: dashboard.php");
        exit();

    } else {
        echo "<script>
            document.querySelector('.btn-text').style.display = 'block';
            document.getElementById('loadingIcon').style.display = 'none';
            document.querySelector('.showerror').style.display = 'block';
        </script>";
    }
}

?>



</html>