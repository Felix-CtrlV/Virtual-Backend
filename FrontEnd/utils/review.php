<?php
    include("../../BackEnd/config/dbconfig.php");

    $text = $_POST["review"];
    $rating = $_POST["rating"];
    $supplier_id = isset($_GET['supplier_id']) ? $_GET['supplier_id'] : 0;
    $customer_id = 1; // replace with session customerid

    $stmt = $conn->prepare('insert into reviews(supplier_id, customer_id, review, rating, created_at) values(?, ?, ?, ?, NOW())');
    $stmt->bind_param('iisi',  $supplier_id, $customer_id, $text, $rating);
    $stmt->execute();   
    if($stmt->affected_rows > 0) {
        header('location: ../shop/?supplier_id=' . $supplier_id . '&page=review');
        exit();
    }else{
        // Handle error
    }
?>