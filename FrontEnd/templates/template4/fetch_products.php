<?php
// Ensure correct path to the central DB config
include '../../../BackEnd/config/dbconfig.php';

$offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
$supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
$limit = 6;

$query = "SELECT p.*, c.category_name FROM products p 
          LEFT JOIN category c ON p.category_id = c.category_id 
          WHERE p.supplier_id = ? 
          ORDER BY p.created_at DESC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $supplier_id, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    while ($product = mysqli_fetch_assoc($result)) {
?>
        <div class="col-lg-4 col-md-6 product-item" data-category="<?= htmlspecialchars($product['category_name'], ENT_QUOTES) ?>">
            <div class="modern-card">
                <div class="image-container">
                    <div class="category-label">
                        <h3>NIKE</h3> <span><?= htmlspecialchars($product['category_name']) ?></span>
                    </div>
                    <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>" alt="...">
                    <button class="zoom-btn">
                        <lord-icon src="https://cdn.lordicon.com/rrbmabsx.json" trigger="morph" colors="primary:#ffffff,secondary:#ffffff" style="width:24px;height:24px"></lord-icon>
                    </button>
                </div>
                <div class="card-footer-custom">
                    <div class="refresh-pill">
                        <lord-icon src="https://cdn.lordicon.com/bsdkzyjd.json" trigger="loop" delay="2000" style="width:22px;height:22px"></lord-icon>
                        <span class="refresh-text">PRICE</span>
                        <span class="price-tag">$<?= number_format($product['price'], 2) ?></span>
                    </div>
                    <button class="btn-add-cart" data-product-id="<?= $product['product_id'] ?>">
                        <lord-icon src="https://cdn.lordicon.com/qfijwmqj.json" trigger="loop" colors="primary:#ffffff,secondary:#ffffff" style="width:22px;height:22px"></lord-icon> Add to Cart
                    </button>
                </div>
            </div>
        </div>
<?php
    }
} else {
    echo "NO_MORE";
}
?>