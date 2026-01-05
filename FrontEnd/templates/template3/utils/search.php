<?php

include("../../../../BackEnd/config/dbconfig.php");

$search = $_POST['search'];
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$like = "%$search%";
$supplierid = $_GET["supplier_id"];

if ($category_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND category_id = ? AND product_name LIKE ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "iis", $supplierid, $category_id, $like);
} else {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND product_name LIKE ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "is", $supplierid, $like);
}

mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);

if ($products_result && mysqli_num_rows($products_result) > 0) {
    while ($row = mysqli_fetch_assoc($products_result)) { ?>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="card-product image h-100">
                <?php if (!empty($row['image'])): ?>
                    <img src="../uploads/products/<?= $row['product_id'] ?>_<?= htmlspecialchars($row['image']) ?>"
                        class="card-img-top" alt="<?= htmlspecialchars($row['product_name']) ?>">
                <?php endif; ?>
                <div class="card-body">
                    <h4 class="card_title"><?= htmlspecialchars($row['product_name']) ?></h4>
                    <p class="card-text price">$<?= number_format($row['price'], 2) ?></p>
                    
                   <a href="?supplier_id=<?= $supplierid ?>&page=product_details&id=<?= $row['product_id'] ?>" class="btn-black-rounded">Shop Now ➔</a>
                </div>
            </div>
        </div>
    <?php }
} else {
    echo '<div class="col-12"><p class="text-center">No products available at the moment.</p></div>';
}


?>