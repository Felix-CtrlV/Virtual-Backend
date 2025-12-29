<section class="banner">
    <div class="banner-content">
        <h1>Elevate Your Style</h1>
        <p>Premium streetwear made for everyday confidence</p>
    </div>
</section>

<section class="featured-section">
    <div class="section-header">
        <h2>Featured Products</h2>
        <span class="section-line"></span>
    </div>

    <div class="categories-grid">
        <?php
        $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? LIMIT 6");
        if ($products_stmt) {
            mysqli_stmt_bind_param($products_stmt, "i", $supplier_id);
            mysqli_stmt_execute($products_stmt);
            $products_result = mysqli_stmt_get_result($products_stmt);
        } else {
            $products_result = false;
        }

        $shown_category = [];
        if ($products_result && mysqli_num_rows($products_result) > 0) {
            while ($product = mysqli_fetch_assoc($products_result)) {
                $categoryquery = "select * from category where category_id = $product[category_id]";
                $category_result = mysqli_query($conn, $categoryquery);
                $category_row = mysqli_fetch_assoc($category_result);
                if (in_array($category_row['category_name'], $shown_category)) {
                    continue;
                }

                $shown_category[] = $category_row['category_name'];
                ?>
                <div class="category-card">
                    <?php if (!empty($product['image'])): ?>
                        <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                            class="card-img-top" alt="<?= htmlspecialchars($category_row['category_name']) ?>">
                        <a href="?supplier_id=<?= $supplier['supplier_id'] ?>&category_id=<?= $category_row['category_id'] ?>&page=products"
                            class="view"><?= $category_row['category_name'] ?></a>
                    <?php endif; ?>
                    <div class="category-overlay">
                        <h3><?= htmlspecialchars($category_row['category_name']) ?></h3>
                        
                        <a href="?supplier_id=<?= $supplier['supplier_id'] ?>&category_id=<?= $category_row['category_id'] ?>&page=products"
                            class="view"><?= $category_row['category_name'] ?></a>

                        <a href="#" class="shop-btn">Shop Now</a>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p class="text-center">No featured categories available.</p>';
        }

        mysqli_stmt_close($products_stmt);
        ?>
    </div>
</section>