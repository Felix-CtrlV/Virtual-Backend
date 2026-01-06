<?php
include("../../../../BackEnd/config/dbconfig.php");

$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$category_id = (isset($_GET['category_id']) && $_GET['category_id'] !== "") ? $_GET['category_id'] : null;
$supplierid = $_GET["supplier_id"];
$like = "%$search%";

if ($category_id) {
    $sql = "SELECT * FROM products WHERE supplier_id = ? AND category_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $supplierid, $category_id);
}

elseif ($search !== "") {    
    $sql = "SELECT p.* FROM products p 
            INNER JOIN category c ON p.category_id = c.category_id 
            WHERE p.supplier_id = ? AND c.category_name LIKE ?
            ORDER BY p.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $supplierid, $like);
}

else {
    $sql = "SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $supplierid);
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
            echo '<div class="col-12"><p class="text-center">No products found for this selection.</p></div>';
        }
    ?>