<?php
include("../../../../BackEnd/config/dbconfig.php");


$search = isset($_POST['search']) ? $_POST['search'] : '';
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$supplierid = isset($_GET["supplier_id"]) ? intval($_GET["supplier_id"]) : 10;
$like = "%$search%";

// --- PAGINATION LOGIC START ---
$limit =4;  /* ko pya chin ta lout logic pya ng dl*/
$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;


if ($category_id) {
    $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM products WHERE supplier_id = ? AND category_id = ? AND LOWER(product_name) LIKE LOWER(?)");
    mysqli_stmt_bind_param($count_stmt, "iis", $supplierid, $category_id, $like);
} else {
    $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM products WHERE supplier_id = ? AND LOWER(product_name) LIKE LOWER(?)");
    mysqli_stmt_bind_param($count_stmt, "is", $supplierid, $like);
}

mysqli_stmt_execute($count_stmt);
mysqli_stmt_bind_result($count_stmt, $total_records);
mysqli_stmt_fetch($count_stmt);
mysqli_stmt_close($count_stmt);

$total_pages = ceil($total_records / $limit); 




if ($category_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND category_id = ? AND LOWER(product_name) LIKE LOWER(?) ORDER BY created_at DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, "iisii", $supplierid, $category_id, $like, $limit, $offset);
} else {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND LOWER(product_name) LIKE LOWER(?) ORDER BY created_at ASC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, "isii", $supplierid, $like, $limit, $offset);
}

mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);


switch ($supplierid) {
    case 10: $theme_class = "luxury-theme"; $brand_text = "ROLEX"; break;
    default: $theme_class = "standard-theme"; $brand_text = "SHOP"; break;
}


if ($products_result && mysqli_num_rows($products_result) > 0) {
    while ($row = mysqli_fetch_assoc($products_result)) { ?>
        <div class="col-md-3 col-sm-6 col-12 mb-4">
            <div class="card-product <?= $theme_class ?> h-100 shadow-sm border-0">
                <?php if (!empty($row['image'])): ?>
                    <div class="product-image-wrapper">
                        <img src="../uploads/products/<?= $row['product_id'] ?>_<?= htmlspecialchars($row['image']) ?>"
                             class="card-img-top" alt="<?= htmlspecialchars($row['product_name']) ?>">
                    </div>
                <?php endif; ?>
                <div class="card-body text-center">
                    <h4 class="card_title"><?= htmlspecialchars($row['product_name']) ?></h4>
                    <div class="product-price-container">
                        <p class="card-text price mb-3">PRICE: $<?= number_format($row['price'], 2) ?></p>
                        <a href="?supplier_id=<?= $supplierid ?>&id=<?= $row['product_id'] ?>&page=product_details" class="btn-order-now">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    <?php }

   
    if ($total_pages > 1) {
        echo '<div class="col-12 mt-4 d-flex justify-content-center">';
        echo '<nav><ul class="pagination">';
        
        // Previous Button
        if ($page > 1) {
            echo '<li class="page-item"><a class="page-link pagination-link" href="javascript:void(0)" data-page="'.($page-1).'">Previous</a></li>';
        }

        // Page Numbers
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo '<li class="page-item '.$active.'"><a class="page-link pagination-link" href="javascript:void(0)" data-page="'.$i.'">'.$i.'</a></li>';
        }

        // Next Button
        if ($page < $total_pages) {
            echo '<li class="page-item"><a class="page-link pagination-link" href="javascript:void(0)" data-page="'.($page+1).'">Next</a></li>';
        }

        echo '</ul></nav>';
        echo '</div>';
    }

} else {
    $error_msg = ($supplierid == 10) ? "We could not find the luxury watch..." : "No items found in this collection.";
    echo '<div class="col-12 text-center py-4"><p class="text-muted">' . $error_msg . '</p></div>';
}
?>

<style>
    .pagination-link {
        padding: 15px 20px; /* Box size ko hane tar */
        font-size: 15px;    /* Sar lone size ko kyee tar */
    }
</style>