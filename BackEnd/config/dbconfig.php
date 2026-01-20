<?php
$host = "mysql-16bab7fd-kaungswan59-86a3.h.aivencloud.com";
$username = "avnadmin";
$password = "AVNS_kEHRdCr_cgK14vicoWI";
$port = 12525;
$database = "Malltiverse";

// $host = "localhost";
// $username = "root";
// $password = "kaung273";
// $database = "malltiverse";
// $port = 3306;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Connection Failed!" . mysqli_connect_error());
} else {
    // echo "Connected Successfully";
}
?>