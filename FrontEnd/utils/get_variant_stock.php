<?php
include '../../BackEnd/config/dbconfig.php';

if (isset($_GET['id'])) {
    $variant_id = (int)$_GET['id'];
    $query = "SELECT quantity FROM product_variant WHERE variant_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $variant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    echo json_encode(['stock' => ($data ? (int)$data['quantity'] : 0)]);
}
?>