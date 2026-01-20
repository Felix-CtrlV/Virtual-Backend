<?php
include("../../../../BackEnd/config/dbconfig.php");


$search = $_POST['search'];
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$like = "%$search%";
$supplierid = $_GET["supplier_id"];

if ($category_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND category_id = ? AND LOWER(product_name) LIKE LOWER(?) ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "iis", $supplierid, $category_id, $like);
} else {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND LOWER(product_name) LIKE LOWER(?) ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmt, "is", $supplierid, $like);
}


mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);


switch ($supplierid) {
    case 10: $theme_class = "luxury-theme"; $brand_text = "ROLEX"; break; 
    
}


if ($products_result && mysqli_num_rows($products_result) > 0) {
    while ($row = mysqli_fetch_assoc($products_result)) { ?>
        <div class="col-md-3 col-sm-6 col-12 mb-4">
            <div class="card-product <?= $theme_class ?> h-100 shadow-sm border-0">
                <div class="card-header-brand">
                    <!--<span class="brand-label"><?= $brand_text ?></span>-->
                </div>
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
} else {
    $error_msg = ($supplierid == 10) ? "We could not find the luxury watch..." : "No items found in this collection.";
    echo '<div class="col-12 text-center py-4"><p class="text-muted">' . $error_msg . '</p></div>';
}
?>