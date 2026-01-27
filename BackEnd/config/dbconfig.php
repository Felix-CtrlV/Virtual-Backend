<?php
// Database Credentials
$host = "mysql-16bab7fd-kaungswan59-86a3.h.aivencloud.com";
$username = "avnadmin";
$password = "AVNS_kEHRdCr_cgK14vicoWI";
$port = 12525;
$database = "Malltiverse";

// Local Fallback (Commented out)
// $host = "localhost";
// $username = "root";
// $password = "kaung273";
// $database = "malltiverse";
// $port = 3306;

// 1. Configure Session Cookie BEFORE starting the session
// This ensures the cookie is valid for the root path '/', meaning it works in /Admin/login/ AND /Admin/utils/
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // 0 means "until the browser is closed"
        'path' => '/',            // Valid for the entire domain
        'domain' => '',           // Uses the current domain automatically
        'secure' => false,        // Set to true if you are using HTTPS
        'httponly' => true,       // JavaScript cannot access the cookie (security)
        'samesite' => 'Lax'       // Prevents some CSRF attacks
    ]);
    session_start();
}

// 2. Connect to Database
$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
?>