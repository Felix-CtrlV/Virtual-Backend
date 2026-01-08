<?php
session_start();
include("partials/nav.php");

// --- CONFIGURATION ---
$items_per_page = 6;
$supplier_id = $_SESSION['supplier_id'] ?? 6; // Default to 6 if session not set (for demo)

// --- MOCK DATABASE (Populated from your product.csv) ---
// In a real app, this would be: $conn->query("SELECT * FROM products WHERE supplier_id = $supplier_id");
$all_products = [
    ['id' => 1, 'supplier_id' => 5, 'name' => 'uniqlo sweatshirt', 'price' => 5000.00, 'image' => 'uniqlohoodie.jpg', 'stock' => 120, 'status' => 'active'],
    ['id' => 2, 'supplier_id' => 6, 'name' => 'Clothing', 'price' => 220.00, 'image' => 'clothing.png', 'stock' => 50, 'status' => 'active'],
    ['id' => 3, 'supplier_id' => 6, 'name' => 'Footwear', 'price' => 225.00, 'image' => 'footwear.jpg', 'stock' => 30, 'status' => 'active'],
    ['id' => 4, 'supplier_id' => 6, 'name' => 'Accessories', 'price' => 300.00, 'image' => 'accessories1.jpg', 'stock' => 15, 'status' => 'active'],
    ['id' => 5, 'supplier_id' => 6, 'name' => 'Fregrance', 'price' => 250.00, 'image' => 'perfume.jpg', 'stock' => 60, 'status' => 'active'],
    ['id' => 6, 'supplier_id' => 6, 'name' => 'Dress', 'price' => 20.00, 'image' => 'gucci dress.jpg', 'stock' => 5, 'status' => 'inactive'], // Low stock example
    ['id' => 7, 'supplier_id' => 6, 'name' => 'White Dress', 'price' => 22.00, 'image' => 'whitedress.jpg', 'stock' => 0, 'status' => 'inactive'],
    ['id' => 8, 'supplier_id' => 6, 'name' => 'Black Dress', 'price' => 300.00, 'image' => 'blackdress.jpg', 'stock' => 25, 'status' => 'active'],
    ['id' => 9, 'supplier_id' => 6, 'name' => 'Red Dress', 'price' => 200.00, 'image' => 'reddress.jpg', 'stock' => 18, 'status' => 'active'],
    ['id' => 10, 'supplier_id' => 6, 'name' => 'Short Dress', 'price' => 220.00, 'image' => 'shortdress.jpg', 'stock' => 10, 'status' => 'active'],
    ['id' => 11, 'supplier_id' => 6, 'name' => 'Fit Dress', 'price' => 300.00, 'image' => 'fitcoatdress.jpg', 'stock' => 42, 'status' => 'active'],
    ['id' => 16, 'supplier_id' => 6, 'name' => 'Heels', 'price' => 400.00, 'image' => 'heel.jpg', 'stock' => 8, 'status' => 'active'],
    ['id' => 18, 'supplier_id' => 6, 'name' => 'Jimmy choo heels', 'price' => 450.00, 'image' => 'heel1.jpg', 'stock' => 12, 'status' => 'active'],
    ['id' => 28, 'supplier_id' => 4, 'name' => 'NIKE1', 'price' => 900.00, 'image' => 'nike1.jpg', 'stock' => 45, 'status' => 'active'],
    ['id' => 31, 'supplier_id' => 6, 'name' => 'Neckless', 'price' => 200.00, 'image' => 'neckless.jpg', 'stock' => 100, 'status' => 'active'],
    // ... Added enough items to test pagination
];

// 1. FILTER: Get only this supplier's products
$my_products = array_filter($all_products, function ($p) use ($supplier_id) {
    return $p['supplier_id'] == $supplier_id;
});

// 2. STATS CALCULATION
$total_items = count($my_products);
$total_value = 0;
$low_stock_count = 0;

foreach ($my_products as $p) {
    $total_value += ($p['price'] * $p['stock']);
    if ($p['stock'] < 10) $low_stock_count++;
}

// 3. PAGINATION LOGIC
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $items_per_page;
$paginated_products = array_slice($my_products, $offset, $items_per_page);
$total_pages = ceil($total_items / $items_per_page);

