<?php
// products.php - Fixed Path & Infinite Scroll

if (!isset($supplier_id)) {
    // Ideally this should be checked, but for standalone testing we might suppress it or ensure the parent includes it.
    // die("Access Denied"); 
}

$category_filter = isset($_GET['category_id']) ? $_GET['category_id'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

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

// Check if this is an AJAX request for infinite scroll
if (isset($_POST['ajax_load']) && $_POST['ajax_load'] == 'true') {
    // Handle AJAX request for infinite scroll
    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
    $supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
    $category_filter = (isset($_POST['category_id']) && $_POST['category_id'] !== 'all') ? (int)$_POST['category_id'] : null;
    $search_query = isset($_POST['search']) ? trim($_POST['search']) : '';
    $limit = 9;

    $sql = "SELECT p.*, c.category_name FROM products p 
              LEFT JOIN category c ON p.category_id = c.category_id 
              WHERE p.supplier_id = ? AND p.status = 'available'";
    $params = [$supplier_id];
    $types = "i";

    if ($category_filter) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_filter;
        $types .= "i";
    }

    if (!empty($search_query)) {
        $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
        $val = "%" . $search_query . "%";
        $params[] = $val;
        $params[] = $val;
        $types .= "ss";
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $imgUrl = "../uploads/products/" . $row['product_id'] . "_" . $row['image'];
            $price = number_format($row['price'], 2);
            $name = htmlspecialchars($row['product_name']);
            $catName = htmlspecialchars($row['category_name'] ?? 'Exclusive');
            $detailLink = "?supplier_id=$supplier_id&page=productdetail&product_id=" . $row['product_id'];

            $vStmt = mysqli_prepare($conn, "SELECT DISTINCT color FROM product_variant WHERE product_id = ? AND quantity > 0");
            mysqli_stmt_bind_param($vStmt, "i", $row['product_id']);
            mysqli_stmt_execute($vStmt);
            $vRes = mysqli_stmt_get_result($vStmt);
            $colors = [];
            while ($c = mysqli_fetch_assoc($vRes)) $colors[] = $c['color'];
            ?>
            <div class="product-card-wrapper">
                <div class="product-card tilt-element">
                    <a href="<?= $detailLink ?>" class="card-link"></a>
                    <div class="card-image-box"><img src="<?= $imgUrl ?>" class="product-img"></div>
                    <div class="card-info">
                        <div>
                            <div class="p-category"><?= $catName ?></div>
                            <h3 class="p-title"><?= $name ?></h3>
                        </div>
                        <div class="p-footer"><span class="p-price">$<?= $price ?></span>
                            <div class="color-options" style="position: relative; z-index: 20;">
                                <?php if (empty($colors)): ?><span style="font-size:0.8rem; color:#555;">Sold Out</span><?php else: ?>
                                    <?php foreach ($colors as $col): ?><div class="color-dot-radio" style="background:<?= getColorHex($col) ?>"></div><?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        echo "NO_MORE";
    }
    exit; // Stop execution for AJAX requests
}
?>

