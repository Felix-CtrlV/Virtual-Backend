<?php
include("../../../../BackEnd/config/dbconfig.php");

$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$supplierid = isset($_POST['supplier_id']) ? $_POST['supplier_id'] : (isset($_GET['supplier_id']) ? $_GET['supplier_id'] : 0);
$category_id = (isset($_POST['category_id']) && $_POST['category_id'] !== "") ? $_POST['category_id'] : null;

$company_stmt = mysqli_prepare($conn,'Select * from companies where supplier_id = ?');
$company_stmt->bind_param('i', $supplierid);
$company_stmt->execute();
$company_result = $company_stmt->get_result();
$company_row = $company_result->fetch_assoc();
$company_id = $company_row['company_id'];

$limit = 6;
$offset = ($page - 1) * $limit;
$like = "%$search%";

if ($category_id) {
    $count_sql = "SELECT COUNT(*) as total FROM products WHERE company_id = ? AND category_id = ? AND status = 'available'";
    $c_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($c_stmt, "ii", $company_id, $category_id);
} elseif ($search !== "") {
    $count_sql = "SELECT COUNT(*) as total FROM products p INNER JOIN category c ON p.category_id = c.category_id WHERE p.company_id = ? AND c.category_name LIKE ? AND p.status = 'available'";
    $c_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($c_stmt, "is", $company_id, $like);
} else {
    $count_sql = "SELECT COUNT(*) as total FROM products WHERE company_id = ? AND status = 'available'";
    $c_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($c_stmt, "i", $company_id);
}

mysqli_stmt_execute($c_stmt);
$total_result = mysqli_stmt_get_result($c_stmt);
$total_items = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_items / $limit);

if ($category_id) {
    $sql = "SELECT * FROM products WHERE company_id = ? AND category_id = ? AND status = 'available' ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiii", $company_id, $category_id, $limit, $offset);
} elseif ($search !== "") {
    $sql = "SELECT p.* FROM products p INNER JOIN category c ON p.category_id = c.category_id WHERE p.company_id = ? AND c.category_name LIKE ? AND p.status = 'available' ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isii", $company_id, $like, $limit, $offset);
} else {
    $sql = "SELECT * FROM products WHERE company_id = ? AND status = 'available' ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $company_id, $limit, $offset);
}

mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);

$html = "";
if ($products_result && mysqli_num_rows($products_result) > 0) {
    while ($row = mysqli_fetch_assoc($products_result)) {
        $img_src = !empty($row['image']) ? "../uploads/products/{$row['product_id']}_{$row['image']}" : "";
        
        $html .= '
        <div class="col-md-4 col-sm-6 col-12"> 
            <div class="card-product image">
                <img src="'.$img_src.'" class="card-img-top" alt="'.htmlspecialchars($row['product_name']).'">
                <div class="card-body">
                    <h4 class="card_title">'.htmlspecialchars($row['product_name']).'</h4>
                    <p class="card-text price">$'.number_format($row['price'], 2).'</p>
                    <a href="?supplier_id='.$supplierid.'&page=product_details&id='.$row['product_id'].'" class="btn-black-rounded">Shop Now ➔</a>
                </div>
            </div>
        </div>';
    }
} else {
    $html = '<div class="col-12"><p class="text-center">No products found for this selection.</p></div>';
}

header('Content-Type: application/json');
echo json_encode([
    'html' => $html,
    'total_pages' => $total_pages
]);
?>