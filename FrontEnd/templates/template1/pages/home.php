<?php
$supplier_id = (int) ($_GET['supplier_id'] ?? 1);

/* =============================
   FETCH HERO DATA
============================= */
$sql = "
    SELECT 
        s.company_name,
        sa.banner,
        sa.template_type,
        sa.description
    FROM suppliers s
    JOIN shop_assets sa ON sa.supplier_id = s.supplier_id
    WHERE s.supplier_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();
$shop_assets = $result->fetch_assoc();
$stmt->close();

$company_name = $shop_assets['company_name'] ?? '';
$description  = $shop_assets['description'] ?? '';

/* =============================
   SPLIT TITLE INTO 2 PARTS
============================= */
function splitHeroTitle(string $title): array
{
    $title = trim($title);
    $words = preg_split('/\s+/', $title);

    if (count($words) === 1) {
        $mid = ceil(strlen($words[0]) / 2);
        return [
            substr($words[0], 0, $mid),
            substr($words[0], $mid)
        ];
    }

    return [
        $words[0],
        implode(' ', array_slice($words, 1))
    ];
}

[$heroWord1, $heroWord2] = splitHeroTitle($company_name);
?>

<!-- =============================
     HERO SECTION
============================= -->
<section class="hero-section">

  <?php if ($shop_assets['template_type'] === 'video'): ?>
    <video class="hero-media" autoplay muted loop playsinline>
      <source src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['banner']) ?>" type="video/mp4">
    </video>
  <?php else: ?>
    <img class="hero-media"
         src="../uploads/shops/<?= $supplier_id ?>/<?= htmlspecialchars($shop_assets['banner']) ?>"
         alt="Hero Banner">
  <?php endif; ?>

  <div class="hero-overlay">
    <div class="hero-title">
      <span><?= htmlspecialchars($heroWord1) ?></span>
      <span><?= htmlspecialchars($heroWord2) ?></span>
    </div>

    <?php if (!empty($description)): ?>
      <p class="hero-tagline"><?= htmlspecialchars($description) ?></p>
    <?php endif; ?>
  </div>

</section>

<!-- =============================
     FEATURED CATEGORIES
============================= -->
<section class="featured-section">
  <div class="section-header">
    <h2>Our Featured Products</h2>
    <span class="section-line"></span>
  </div>

  <div class="categories-grid">
    <?php
    $category_stmt = mysqli_prepare(
        $conn,
        "SELECT c.category_id, c.category_name
         FROM category c
         WHERE c.supplier_id = ?
         LIMIT 4"
    );

    mysqli_stmt_bind_param($category_stmt, "i", $supplier_id);
    mysqli_stmt_execute($category_stmt);
    $category_result = mysqli_stmt_get_result($category_stmt);

    if ($category_result && mysqli_num_rows($category_result) > 0):
        $i = 1;
        while ($row = mysqli_fetch_assoc($category_result)):
            $imagePath = "../uploads/shops/{$supplier_id}/category_{$i}.jpg";
    ?>
        <div class="category-card">
          <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row['category_name']) ?>">
          <div class="category-overlay">
            <h3><?= htmlspecialchars($row['category_name']) ?></h3>
            <a href="?supplier_id=<?= $supplier_id ?>&category_id=<?= $row['category_id'] ?>&page=products"
               class="shop-btn">Shop Now</a>
          </div>
        </div>
    <?php
            $i++;
        endwhile;
    endif;

    mysqli_stmt_close($category_stmt);
    ?>
  </div>
</section>
