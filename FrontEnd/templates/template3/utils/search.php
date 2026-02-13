<?php
include("../../../../BackEnd/config/dbconfig.php");

$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
$supplierid = isset($_POST['supplier_id']) ? $_POST['supplier_id'] : (isset($_GET['supplier_id']) ? $_GET['supplier_id'] : 0);
$category_id = (isset($_POST['category_id']) && $_POST['category_id'] !== "") ? $_POST['category_id'] : null;

$company_stmt = mysqli_prepare($conn, 'Select * from companies where supplier_id = ?');
$company_stmt->bind_param('i', $supplierid);
$company_stmt->execute();
$company_result = $company_stmt->get_result();
$company_row = $company_result->fetch_assoc();
$company_id = $company_row['company_id'];

$limit = 6;
$offset = ($page - 1) * $limit;
$like = "%$search%";

    $count_sql = "SELECT COUNT(DISTINCT p.product_id) as total
              FROM products p
              LEFT JOIN category c ON p.category_id = c.category_id
              WHERE p.company_id = ?
              AND p.status = 'available'";

    $params = [$company_id];
    $types = "i";

    if ($category_id) {
        $count_sql .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }

    if ($search !== "") {
        $count_sql .= " AND (p.product_name LIKE ? OR c.category_name LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $types .= "ss";
    }

    $c_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($c_stmt, $types, ...$params);
    mysqli_stmt_execute($c_stmt);
    $total_result = mysqli_stmt_get_result($c_stmt);
    $total_items = mysqli_fetch_assoc($total_result)['total'];
    $total_pages = ceil($total_items / $limit);

    $sql = "SELECT DISTINCT p.*
        FROM products p
        LEFT JOIN category c ON p.category_id = c.category_id
        WHERE p.company_id = ?
        AND p.status = 'available'";

    $params = [$company_id];
    $types = "i";

    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }

    if ($search !== "") {
        $sql .= " AND (p.product_name LIKE ? OR c.category_name LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $types .= "ss";
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $products_result = mysqli_stmt_get_result($stmt);


$html = "";
if ($products_result && mysqli_num_rows($products_result) > 0) {
    while ($row = mysqli_fetch_assoc($products_result)) {
        $img_src = !empty($row['image']) ? "../uploads/products/{$row['product_id']}_{$row['image']}" : "";

        $html .= '
        <div class="col-md-4 col-sm-6 col-12"> 
            <div class="card-product image">
<img src="' . $img_src . '" class="card-img-top" 
     alt="' . htmlspecialchars($row['product_name']) . '">
                <div class="card-body">
                    <h4 class="card_title">' . htmlspecialchars($row['product_name']) . '</h4>
                    <p class="card-text price">$' . number_format($row['price'], 2) . '</p>
                    <a href="?supplier_id=' . $supplierid . '&page=product_details&id=' . $row['product_id'] . '" class="btn-black-rounded">Shop Now ➔</a>
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