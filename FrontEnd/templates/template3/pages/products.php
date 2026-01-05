<section class="page-content product-page">
    <div class="container">
        <h2 class="text-center">Trendy Stocks</h2>
        <div class="row g-4">
        <div class="welcome-section text-center py-1">
            <?php if (!empty($supplier['description'])): ?>                
            <?php endif; ?>
        </div>
        
        <div class="featured-section mt-9">
            <div class="row g-4">
                <?php
                $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? LIMIT 6");
                if ($products_stmt) {
                    mysqli_stmt_bind_param($products_stmt, "i", $supplier_id);
                    mysqli_stmt_execute($products_stmt);
                    $products_result = mysqli_stmt_get_result($products_stmt);
                } else {
                    $products_result = false; //query fail
                }
                
                $shown_category = [];
                if ($products_result && mysqli_num_rows($products_result) > 0) {
                    while ($product = mysqli_fetch_assoc($products_result)) {
                        $categoryquery = "select * from category where category_id = $product[category_id]";
                        $category_result = mysqli_query($conn, $categoryquery);
                        $category_row = mysqli_fetch_assoc($category_result);
                        if (in_array($category_row['category_name'], $shown_category)) { //one time show category
                        continue; //if shown skip continue
                        }

                    $shown_category[] = $category_row['category_name'];
                ?>
                    <div class="col-md-4 col-sm-6">           
                        <div class="card product-card h-100">
                            <?php if (!empty($product['image'])): ?> 
                                <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($category_row['category_name']) ?>">
                                     <h3 class="category_name"><?= $category_row['category_name'] ?></h3>                
                                     <a href="?supplier_id=<?= $supplier['supplier_id']?>&category_id=<?= $category_row['category_id'] ?>&page=collection" class="btn btn-primary btn-view" style="background-color: #000000;">View</a>
                                     
                                <?php endif; ?>
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
    
         
    