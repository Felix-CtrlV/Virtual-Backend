<?php
$current_page = 'home.php';
?>

<section class="hero" style="margin: 0; padding: 0; min-width: 98.95vw;">
        <?php if ($shop_assets['template_type'] == 'video'): ?>
            <video class="hero-media" autoplay muted loop playsinline src="../uploads/shops/<?= $supplier_id?>/<?= $banner1?>"></video>
        <?php else: ?>
            <img src="../uploads/shops/<?= $supplier_id ?>/<?= $banner1 ?>" alt="Hero Banner" class="hero-media">
        <?php endif; ?>
</section>
<style>
    .hero {
        width: 100%;
        margin-top: 30px; 
        margin-bottom: 20px;
        overflow: hidden;
        position: relative;
    }

    .hero-inner {
        /* min-width: 100vw; */
        width: 100%;
        /* aspect-ratio: 16 / 9; */
        max-height: 100vh;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hero-media {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scale(1.05); 
        display: block;
    }
</style>

    <section class="page-content product-page">
        <div class="container">
            <h2 class="text-center">Trendy Stocks</h2>
            <div class="row g-4">
                <div class="featured-section mt-9">
                    <div class="row g-4">
                        <?php
                        $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE company_id = ? LIMIT 6");
                        if ($products_stmt) {
                            mysqli_stmt_bind_param($products_stmt, "i", $company_id);
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

                                <div class="col-md-4 col-sm-6">
                                    <div class="card product-card h-100">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                                                class="card-img-top" alt="<?= htmlspecialchars($category_row['category_name']) ?>">
                                            <h3 class="category_name"><?= $category_row['category_name'] ?></h3>
                                            <a href="?supplier_id=<?= $supplier['supplier_id'] ?>&category_id=<?= $category_row['category_id'] ?>&page=product"
                                                class="btn-view">View</a>

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

</body>

</html>