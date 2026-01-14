<section class="hero-section">
  <?php if ($shop_assets['template_type'] === 'video'): ?>
    <video class="hero-media" autoplay muted loop playsinline
      src="../uploads/shops/<?= $supplier_id ?>/<?= $shop_assets['banner'] ?>">
    </video>
  <?php else: ?>
    <img class="hero-media" src="../uploads/shops/<?= $supplier_id ?>/<?= $shop_assets['banner'] ?>"
      alt="Hero Banner">
  <?php endif; ?>

  <div class="hero-overlay">
    <div class="hero-title">
      <span>Heavy</span>
      <span>Lodes</span>
    </div>

    <p class="hero-tagline">Built heavy. Worn louder.</p>
  </div>
</section>

<section class="featured-section">
    <div class="section-header">
        <h2>Featured Products</h2>
        <span class="section-line"></span>
    </div>

    <div class="categories-grid">
        <?php
/*$category_stmt = mysqli_prepare(
    $conn,
    "SELECT c.category_id, c.category_name, 
            MIN(p.image) AS image, 
            MIN(p.product_id) AS product_id
     FROM category c
     JOIN products p ON p.category_id = c.category_id
     WHERE c.supplier_id = ?
     GROUP BY c.category_id
     LIMIT 4"
);*/
$category_stmt = mysqli_prepare(
    $conn,
    "SELECT c.category_id, c.category_name, p.image, p.product_id
     FROM category c
     JOIN products p 
       ON p.product_id = (
           SELECT p2.product_id
           FROM products p2
           WHERE p2.category_id = c.category_id
           ORDER BY p2.created_at DESC
           LIMIT 1
       )
     WHERE c.supplier_id = ?
     LIMIT 4"
);


mysqli_stmt_bind_param($category_stmt, "i", $supplier_id);
mysqli_stmt_execute($category_stmt);
$category_result = mysqli_stmt_get_result($category_stmt);

if ($category_result && mysqli_num_rows($category_result) > 0):
    while ($row = mysqli_fetch_assoc($category_result)):
?>
        <div class="category-card">
            <img src="../uploads/products/<?= $row['product_id'] ?>_<?= htmlspecialchars($row['image']) ?>"
                 alt="<?= htmlspecialchars($row['category_name']) ?>">

            <div class="category-overlay">
                <h3><?= htmlspecialchars($row['category_name']) ?></h3>

                <a href="?supplier_id=<?= $supplier_id ?>&category_id=<?= $row['category_id'] ?>&page=products"
                   class="shop-btn">Shop Now</a>
            </div>
        </div>
<?php
    endwhile;
else:
    echo "<p>No categories available.</p>";
endif;

mysqli_stmt_close($category_stmt);
?>

    </div>
</section>