<style>
    /* VARIABLES & LAYOUT */
    :root {
        --bg-color: #0a0a0a;
        --card-bg: #141414;
        --text-main: #ffffff;
        --accent: #D4AF37;
        --border-color: #2a2a2a;
        --font-display: 'Helvetica Neue', 'Arial Black', sans-serif;
        --font-body: 'Helvetica', sans-serif;
    }

    .products-page {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: var(--font-body);
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* Hero & Search */
    .products-hero {
        padding: 120px 0 60px;
        text-align: center;
    }

    .products-title {
        font-family: var(--font-display);
        font-size: clamp(3rem, 10vw, 8rem);
        text-transform: uppercase;
        font-weight: 900;
        margin-bottom: 40px;
    }

    .search-wrapper {
        margin: 0 auto 30px;
        max-width: 500px;
        position: relative;
        padding: 0 20px;
    }

    .search-input {
        width: 100%;
        background: transparent;
        border: 1px solid #333;
        border-radius: 50px;
        padding: 15px 50px 15px 25px;
        color: #fff;
        outline: none;
        transition: 0.3s;
    }

    .search-input:focus {
        border-color: var(--accent);
        background: rgba(255, 255, 255, 0.05);
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
    }

    /* Filters */
    .filter-container {
        display: flex;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 80px;
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
        text-decoration: none;
        transition: 0.3s;
    }

    .category-btn:hover,
    .category-btn.active {
        background: #fff;
        color: #000;
        border-color: #fff;
    }

    /* Product Grid */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 40px;
        padding: 0 5%;
        margin-bottom: 50px;
    }

    .product-card {
        background-color: var(--card-bg);
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        transition: transform 0.1s ease-out;
        border: 1px solid var(--border-color);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .card-image-box {
        position: relative;
        width: 100%;
        aspect-ratio: 1 / 1;
        background: #1a1a1a;
        overflow: hidden;
    }

    .product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s;
    }

    .product-card:hover .product-img {
        transform: scale(1.08);
    }

    .card-info {
        padding: 25px;
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

    .color-options {
        display: flex;
        gap: 8px;
    }

    .color-dot-radio {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        padding: 2px;
        border: 1px solid #444;
    }

    .card-link {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 10;
    }

    /* End of Collection Animation */
    .end-message {
        text-align: center;
        padding: 60px 20px;
        grid-column: 1 / -1;
        animation: fadeInUp 1s ease-out;
    }

    .end-message h3 {
        font-family: var(--font-display);
        font-size: 2.5rem;
        color: var(--accent);
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .end-message p {
        color: #888;
        font-size: 1.1rem;
        max-width: 500px;
        margin: 0 auto 30px;
        line-height: 1.6;
    }

    .pulse-dots {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 30px;
    }

    .pulse-dot {
        width: 10px;
        height: 10px;
        background: var(--accent);
        border-radius: 50%;
        animation: pulse 1.5s infinite;
    }

    .pulse-dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .pulse-dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
            opacity: 0.7;
        }
        50% {
            transform: scale(1.3);
            opacity: 1;
        }
    }

    /* Loading Animation */
    .loading-content {
        text-align: center;
        padding: 40px;
        color: #888;
        font-size: 1.1rem;
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(255, 255, 255, 0.1);
        border-top: 3px solid var(--accent);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<section class="page-content products-page">
    <div class="products-hero">
        <h1 class="products-title">The Collection</h1>

        <div class="search-wrapper">
            <form action="" method="GET" id="search-form">
                <input type="hidden" name="supplier_id" value="<?= $supplier_id ?>">
                <input type="hidden" name="page" value="products">
                <?php if ($category_filter !== 'all'): ?><input type="hidden" name="category_id" value="<?= $category_filter ?>"><?php endif; ?>
                <input type="text" name="search" class="search-input" placeholder="Search..." value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="search-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </form>
        </div>

        <div class="filter-container">
            <a href="?supplier_id=<?= $supplier_id ?>&page=products" class="category-btn <?= $category_filter == 'all' ? 'active' : '' ?>">All</a>
            <?php
            $cat_query = mysqli_query($conn, "SELECT * FROM category WHERE company_id = $company_id ORDER BY category_name ASC");
            while ($cat = mysqli_fetch_assoc($cat_query)) {
                $cId = $cat['category_id'];
                $active = ($category_filter == $cId) ? 'active' : '';
                $searchStr = $search_query ? "&search=" . urlencode($search_query) : "";
                echo "<a href='?supplier_id=$supplier_id&page=products&category_id=$cId$searchStr' class='category-btn $active'>" . htmlspecialchars($cat['category_name']) . "</a>";
            }
            ?>
        </div>
    </div>

    <div class="product-grid" id="product-grid">
        <?php
        $sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN category c ON p.category_id = c.category_id WHERE p.company_id = ? AND status = 'available'";
        $types = "i";
        $params = [$company_id];

        if ($category_filter !== 'all') {
            $sql .= " AND p.category_id = ?";
            $types .= "i";
            $params[] = $category_filter;
        }
        if (!empty($search_query)) {
            $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
            $types .= "ss";
            $val = "%$search_query%";
            $params[] = $val;
            $params[] = $val;
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT 9";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
                $imgUrl = "../uploads/products/" . $row['product_id'] . "_" . $row['image'];
                $detailLink = "?supplier_id=$supplier_id&page=productdetail&product_id=" . $row['product_id'];

                $vStmt = mysqli_prepare($conn, "SELECT DISTINCT color FROM product_variant WHERE product_id = ? AND quantity > 0");
                mysqli_stmt_bind_param($vStmt, "i", $row['product_id']);
                mysqli_stmt_execute($vStmt);
                $vRes = mysqli_stmt_get_result($vStmt);
                $colors = [];
                while ($c = mysqli_fetch_assoc($vRes)) $colors[] = $c['color'];
        ?>
                <div class="product-card-wrapper tilt-wrapper">
                    <div class="product-card tilt-element">
                        <a href="<?= $detailLink ?>" class="card-link"></a>
                        <div class="card-image-box"><img src="<?= $imgUrl ?>" class="product-img"></div>
                        <div class="card-info">
                            <div>
                                <div class="p-category"><?= htmlspecialchars($row['category_name'] ?? 'Exclusive') ?></div>
                                <h3 class="p-title"><?= htmlspecialchars($row['product_name']) ?></h3>
                            </div>
                            <div class="p-footer"><span class="p-price">$<?= number_format($row['price'], 2) ?></span>
                                <div class="color-options">
                                    <?php foreach ($colors as $col): ?><div class="color-dot-radio" style="background:<?= getColorHex($col) ?>"></div><?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile;
        else: ?>
            <p style="grid-column: 1/-1; text-align: center; color: #666;">No products found.</p>
        <?php endif; ?>
    </div>

    <?php if (mysqli_num_rows($result) >= 9): ?>
        <div id="infinite-scroll-trigger" style="height: 50px; margin-bottom: 50px;"></div>
        <div id="loading-state" style="text-align: center; display: none; padding: 20px; color: #888;">Loading more...</div>
    <?php endif; ?>
</section>

<script>
    // 3D Tilt
    document.addEventListener("DOMContentLoaded", function() {
        const attachTilt = (card) => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = (e.clientX - rect.left - rect.width / 2) / (rect.width / 2) * 5;
                const y = (e.clientY - rect.top - rect.height / 2) / (rect.height / 2) * -5;
                this.style.transform = `perspective(1000px) rotateX(${y}deg) rotateY(${x}deg) scale(1.02)`;
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'perspective(1000px) rotate(0) scale(1)';
            });
        };
        document.querySelectorAll('.tilt-element').forEach(attachTilt);
        window.attachTiltToElement = attachTilt;
    });

    // INFINITE SCROLL - FIXED
    document.addEventListener("DOMContentLoaded", function() {
        const trigger = document.getElementById('infinite-scroll-trigger');
        const loader = document.getElementById('loading-state');
        const grid = document.getElementById('product-grid');

        let offset = 9;
        const sId = "<?= $supplier_id ?>";
        const cId = "<?= $category_filter ?>";
        const search = "<?= htmlspecialchars($search_query) ?>";
        let isFetching = false,
            hasMore = true;

        const loadMore = async () => {
            if (isFetching || !hasMore) return;
            isFetching = true;
            
            // Create a better loading indicator
            if (loader) loader.style.display = 'block';
            
            try {
                const formData = new FormData();
                formData.append('offset', offset);
                formData.append('supplier_id', sId);
                formData.append('ajax_load', 'true');
                if (cId !== 'all') formData.append('category_id', cId);
                if (search) formData.append('search', search);

                // FIXED: Use the current window URL. 
                // Since this PHP file handles the AJAX request at the top, we just post to the same page.
                const scriptPath = window.location.href;
                
                const response = await fetch(scriptPath, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const html = await response.text();
                
                if (html.trim() === 'NO_MORE' || !html.trim()) {
                    hasMore = false;
                    
                    // Remove the trigger
                    if (trigger) trigger.remove();
                    
                    // Remove the loading indicator
                    if (loader) loader.remove();
                    
                    // Add nice end message
                    const endMessage = document.createElement('div');
                    endMessage.className = 'end-message';
                    endMessage.innerHTML = `
                        <h3>End of Collection</h3>
                        <p>You've reached the end of our curated selection. Check back soon for new arrivals.</p>
                        <div class="pulse-dots">
                            <div class="pulse-dot"></div>
                            <div class="pulse-dot"></div>
                            <div class="pulse-dot"></div>
                        </div>
                    `;
                    
                    // Insert after the grid
                    grid.parentNode.insertBefore(endMessage, grid.nextSibling);
                    
                } else {
                    // Create a temporary container to parse HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    
                    // Get only the product cards
                    const productCards = tempDiv.querySelectorAll('.product-card-wrapper');
                    
                    if (productCards.length > 0) {
                        productCards.forEach(card => {
                            grid.appendChild(card);
                        });
                        offset += 9;
                        
                        // Re-attach tilt effect to new cards
                        if(window.attachTiltToElement) {
                            grid.querySelectorAll('.tilt-element').forEach(window.attachTiltToElement);
                        }
                        
                        // Hide loading indicator
                        if (loader) loader.style.display = 'none';
                    } else {
                        hasMore = false;
                        if (trigger) trigger.remove();
                        if (loader) loader.remove();
                    }
                }
            } catch (err) {
                console.error('Error loading products:', err);
                if (loader) {
                    loader.innerHTML = '<div class="loading-content"><div class="spinner"></div><div>Error loading products. Please try again.</div></div>';
                    loader.style.display = 'block';
                }
                
                // Try again after 3 seconds
                setTimeout(() => {
                    if (loader) {
                        loader.innerHTML = 'Loading more...';
                        loader.style.display = 'none';
                    }
                    isFetching = false;
                }, 3000);
            } finally {
                isFetching = false;
            }
        };

        if (trigger) {
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && hasMore && !isFetching) {
                    loadMore();
                }
            }, {
                threshold: 0.1,
                rootMargin: '100px'
            });
            observer.observe(trigger);
        }
        
        // Also add a manual scroll listener as backup
        window.addEventListener('scroll', function() {
            if (!trigger || !hasMore || isFetching) return;
            
            const triggerRect = trigger.getBoundingClientRect();
            if (triggerRect.top <= window.innerHeight + 100) {
                loadMore();
            }
        });
    });
</script>