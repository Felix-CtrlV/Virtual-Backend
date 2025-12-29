<section class="page-content collection-page">
    <div class="container">
        <div class="collectionContainer"></div>        
        <h2 class="text-center mb-5">Latest Products</h2>
        <div class="row g-4">
            
            <?php
            if (!isset($_GET['category_id'])) { //category_id not found
                $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC"); //pop products that's same with supplier_id
                if ($products_stmt) {
                    mysqli_stmt_bind_param($products_stmt, "i", $supplier_id);
                    mysqli_stmt_execute($products_stmt);
                    $products_result = mysqli_stmt_get_result($products_stmt);
                } else {
                    $products_result = false;
                }
            } else { //url found
                $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? and category_id = ? ORDER BY created_at DESC");
                if ($products_stmt) {
                    mysqli_stmt_bind_param($products_stmt, "ii", $supplier_id, $_GET['category_id']);
                    mysqli_stmt_execute($products_stmt);
                    $products_result = mysqli_stmt_get_result($products_stmt);
                } else {
                    $products_result = false;
                }
            }

            if ($products_result && mysqli_num_rows($products_result) > 0) { //at least has one product
                while ($product = mysqli_fetch_assoc($products_result)) { //will stop if no rows
            ?>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="card-product image h-100">
                            <?php if (!empty($product['image'])): ?>
                                <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                                    class="card-img-top" alt="<?= htmlspecialchars($product['product_name']) ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h4 class="card_title"><?= htmlspecialchars($product['product_name']) ?></h4>
                                <p class="card-text price">$<?= number_format($product['price'], 2) ?></p>
                                <a href="product_detail.php?id=<?= $product['product_id'] ?>" class="btn-black-rounded">Shop Now ➔</a>
                            </div>
                        </div>
                    </div>
                <?php
                }
                if (isset($products_stmt)) {
                    mysqli_stmt_close($products_stmt);
                }
                    } else {
                ?>
                <div class="col-12">
                    <p class="text-center">No products available at the moment.</p>
                </div>
            <?php } ?>
        </div>
    </div>
</section>