<section class="page-content products-page">
    <div class="container">
        <h2 class="text-center mb-5">Our Products</h2>

        <div class="category-filter mb-4 text-center">
            <button class="btn btn-category active" data-filter="all">All</button>

            <?php

            $cat_query = mysqli_query($conn, "SELECT * FROM category WHERE supplier_id = $supplier_id ORDER BY category_name ASC");

            while ($cat = mysqli_fetch_assoc($cat_query)) {
                $cname = htmlspecialchars($cat['category_name'], ENT_QUOTES);
                // This creates buttons strictly for Clothing and Footwear
                echo '<a href="?supplier_id=' . $supplier_id . '&page=products&category_id=' . $cat['category_id'] . '" class="btn btn-category" data-filter="' . htmlspecialchars($cname) . '">' . htmlspecialchars($cname) . '</a>';
            }
            ?>
        </div>

        <div class="row g-4 product-grid">
            <?php
            $limit = 6;

            if (isset($_GET["category_id"]) && $_GET["category_id"] !== '') {
                $category_id = $_GET["category_id"];
                $products_stmt = mysqli_prepare($conn, "select p.*, c.category_name from products p left join category c on p.category_id = c.category_id where p.category_id = ?");
                if ($products_stmt) {
                    mysqli_stmt_bind_param($products_stmt, "i", $category_id);
                    mysqli_stmt_execute($products_stmt);
                    $products_result = mysqli_stmt_get_result($products_stmt);
                }
            } else {
                $products_stmt = mysqli_prepare($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN category c ON p.category_id = c.category_id WHERE p.supplier_id = ? ORDER BY p.created_at");
                if ($products_stmt) {
                    mysqli_stmt_bind_param($products_stmt, "i", $supplier_id);
                    mysqli_stmt_execute($products_stmt);
                    $products_result = mysqli_stmt_get_result($products_stmt);
                }
            }

            if ($products_result && mysqli_num_rows($products_result) > 0) {
                while ($product = mysqli_fetch_assoc($products_result)) {
                    ?>
                    <div class="col-lg-4 col-md-6 product-item"
                        data-category="<?= htmlspecialchars($product['category_name'], ENT_QUOTES) ?>">
                        <div class="modern-card">
                            <div class="image-container">
                                <div class="category-label">
                                    <h3>NIKE</h3> <span><?= htmlspecialchars($product['category_name']) ?></span>
                                </div>
                                <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                                    alt="<?= htmlspecialchars($product['product_name']) ?>">
                                <button class="zoom-btn">
                                    <lord-icon src="https://cdn.lordicon.com/rrbmabsx.json" trigger="morph" state="morph-open"
                                        colors="primary:#ffffff,secondary:#ffffff" style="width:22px;height:22px">
                                    </lord-icon>
                                </button>
                            </div>
                            <div class="card-footer-custom">
                                <div class="refresh-pill">
                                    <lord-icon src="https://cdn.lordicon.com/bsdkzyjd.json" trigger="loop" state="loop-spin"
                                        colors="primary:#121331,secondary:#ff3c00" style="width:22px;height:22px">
                                    </lord-icon>
                                    <span class="refresh-text">PRICE</span>
                                    <span class="price-tag">$<?= number_format($product['price'], 2) ?></span>
                                </div>
                                <button class="btn-add-cart" data-product-id="<?= $product['product_id'] ?>">
                                    <lord-icon src="https://cdn.lordicon.com/qfijwmqj.json" trigger="loop"
                                        colors="primary:#ffffff,secondary:#ffffff" style="width:22px;height:22px"></lord-icon>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                mysqli_stmt_close($products_stmt);
            }
            ?>
        </div>

        <div class="text-center mt-5">
            <button id="load-more-btn" class="btn btn-dark px-5 py-3" data-offset="6"
                data-supplier="<?= (int) $supplier_id ?>">LOAD MORE PRODUCTS</button>
        </div>
    </div>
</section>

<script src="../script.js"></script>
</div>
</section>