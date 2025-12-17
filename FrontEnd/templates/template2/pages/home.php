<section class="page-content home-page">
    <div class="container">
        <div class="welcome-section text-center py-5">
            <h2 class="display-4 mb-4">Welcome to <?= htmlspecialchars($supplier['company_name']) ?></h2>
            <?php if (!empty($supplier['description'])): ?>
                <p class="lead"><?= nl2br(htmlspecialchars($supplier['description'])) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="featured-section mt-5">
            <h3 class="text-center mb-4">Featured Products</h3>
            <div class="row g-4">
                <?php
                $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? LIMIT 6");
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
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($product['product_name']) ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                                <p class="card-text price">$<?= number_format($product['price'], 2) ?></p>
                                <button class="btn btn-primary btn-add-cart" data-product-id="<?= $product['product_id'] ?>">Add to Cart</button>
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
                        <p class="text-center">No featured products available at the moment.</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

