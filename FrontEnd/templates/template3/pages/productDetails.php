<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Product Detail – Glass Chair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #cecdcdff;
            --card: #ffffff;
            --text: #2b2b2b;
            --muted: #8a8a8a;
            --accent: #1f1f1f;
            --border: #e6e6e6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .page {
            max-width: 100%;
            padding: 60px 40px;
            display: flex;
            justify-content: center;
        }

        /* Image */
        .gallery {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border-radius: 4px;
            padding: 50px;
        }

        .gallery img {
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }

        /* Product Info */
        .product {
            width: 70vw;
            max-width: 900px;
            background: var(--card);
            padding: 40px;
            border-radius: 4px;
            border: 1px solid var(--border);

        }


        .product_category {
            color: var(--muted);
            margin-bottom: 12px;
        }

        .product h1 {
            font-size: 28px;
            font-weight: 500;
            margin: 0 0 12px;
            letter-spacing: 0.5px;
        }

        .price {
            font-size: 22px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .desc {
            font-size: 14px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 28px;
        }

        .options {
            margin-bottom: 24px;
        }

        .colors {
            display: flex;
            gap: 10px;
            margin: 10px 0 20px;
        }

        .color {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 1px solid #ccc;
            cursor: pointer;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            opacity: 1;
            height: auto;
        }

        .actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .actions input {
            width: 70px;
        }

        .btn {
            flex: 1;
            padding: 14px 16px;
            background: var(--accent);
            color: #fff;
            border: none;
            font-size: 13px;
            letter-spacing: 1px;
            cursor: pointer;
            border-radius: 2px;
            transition: opacity 0.2s ease;
        }

        .btn:hover {
            opacity: 0.85;
        }

        @media (max-width: 900px) {
            .page {
                grid-template-columns: 1fr;
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <?php if (!isset($_GET['product_id'])) {
            echo "<p>Product not found.</p>";
            exit;
        }
        $product_id = (int) $_GET['product_id'];
        $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id = ? AND supplier_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $product_id, $supplier_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);
        if (!$product) {
            echo "<p>Product not found.</p>";
            exit;
        } ?>
        <div class="gallery">
            <div class="product_image"> <?php if (!empty($product['image'])): ?> <img
                        src="../uploads/products/<?= $product['product_id'] ?>_<?= htmlspecialchars($product['image']) ?>">
                <?php endif; ?> </div>
            <div class="product">
                <div class="product_category"><?= htmlspecialchars($product['product_name']) ?></div> <span
                    class="price">$<?= number_format($product['price'], 2) ?></span>
                <p class="desc"> According to a new study, some 90 million people around the world used online dating
                    apps like Tinder and Momo last month. And it turns out that nearly two-thirds of those swiped were
                    male. </p>
                <?php
                $sizes = [];

                $size_stmt = mysqli_prepare(
                    $conn,
                    "SELECT DISTINCT size 
FROM product_variant 
WHERE product_id = ?
"
                );
                mysqli_stmt_bind_param($size_stmt, "i", $product_id);
                mysqli_stmt_execute($size_stmt);
                $size_result = mysqli_stmt_get_result($size_stmt);

                while ($row = mysqli_fetch_assoc($size_result)) {
                    $sizes[] = $row['size'];
                }

                mysqli_stmt_close($size_stmt);
                ?>

                <?php $colors = [];
                $product_var_stmt = mysqli_prepare($conn, "SELECT DISTINCT color 
FROM product_variant 
WHERE product_id = ?
");
                mysqli_stmt_bind_param($product_var_stmt, "i", $product_id);
                mysqli_stmt_execute($product_var_stmt);
                $product_var_result = mysqli_stmt_get_result($product_var_stmt);
                while ($row = mysqli_fetch_assoc($product_var_result)) {
                    $colors[] = $row['color'];
                }
                mysqli_stmt_close($product_var_stmt); ?>
                <div class="options"> <label>Color</label>
                    <div class="colors"> <?php if (count($colors) > 0): ?>     <?php foreach ($colors as $color): ?>
                                <div class="color" title="<?= htmlspecialchars($color) ?>"
                                    style="background-color: <?= htmlspecialchars($color) ?>;"> </div> <?php endforeach; ?>
                        <?php else: ?> <span style="color:#999;font-size:12px;">No color variants</span> <?php endif; ?>
                    </div>
                </div>
                <div class="options">
                    <label for="size">Size</label>

                    <select id="size" name="size" style="
                width: 100%;
                padding: 10px;
                margin-top: 8px;
                border: 1px solid var(--border);
                border-radius: 4px;
                font-family: inherit;
            ">
                        <?php if (!empty($sizes)): ?>
                            <option value="">Select size</option>
                            <?php foreach ($sizes as $size): ?>
                                <option value="<?= htmlspecialchars($size) ?>">
                                    <?= htmlspecialchars($size) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option disabled>No sizes available</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="actions"> <input type="number" value="1" min="1"> <button class="btn">ADD TO BAG</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>