<?php
// PATH FIX: Go up 4 levels to reach the BackEnd folder from 'utils'
include("../../../../BackEnd/config/dbconfig.php");

$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
$supplierid = isset($_POST['supplier_id']) ? $_POST['supplier_id'] : 0;
$category_id = (isset($_POST['category_id']) && $_POST['category_id'] !== "") ? $_POST['category_id'] : null;

// --- 1. Get Company ID from Supplier ID ---
$company_stmt = mysqli_prepare($conn, 'SELECT company_id FROM companies WHERE supplier_id = ?');
$company_stmt->bind_param('i', $supplierid);
$company_stmt->execute();
$company_result = $company_stmt->get_result();
$company_row = $company_result->fetch_assoc();
$company_id = $company_row ? $company_row['company_id'] : 0;

$limit = 8; // Matching your products.php limit
$offset = ($page - 1) * $limit;
$like = "%$search%";

// --- 2. Prepare SQL Query ---
$sql = "SELECT p.* FROM products p 
        LEFT JOIN category c ON p.category_id = c.category_id 
        WHERE p.company_id = ? 
        AND p.status != 'unavailable'";

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

// --- 3. Generate HTML ---
$html = "";

if ($products_result && mysqli_num_rows($products_result) > 0) {
    while ($product = mysqli_fetch_assoc($products_result)) {
        
        // Image Path
        $img_path = "";
        if (!empty($product['image'])) {
            // This path works because the HTML is rendered inside 'shop/index.php'
            $img_path = '<img src="../uploads/products/' . $product['product_id'] . '_' . htmlspecialchars($product['image']) . '">';
        }

        // HTML Structure matching your design
        $html .= '
        <div class="product">
            <div class="product_image">
                ' . $img_path . '
            </div>

            <div class="card-body">
                <div class="product-info">
                    <span class="card_title">' . htmlspecialchars($product['product_name']) . '</span>
                    <span class="price">$' . number_format($product['price'], 2) . '</span>
                </div>
            </div>

            <a class="detail-link" 
               href="?supplier_id=' . $supplierid . '&page=productDetail&product_id=' . $product['product_id'] . '">
                <button class="detail-btn">VIEW DETAILS</button>
            </a>
        </div>';
    }
} else {
    $html = '<div class="col-12"><p class="text-center" style="width:100%; margin-top:20px;">No products found matching "' . htmlspecialchars($search) . '".</p></div>';
}

// --- 4. Return JSON ---
header('Content-Type: application/json');
echo json_encode([
    'html' => $html
]);
?>