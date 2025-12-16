<?php 
$host = "mysql-16bab7fd-kaungswan59-86a3.h.aivencloud.com";
$username = "avnadmin";
$password = "AVNS_kEHRdCr_cgK14vicoWI";
$port = 12525;
$database = "Malltiverse";

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Connection Failed!" . mysqli_connect_error());
}
?>
