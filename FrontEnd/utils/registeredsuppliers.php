<?php
include("../../BackEnd/config/dbconfig.php");

$name = $_POST["name"];
$email = $_POST["email"];
$password = $_POST["password"];
$hashed = password_hash($password, PASSWORD_DEFAULT);

$phone = $_POST["phone"];
$address = $_POST["address"];

$shopname = $_POST["shopname"];
$tags = $_POST["tags"];
$shopdescription = $_POST["shopdescription"];

$primary = $_POST["primary"];
$secondary = $_POST["secondary"];
$about = $_POST["about"];
$template = $_POST["selected_template"];

$supplierquery = $conn->prepare('INSERT INTO suppliers(name, email, password, company_name, tags, description, address, phone, created_at, template_id, renting_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NULL)');

if ($supplierquery) {
    $supplierquery->bind_param('ssssssssi', $name, $email, $hashed, $shopname, $tags, $shopdescription, $address, $phone, $template);
    $supplierquery->execute();
    $supplier_id = $conn->insert_id;
    $supplierquery->close();
    $uploadDir = "../uploads/shops/$supplier_id/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // $logoPath = null;

    if (isset($_FILES['logoimage']) && $_FILES['logoimage']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['logoimage']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            die("Invalid logo type");
        }
        
        $logoName = "logo.$ext";
        move_uploaded_file($_FILES['logoimage']['tmp_name'], $uploadDir . $logoName);

    }

    // $bannerPath = null;

    if (isset($_FILES['bannerimage']) && $_FILES['bannerimage']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['bannerimage']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            die("Invalid banner type");
        }

        $bannerName = "banner.$ext";
        move_uploaded_file($_FILES['bannerimage']['tmp_name'], $uploadDir . $bannerName);
    }

    $assetsquery = $conn->prepare('insert into shop_assets(supplier_id, logo, banner, primary_color, secondary_color, about) values (?,?,?,?,?,?)');
    $assetsquery->bind_param('isssss', $supplier_id, $logoName, $bannerName, $primary, $secondary, $about);
    $assetsquery->execute();
    $assetsquery->close();  

    // $renting = $conn->prepare("insert into rent_payments(supplier_id, due_date, paid_amount, month) values(?,?,?,?)");
    // $renting->bind_param("isss", $supplier_id, )

    // header("Location: ../supplierLogin.php");

}

?>