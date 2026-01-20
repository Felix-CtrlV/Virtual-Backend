<?php
// products.php - Modern "Carved" Dark Aesthetic with Search & Radio-Style Color Variants

// Ensure access context
if (!isset($supplier_id)) {
    die("Access Denied");
}

// Check for category filter and search query
$category_filter = isset($_GET['category_id']) ? $_GET['category_id'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// HELPER: Map Color Names to Hex Codes
function getColorHex($colorName)
{
    $c = strtolower(trim($colorName));
    $map = [
        'black' => '#212121',
        'white' => '#f5f5f5',
        'red' => '#D32F2F',
        'blue' => '#1976D2',
        'green' => '#388E3C',
        'yellow' => '#FBC02D',
        'navy' => '#1A237E',
        'grey' => '#9E9E9E',
        'gray' => '#9E9E9E',
        'gold' => '#D4AF37',
        'orange' => '#F57C00',
        'purple' => '#7B1FA2',
        'brown' => '#5D4037',
        'beige' => '#F5F5DC'
    ];
    return isset($map[$c]) ? $map[$c] : $colorName;
}
?>

<style>
    /* ============================================
       SHARED VARIABLES & LAYOUT
       ============================================ */
    :root {
        --bg-color: #0a0a0a;
        --card-bg: #141414;
        --text-main: #ffffff;
        --accent: #D4AF37;
        --border-color: #2a2a2a;
        --font-display: 'Helvetica Neue', 'Arial Black', sans-serif;
        --font-body: 'Helvetica', sans-serif;
        --transition-smooth: cubic-bezier(0.16, 1, 0.3, 1);
    }

    .products-page {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: var(--font-body);
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* Hero Section */
    .products-hero {
        padding: 120px 0 60px;
        text-align: center;
    }

    .products-title {
        font-family: var(--font-display);
        font-size: clamp(3rem, 10vw, 8rem);
        text-transform: uppercase;
        font-weight: 900;
        letter-spacing: -0.04em;
        line-height: 0.9;
        margin-bottom: 40px;
        opacity: 0;
        animation: fadeUp 1s var(--transition-smooth) forwards;
    }

    @keyframes fadeUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Search Bar */
    .search-wrapper {
        margin: 0 auto 30px;
        max-width: 500px;
        position: relative;
        opacity: 0;
        animation: fadeUp 1s var(--transition-smooth) 0.1s forwards;
        padding: 0 20px;
    }

    .search-input {
        width: 100%;
        background: transparent;
        border: 1px solid #333;
        border-radius: 50px;
        padding: 15px 50px 15px 25px;
        color: #fff;
        font-family: var(--font-body);
        font-size: 1rem;
        outline: none;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        border-color: var(--accent);
        background: rgba(255, 255, 255, 0.05);
        box-shadow: 0 0 15px rgba(212, 175, 55, 0.1);
    }

    .search-btn {
        position: absolute;
        right: 35px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 1.2rem;
        transition: color 0.3s ease;
    }

    .search-btn:hover {
        color: var(--accent);
    }

    /* Filter Pills */
    .filter-container {
        display: flex;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 80px;
        opacity: 0;
        animation: fadeUp 1s var(--transition-smooth) 0.2s forwards;
    }

    .category-btn {
        background: transparent;
        border: 1px solid #333;
        color: #666;
        padding: 12px 30px;
        border-radius: 50px;
        text-transform: uppercase;
        font-weight: 700;
        font-size: 0.85rem;
        letter-spacing: 1px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .category-btn:hover,
    .category-btn.active {
        background: #fff;
        color: #000;
        border-color: #fff;
        transform: translateY(-2px);
    }

    /* ============================================
       PRODUCT CARD STYLE
       ============================================ */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 40px;
        padding: 0 5%;
        margin-bottom: 100px;
    }

    .product-card-wrapper {
        perspective: 1000px;
    }

    .product-card {
        background-color: var(--card-bg);
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        transition: transform 0.1s ease-out;
        transform-style: preserve-3d;
        border: 1px solid var(--border-color);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    /* Glare Effect */
    .product-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(125deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 60%);
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
        z-index: 5;
    }

    .product-card:hover::after {
        opacity: 1;
    }

    .product-card:hover {
        border-color: #444;
    }

    /* Image */
    .card-image-box {
        position: relative;
        width: 100%;
        aspect-ratio: 1 / 1;
        background: #1a1a1a;
        overflow: hidden;
        transform: translateZ(20px);
    }

    .product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s var(--transition-smooth);
    }

    .product-card:hover .product-img {
        transform: scale(1.08);
    }

    /* Info */
    .card-info {
        padding: 25px;
        transform: translateZ(30px);
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        justify-content: space-between;
    }

    .p-category {
        color: var(--accent);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .p-title {
        color: #fff;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    .p-footer {
        display: flex;
        justify-content: space-between;
        align-items: end;
        margin-top: auto;
    }

    .p-price {
        font-family: var(--font-display);
        font-size: 1.25rem;
        color: #fff;
    }

    /* Radio Color Options */
    .color-options {
        display: flex;
        gap: 8px;
    }

    .color-dot-radio {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        cursor: pointer;
        background-clip: content-box;
        padding: 2px;
        border: 1px solid #444;
        transition: all 0.2s ease;
        position: relative;
    }

    .color-dot-radio:hover {
        border-color: #fff;
        transform: scale(1.1);
        box-shadow: 0 0 5px rgba(255, 255, 255, 0.2);
    }

    .card-link {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 10;
    }

    /* Load More */
    .load-more-container {
        text-align: center;
        margin-top: 60px;
    }

    .magnet-btn-dark {
        display: inline-block;
        padding: 20px 50px;
        background: #1a1a1a;
        color: #fff;
        border: 1px solid #333;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: bold;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .magnet-btn-dark:hover {
        background: #fff;
        color: #000;
        border-color: #fff;
    }

    .magnet-btn-dark:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        hover: none;
    }
</style>

<section class="page-content products-page">

    <div class="products-hero">
        <h1 class="products-title">The Collection</h1>

        <div class="search-wrapper">
            <form action="" method="GET" class="search-form">
                <input type="hidden" name="supplier_id" value="<?= $supplier_id ?>">
                <input type="hidden" name="page" value="products">
                <?php if ($category_filter !== 'all'): ?>
                    <input type="hidden" name="category_id" value="<?= htmlspecialchars($category_filter) ?>">
                <?php endif; ?>

                <input type="text" name="search" class="search-input"
                    placeholder="Search products..."
                    value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="search-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </form>
        </div>

        <div class="filter-container">
            <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="category-btn <?= $category_filter == 'all' ? 'active' : '' ?>">All Products</a>
            <?php
            $cat_query = mysqli_query($conn, "SELECT * FROM category WHERE supplier_id = $supplier_id ORDER BY category_name ASC");
            while ($cat = mysqli_fetch_assoc($cat_query)) {
                $cId = $cat['category_id'];
                $cName = htmlspecialchars($cat['category_name']);
                $isActive = ($category_filter == $cId) ? 'active' : '';
                // Append search query to filters so we don't lose the search term when changing categories
                $searchPart = $search_query ? "&search=" . urlencode($search_query) : "";
                echo "<a href='?supplier_id=$supplier_id&page=products&category_id=$cId$searchPart' class='category-btn $isActive'>$cName</a>";
            }
            ?>
        </div>
    </div>

    <div class="row product-grid" id="product-grid">
        <?php
        // Prepare SQL with Dynamic Parameters
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN category c ON p.category_id = c.category_id 
                WHERE p.supplier_id = ? AND status = 'available'";

        $types = "i";
        $params = [$supplier_id];

        // Apply Category Filter
        if ($category_filter !== 'all') {
            $sql .= " AND p.category_id = ?";
            $types .= "i";
            $params[] = $category_filter;
        }

        // Apply Search Filter
        if (!empty($search_query)) {
            $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
            $types .= "ss";
            $searchTerm = "%" . $search_query . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT 9";

        $stmt = mysqli_prepare($conn, $sql);

        // Dynamic binding
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
                $imgUrl = "../uploads/products/" . $row['product_id'] . "_" . $row['image'];
                $price = number_format($row['price'], 2);
                $name = htmlspecialchars($row['product_name']);
                $catName = htmlspecialchars($row['category_name'] ?? 'Exclusive');
                $detailLink = "?supplier_id=$supplier_id&page=productdetail&product_id=" . $row['product_id'];

                // FETCH VARIANTS
                $pId = $row['product_id'];
                $vStmt = mysqli_prepare($conn, "SELECT DISTINCT color FROM product_variant WHERE product_id = ? AND quantity > 0");
                mysqli_stmt_bind_param($vStmt, "i", $pId);
                mysqli_stmt_execute($vStmt);
                $vResult = mysqli_stmt_get_result($vStmt);
                $availableColors = [];
                while ($vRow = mysqli_fetch_assoc($vResult)) $availableColors[] = $vRow['color'];
        ?>
                <div class="product-card-wrapper tilt-wrapper">
                    <div class="product-card tilt-element">
                        <a href="<?= $detailLink ?>" class="card-link"></a>
                        <div class="card-image-box">
                            <img src="<?= $imgUrl ?>" alt="<?= $name ?>" class="product-img">
                        </div>
                        <div class="card-info">
                            <div>
                                <div class="p-category"><?= $catName ?></div>
                                <h3 class="p-title"><?= $name ?></h3>
                            </div>
                            <div class="p-footer">
                                <span class="p-price">$<?= $price ?></span>
                                <div class="color-options" style="position: relative; z-index: 20;">
                                    <?php if (empty($availableColors)): ?>
                                        <span style="font-size:0.8rem; color:#555;">Sold Out</span>
                                    <?php else: ?>
                                        <?php foreach ($availableColors as $col):
                                            $hex = getColorHex($col); ?>
                                            <div class="color-dot-radio"
                                                title="<?= htmlspecialchars($col) ?>"
                                                style="background-color: <?= $hex ?>;">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            endwhile;
        else:
            ?>
            <div class="col-12 text-center text-muted" style="width: 100%; padding: 50px;">
                <p>No products found matching your search.</p>
                <?php if (!empty($search_query)): ?>
                    <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="magnet-btn-dark" style="margin-top:20px; font-size:0.8rem; padding:10px 30px;">Clear Search</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (mysqli_num_rows($result) >= 9): ?>
        <div class="load-more-container">
            <button id="load-more-btn" class="magnet-btn-dark"
                data-offset="9"
                data-supplier="<?= $supplier_id ?>"
                data-category="<?= $category_filter ?>"
                data-search="<?= htmlspecialchars($search_query) ?>">
                View More
            </button>
        </div>
    <?php endif; ?>
</section>

<script>
    // 3D Tilt Logic
    document.addEventListener("DOMContentLoaded", function() {
        const cards = document.querySelectorAll('.tilt-element');
        cards.forEach(card => attachTilt(card));

        function attachTilt(card) {
            card.addEventListener('mousemove', handleHover);
            card.addEventListener('mouseleave', resetCard);
        }

        function handleHover(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = ((y - centerY) / centerY) * -5;
            const rotateY = ((x - centerX) / centerX) * 5;
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
        }

        function resetCard() {
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
        }

        // Expose function for new elements
        window.attachTiltToElement = attachTilt;
    });

    // View More Button Functionality
    document.addEventListener("DOMContentLoaded", function() {
        const loadMoreBtn = document.getElementById('load-more-btn');
        const productGrid = document.getElementById('product-grid');

        if (loadMoreBtn && productGrid) {
            loadMoreBtn.addEventListener('click', async function() {
                const offset = parseInt(loadMoreBtn.dataset.offset || 9);
                const supplierId = loadMoreBtn.dataset.supplier;
                const categoryFilter = loadMoreBtn.dataset.category || 'all';
                const searchQuery = loadMoreBtn.dataset.search || '';

                loadMoreBtn.disabled = true;
                const originalText = loadMoreBtn.textContent;
                loadMoreBtn.textContent = 'Loading...';

                try {
                    const formData = new FormData();
                    formData.append('offset', offset);
                    formData.append('supplier_id', supplierId);
                    if (categoryFilter !== 'all') formData.append('category_id', categoryFilter);
                    if (searchQuery !== '') formData.append('search', searchQuery);

                    // NOTE: Ensure this path is correct for your server
                    const response = await fetch('/Malltiverse/FrontEnd/templates/template4/fetch_products.php', {
                        method: 'POST',
                        body: formData
                    });

                    const html = await response.text();

                    if (html.trim() === 'NO_MORE' || html.trim() === '') {
                        loadMoreBtn.textContent = 'No More Products';
                        loadMoreBtn.disabled = true;
                    } else {
                        // Append new items
                        const temp = document.createElement('div');
                        temp.innerHTML = html;

                        const newCards = [];
                        while (temp.firstChild) {
                            if (temp.firstChild.nodeType === 1) {
                                newCards.push(temp.firstChild);
                                productGrid.appendChild(temp.firstChild);
                            } else {
                                productGrid.appendChild(temp.firstChild);
                            }
                        }

                        // Update offset
                        loadMoreBtn.dataset.offset = offset + 9;
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = originalText;

                        // Re-attach JS Tilt
                        if (typeof window.attachTiltToElement === 'function') {
                            newCards.forEach(wrapper => {
                                const card = wrapper.querySelector('.tilt-element');
                                if (card) window.attachTiltToElement(card);
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = originalText;
                }
            });
        }
    });
</script>