<?php
// product_detail.php


// 1. Get Product ID from URL
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    echo "<div class='container'>Invalid Product ID.</div>";
    return; 
}


// 2. Fetch Product Data
$stmt = mysqli_prepare($conn, "
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN category c ON p.category_id = c.category_id 
    WHERE p.product_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo "<div class='container'>Product not found.</div>";
    return;
}

// 3. Fetch Variants (Colors/Sizes)
$stmt2 = mysqli_prepare($conn, "SELECT color, size FROM product_variant WHERE product_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $product_id);
mysqli_stmt_execute($stmt2);
$variants = mysqli_stmt_get_result($stmt2);

$colors = [];
$sizes = [];
while ($v = mysqli_fetch_assoc($variants)) {
    if (!in_array($v['color'], $colors))
        $colors[] = $v['color'];
    if (!in_array($v['size'], $sizes))
        $sizes[] = $v['size'];
}
?>

<div class="product-container mt-5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <div class="row">
        <div class="col-md-6">
            <div class="product-image-box shadow-sm">
                <img src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>"
                    class="img-fluid" alt="<?= htmlspecialchars($product['product_name']) ?>">
            </div>
        </div>

        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active"><?= htmlspecialchars($product['category_name']) ?></li>
                </ol>
            </nav>

            <h1 class="display-5 fw-bold"><?= htmlspecialchars($product['product_name']) ?></h1>
            <h3 class="text-primary mb-4">$<?= number_format($product['price'], 2) ?></h3>

  
   <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

    <div class="options">
        <div class="option-group">
            <label>COLOR</label>
            <div class="colors" style="display: flex; gap: 10px;">
                <?php foreach ($colors as $i => $color): ?>
                    <label style="cursor: pointer;">
                        <input type="radio" name="color" id="color<?= $i ?>" 
                               value="<?= htmlspecialchars($color) ?>" 
                               style="display: none;" class="color-radio" required>
                        <span style="display: block; width: 30px; height: 30px; border-radius: 50%; background-color: <?= htmlspecialchars($color) ?>; border: 2px solid #ddd; transition: transform 0.2s;" class="color-swatch"></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="option-group mt-3">
            <label>SIZE</label>
            <select name="size" class="form-select w-50" required>
                <option value="">Choose Size</option>
                <?php foreach ($sizes as $size): ?>
                    <option value="<?= htmlspecialchars($size) ?>">
                        <?= htmlspecialchars($size) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mt-4">
            <label class="form-label fw-bold">QUANTITY</label>
            <input type="number" name="qty" class="form-control w-25" value="1" min="1">
        </div>

        <div class="row g-2 mt-4">
            <div class="col-6">
                <button type="submit" name="action" value="add_to_cart" class="btn btn-dark btn-lg w-100 mb-3">
                    <i class="fas fa-shopping-bag me-1"></i>ADD TO CART
                </button>
            </div>
            <div class="col-6">
                <button type="submit" name="action" value="add_to_wishlist" class="btn btn-outline-dark btn-lg w-100 mb-3">
                    <i class="fas fa-heart me-1"></i> ADD TO WISHLIST
                </button>
                
                