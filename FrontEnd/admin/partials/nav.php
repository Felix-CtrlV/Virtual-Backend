<?php
include("../../BackEnd/config/dbconfig.php");

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: index.php");
    exit();
}

$adminid = $_SESSION["adminid"];
$admininfosql = "select * from admins where adminid='$adminid'";
$adminresult = mysqli_query($conn, $admininfosql);
$admininfo = mysqli_fetch_assoc($adminresult);
$name = $admininfo['name'];

$currentPage = basename($_SERVER['PHP_SELF']);

if (!isset($active)) {
    $active = '';
    if ($currentPage === 'dashboard.php')
        $active = 'dashboard';
    elseif ($currentPage === 'users.php')
        $active = 'users';
    elseif ($currentPage === 'viewsuppliers.php' or $currentPage === 'suppliersmanagement.php')
        $active = 'view-supplier';
    elseif ($currentPage === 'reviews.php')
        $active = 'reviews';
    elseif ($currentPage === 'rentingpayment.php')
        $active = 'renting';
    elseif ($currentPage === 'setting.php')
        $active = 'profile';
}

if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
    $pageSubtitle = 'High-level overview of your mall performance.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Malltiverse Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adminstyle.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <script src="https://kit.fontawesome.com/7867607d9e.js" crossorigin="anonymous"></script>
</head>

<body>
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-vr-cardboard"></i>
            </div>
            <div>
                <div class="logo-text">Malltiverse</div>
                <div class="logo-sub">Admin Console</div>
            </div>
        </div>

        <div class="nav-section-title">Overview</div>
        <ul class="nav">
            <a class="nav-button <?php echo $active === 'dashboard' ? 'active' : ''; ?>"
                href="dashboard.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/kwnsnjyg.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">Dashboard</span>
                <span class="nav-badge">Today</span>
            </a>
        </ul>

        <div class="nav-section-title">Management</div>
        <ul class="nav">
            <a class="nav-button <?php echo $active === 'users' ? 'active' : ''; ?>"
                href="users.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/spzqjmbt.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">User Management</span>
            </a>
            <a class="nav-button <?php echo $active === 'view-supplier' ? 'active' : ''; ?>"
                href="viewsuppliers.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/ntfnmkcn.json" trigger="loop" delay="2000"
                    state="hover-look-around"
                    colors="primary:#ffffff,secondary:#ffffff,tertiary:#000000,quaternary:#ffffff,quinary:#ffffff"
                    style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">View Companies</span>
            </a>
            <a class="nav-button <?php echo $active === 'reviews' ? 'active' : ''; ?>"
                href="reviews.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/xuoapdes.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">Reviews</span>
            </a>
            <a class="nav-button <?php echo $active === 'renting' ? 'active' : ''; ?>"
                href="rentingpayment.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/jeuxydnh.json" trigger="loop" delay="2000" stroke="bold"
                    state="hover-partial-roll" colors="primary:#ffffff,secondary:#ffffff"
                    style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">Rent</span>
            </a>
        </ul>

        <div class="nav-section-title">Account</div>
        <ul class="nav">
            <a class="nav-button <?php echo $active === 'profile' ? 'active' : ''; ?>"
                href="setting.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/umuwriak.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">Profile Settings</span>
            </a>
        </ul>

        <div class="nav-foot">
            <p>Signed in as <strong><?php echo htmlspecialchars($name); ?></strong></p>
            <small>Malltiverse • v1.0.0</small><br>
            <small><a href="../utils/signout.php">Sign out</a></small>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1 id="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p id="page-subtitle"><?php echo htmlspecialchars($pageSubtitle); ?></p>
            </div>
            <div class="topbar-actions">
                <span class="pill">Online</span>
                <div class="avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
            </div>
        </div>