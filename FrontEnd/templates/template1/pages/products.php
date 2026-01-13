<section class="products-page">

  <div class="container">

    <h2 class="text-center mb-5">Our Products</h2>



    <div class="product_list_grid">

      <?php



      if (!isset($_GET['category_id'])) {

        $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC");

        if ($products_stmt) {

          mysqli_stmt_bind_param($products_stmt, "i", $supplier_id);

          mysqli_stmt_execute($products_stmt);

          $products_result = mysqli_stmt_get_result($products_stmt);

        } else {

          $products_result = false;

        }

      } else {

        $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? and category_id = ? ORDER BY created_at DESC");

        if ($products_stmt) {

          mysqli_stmt_bind_param($products_stmt, "ii", $supplier_id, $_GET['category_id']);

          mysqli_stmt_execute($products_stmt);

          $products_result = mysqli_stmt_get_result($products_stmt);

        } else {

          $products_result = false;

        }

      }



      if ($products_result && mysqli_num_rows($products_result) > 0) {

        while ($product = mysqli_fetch_assoc($products_result)) {



          ?>

          <div class="product">

            <div class="product_image">

              <?php if (!empty($product['image'])): ?>

                <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>">

              <?php endif; ?>

            </div>



            <div class="card-body">

              <div class="product-info">

                <span class="card_title"><?= htmlspecialchars($product['product_name']) ?></span>

                <span class="price">$<?= number_format($product['price'], 2) ?></span>

              </div>



              <button class="add-to-cart" data-product-id="<?= $product['product_id'] ?>"
                data-supplier-id="<?= $supplier_id ?>" title="Add to cart">+</button>

            </div>

            <a class="detail-link"
              href="?supplier_id=<?= $supplier_id ?>&page=productDetail&product_id=<?= $product['product_id'] ?>">

              <button class="detail-btn">VIEW DETAILS</button>

            </a>





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