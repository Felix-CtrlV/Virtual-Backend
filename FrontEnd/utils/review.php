<?php
    include("../../BackEnd/config/dbconfig.php");

    $text = $_POST["review"];
    $rating = $_POST["rating"];
    $company_id = isset($_GET['company_id']) ? $_GET['company_id'] : 0;
    $customer_id = $_SESSION['customerid'];

    $stmt = $conn->prepare('insert into reviews(company_id, customer_id, review, rating, created_at) values(?, ?, ?, ?, NOW())');
    $stmt->bind_param('iisi',  $company_id, $customer_id, $text, $rating);
    $stmt->execute();   
    if($stmt->affected_rows > 0) {
        header('location: ../shop/?company_id=' . $company_id . '&page=review');
        exit();
    }else{
        // Handle error
    }
?>  