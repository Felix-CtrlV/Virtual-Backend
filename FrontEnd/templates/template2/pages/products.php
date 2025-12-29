<section class="page-content products-page">
    <div class="container">
        <h2 class="text-center mb-5">Our Products</h2>

        <div class="row g-4">
            <?php
            $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC");
            if ($products_stmt) {
                mysqli_stmt_bind_param($products_stmt, "i", $supplier_id);
                mysqli_stmt_execute($products_stmt);
                $products_result = mysqli_stmt_get_result($products_stmt);
            } else {
                $products_result = false;
            }

            if ($products_result && mysqli_num_rows($products_result) > 0) {
                while ($product = mysqli_fetch_assoc($products_result)) {
                    ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card product-card h-100">
                            <?php if (!empty($product['image'])): ?>
                                <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                                    class="card-img-top" alt="<?= htmlspecialchars($product['product_name']) ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                                <?php if (!empty($product['description'])): ?>
                                    <p class="card-text"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                                <?php endif; ?>
                                <p class="card-text price">$<?= number_format($product['price'], 2) ?></p>
                                <button class="btn btn-primary btn-add-cart" data-product-id="<?= $product['product_id'] ?>">Add
                                    to Cart</button>
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