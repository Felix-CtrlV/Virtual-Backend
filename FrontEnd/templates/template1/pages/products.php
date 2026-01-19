<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<?php
// --- 1. PAGINATION CALCULATIONS ---
$limit = 8; // 12 products per page
$current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $limit;

// --- 2. GET TOTAL COUNT (To know how many pages exist) ---
// Change the first condition
if (!isset($_GET['category_id'])) {
    $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM products WHERE supplier_id = ? AND status != 'unavailable'");
    mysqli_stmt_bind_param($count_stmt, "i", $supplier_id);
} else {
    $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM products WHERE supplier_id = ? AND category_id = ? AND status != 'unavailable'");
    mysqli_stmt_bind_param($count_stmt, "ii", $supplier_id, $_GET['category_id']);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_rows = mysqli_fetch_array($count_result)[0];
$total_pages = ceil($total_rows / $limit);
mysqli_stmt_close($count_stmt);
?>

<section class="products-page pt-5">
  <div class="container">
    <h2 class="text-center mb-5">Our Products</h2>

    <div class="product_list_grid">
      <?php
      // --- 3. FETCH PRODUCTS WITH LIMIT & OFFSET ---
      if (!isset($_GET['category_id'])) {
    $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND status != 'unavailable' ORDER BY created_at DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($products_stmt, "iii", $supplier_id, $limit, $offset);
} else {
    // ADD THE STATUS CHECK HERE TOO:
    $products_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE supplier_id = ? AND category_id = ? AND status != 'unavailable' ORDER BY created_at DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($products_stmt, "iiii", $supplier_id, $_GET['category_id'], $limit, $offset);
}
      if ($products_stmt) {
        mysqli_stmt_execute($products_stmt);
        $products_result = mysqli_stmt_get_result($products_stmt);

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
        } else {
          echo '<div class="col-12"><p class="text-center">No products available at the moment.</p></div>';
        }
        mysqli_stmt_close($products_stmt);
      }
      ?>
    </div>

    <?php if ($total_pages > 1): ?>
      <div class="pagination-container" style="margin: 50px 0 80px 0; text-align: center;">
        <?php 
          $url_params = "supplier_id=$supplier_id&page=products";
          if (isset($_GET['category_id'])) $url_params .= "&category_id=" . $_GET['category_id'];
        ?>

        <?php if ($current_page > 1): ?>
          <a href="?<?= $url_params ?>&p=<?= $current_page - 1 ?>" class="pag-btn">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?<?= $url_params ?>&p=<?= $i ?>" class="pag-btn <?= ($i == $current_page) ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
          <a href="?<?= $url_params ?>&p=<?= $current_page + 1 ?>" class="pag-btn">Next &raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<style>
  .pag-btn { padding: 8px 16px; border: 1px solid #ccc; text-decoration: none; color: #333; border-radius: 4px; }
  .pag-btn.active { background-color: #333; color: #fff; border-color: #333; }
  .pag-btn:hover { background-color: #f4f4f4; }
</style>