// --- HANDLERS (Placeholder) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add/Edit/Toggle logic here (SQL Update/Insert)
    // Refresh page to show changes
    // header("Location: inventory.php");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Supplier Dashboard</title>
    <link rel="stylesheet" href="supplierCss.css">

    <style>
        /* --- UTILITIES & LAYOUT --- */
        :root {
            --primary-accent: #333;
            --secondary-accent: #FFD55A;
            --danger: #e74c3c;
            --success: #2ecc71;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .page-container {
            padding: 30px 0 60px 0;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* --- STATS CARDS --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-title {
            font-size: 0.9rem;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-top: 10px;
        }

        .stat-icon {
            font-size: 1.5rem;
            opacity: 0.7;
            align-self: flex-end;
            margin-top: -30px;
        }

        /* --- ACTION BAR --- */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-add {
            background: var(--primary-accent);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            background: var(--secondary-accent);
            color: black;
        }

        /* --- TABLE STYLES --- */
        .inventory-panel {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .custom-table th {
            text-align: left;
            padding: 15px;
            color: #777;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .custom-table td {
            padding: 15px;
            background: white;
            vertical-align: middle;
            border-top: 1px solid rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid rgba(0, 0, 0, 0.02);
        }

        .custom-table td:first-child {
            border-radius: 12px 0 0 12px;
            border-left: 1px solid rgba(0, 0, 0, 0.02);
        }

        .custom-table td:last-child {
            border-radius: 0 12px 12px 0;
            border-right: 1px solid rgba(0, 0, 0, 0.02);
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-img {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
            background: #eee;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .badge.instock {
            background: rgba(46, 204, 113, 0.15);
            color: var(--success);
        }

        .badge.lowstock {
            background: rgba(231, 76, 60, 0.15);
            color: var(--danger);
        }

        .btn-icon {
            background: #f4f4f4;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            cursor: pointer;
            color: #555;
            transition: 0.2s;
        }

        .btn-icon:hover {
            background: #333;
            color: white;
        }

        /* --- PAGINATION --- */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 8px;
        }

        .page-link {
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: #555;
            background: rgba(255, 255, 255, 0.6);
            transition: 0.2s;
            font-weight: 500;
        }

        .page-link.active {
            background: #333;
            color: white;
        }

        .page-link:hover:not(.active) {
            background: white;
            transform: translateY(-2px);
        }

        /* --- MODERN MODAL --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.open {
            opacity: 1;
        }

        .modern-modal {
            background: #fff;
            width: 550px;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }

        .modal-overlay.open .modern-modal {
            transform: scale(1);
        }

        .modal-header h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .modal-header p {
            color: #888;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }

        /* Drag & Drop Upload */
        .upload-zone {
            border: 2px dashed #e0e0e0;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
            margin-bottom: 25px;
            background: #fafafa;
            position: relative;
            overflow: hidden;
        }

        .upload-zone:hover {
            border-color: var(--secondary-accent);
            background: #fffcf0;
        }

        .upload-preview {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 14px;
            display: none;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .modern-input {
            width: 100%;
            padding: 14px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: 0.2s;
            background: #f9f9f9;
        }

        .modern-input:focus {
            border-color: #333;
            background: white;
            outline: none;
        }

        .stock-control {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f4f4f4;
            padding: 10px;
            border-radius: 12px;
            margin-top: 5px;
        }

        .stock-val {
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="page-container">

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-title">Total Products</span>
                <div class="stat-value"><?= number_format($total_items) ?></div>
                <div class="stat-icon">📦</div>
            </div>
            <div class="stat-card">
                <span class="stat-title">Inventory Value</span>
                <div class="stat-value">$<?= number_format($total_value, 2) ?></div>
                <div class="stat-icon">💰</div>
            </div>
            <div class="stat-card" style="border-bottom: 4px solid <?= $low_stock_count > 0 ? 'var(--danger)' : 'var(--success)' ?>">
                <span class="stat-title">Low Stock Alerts</span>
                <div class="stat-value" style="color: <?= $low_stock_count > 0 ? 'var(--danger)' : 'inherit' ?>">
                    <?= $low_stock_count ?>
                </div>
                <div class="stat-icon">⚠️</div>
            </div>
        </div>

        <div class="action-bar">
            <div>
                <h2 style="font-weight: 300; font-size: 2rem;">Product <b>Inventory</b></h2>
                <small style="color: #666;">Manage catalog for Supplier #<?= $supplier_id ?></small>
            </div>
            <button class="btn-add" onclick="openModal('addModal')">
                <span>+</span> Add New Product
            </button>
        </div>

        <div class="inventory-panel">
            <?php if (empty($paginated_products)): ?>
                <div style="text-align:center; padding:40px; color:#888;">No products found. Add one to get started!</div>
            <?php else: ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th width="40%">Product Details</th>
                            <th>Price</th>
                            <th>Stock Status</th>
                            <th>Availability</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginated_products as $p): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="../uploads/products/<?= htmlspecialchars($p['image']) ?>"
                                            class="product-img"
                                            onerror="this.src='https://via.placeholder.com/50?text=No+Img'">
                                        <div>
                                            <div style="font-weight:bold;"><?= htmlspecialchars($p['name']) ?></div>
                                            <small style="color:#999;">ID: #<?= $p['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight:600;">$<?= number_format($p['price'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $p['stock'] > 10 ? 'instock' : 'lowstock' ?>">
                                        <?= $p['stock'] ?> Units
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size:0.85rem; color: <?= $p['status'] == 'active' ? 'green' : '#aaa' ?>;">
                                        ● <?= ucfirst($p['status']) ?>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn-icon" onclick='openEditModal(<?= json_encode($p) ?>)'>✏️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="page-link">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div id="addModal" class="modal-overlay">
        <div class="modern-modal">
            <div style="position:absolute; top:20px; right:20px; cursor:pointer; font-size:1.5rem;" onclick="closeModal('addModal')">&times;</div>

            <div class="modal-header">
                <h2>New Product</h2>
                <p>Fill in the details below to add to your catalog.</p>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="upload-zone" onclick="document.getElementById('addFile').click()">
                    <input type="file" id="addFile" name="image" hidden onchange="previewImage(this, 'addPreview')">
                    <img id="addPreview" class="upload-preview">
                    <div style="color:#888;">
                        <div style="font-size:2rem; margin-bottom:10px;">☁️</div>
                        <span style="font-weight:600; color:#333; text-decoration:underline;">Click to upload</span> or drag image here
                    </div>
                </div>

                <div class="input-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" class="modern-input" placeholder="e.g. Summer Dress" required>
                </div>

                <div style="display:flex; gap:15px;">
                    <div class="input-group" style="flex:1;">
                        <label>Price ($)</label>
                        <input type="number" step="0.01" name="price" class="modern-input" placeholder="0.00" required>
                    </div>
                    <div class="input-group" style="flex:1;">
                        <label>Initial Quantity</label>
                        <input type="number" name="initial_stock" class="modern-input" placeholder="0" required>
                    </div>
                </div>

                <button type="submit" name="add_product" class="btn-add" style="width:100%; justify-content:center; margin-top:10px;">
                    Create Product
                </button>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modern-modal">
            <div style="position:absolute; top:20px; right:20px; cursor:pointer; font-size:1.5rem;" onclick="closeModal('editModal')">&times;</div>

            <div class="modal-header">
                <h2>Edit Product</h2>
                <p>Update stock levels, pricing, or imagery.</p>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" id="edit_id">

                <div class="upload-zone" style="height:150px; padding:0; display:flex; align-items:center; justify-content:center;" onclick="document.getElementById('editFile').click()">
                    <input type="file" id="editFile" name="edit_image" hidden onchange="previewImage(this, 'editPreviewImg')">

                    <img id="editPreviewImg" class="upload-preview" style="display:block;">
                    <div style="position:absolute; bottom:10px; background:rgba(0,0,0,0.6); color:white; padding:5px 15px; border-radius:20px; font-size:0.8rem; pointer-events:none;">
                        Change Image
                    </div>
                </div>

                <div class="input-group">
                    <label>Product Name</label>
                    <input type="text" id="edit_name" class="modern-input" disabled style="background:#f0f0f0; color:#888;">
                </div>

                <div class="input-group">
                    <label>Update Price ($)</label>
                    <input type="number" step="0.01" name="edit_price" id="edit_price" class="modern-input">
                </div>

                <div class="input-group">
                    <label>Stock Management</label>
                    <div class="stock-control">
                        <div style="color:#666; font-size:0.9rem;">Current Level: <b id="current_stock_display">0</b></div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:0.9rem;">Add:</span>
                            <input type="number" name="add_stock" class="modern-input" style="width:80px; padding:8px;" placeholder="+0">
                        </div>
                    </div>
                </div>

                <button type="submit" name="edit_product" class="btn-add" style="width:100%; justify-content:center; margin-top:10px;">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        // --- Modal Logic ---
        function openModal(id) {
            const el = document.getElementById(id);
            el.style.display = 'flex';
            // Small timeout to allow display:flex to apply before opacity transition
            setTimeout(() => el.classList.add('open'), 10);
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            el.classList.remove('open');
            setTimeout(() => el.style.display = 'none', 300);
        }

        function openEditModal(product) {
            // Populate fields
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('current_stock_display').innerText = product.stock;

            // Handle Image
            const imgPath = "../uploads/products/" + product.image;
            document.getElementById('editPreviewImg').src = imgPath;

            openModal('editModal');
        }

        // Close on Outside Click
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal(e.target.id);
            }
        }

        // --- Image Preview Logic ---
        function previewImage(input, imgId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById(imgId);
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>

</html>