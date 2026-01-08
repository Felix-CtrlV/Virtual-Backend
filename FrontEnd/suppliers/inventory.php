<?php
session_start();
include("partials/nav.php");

// --- CONFIGURATION ---
$items_per_page = 6;
$supplier_id = $_SESSION['supplierid'] ?? 6; // Default fallback

// ==========================================
// 0. HANDLE AJAX: ADD NEW CATEGORY
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'add_category') {
    $cat_name = trim($_POST['category_name']);
    if (!empty($cat_name)) {
        $stmt = $conn->prepare("INSERT INTO category (supplier_id, category_name) VALUES (?, ?)");
        $stmt->bind_param("is", $supplier_id, $cat_name);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'id' => $conn->insert_id, 'name' => htmlspecialchars($cat_name)]);
        } else {
            echo json_encode(['status' => 'error']);
        }
        $stmt->close();
    }
    exit;
}

// ==========================================
// 1. HANDLE UPDATES (Price & Stock)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_existing') {
    $pid = (int) $_POST['product_id'];
    $new_price = (float) $_POST['product_price'];

    $stmt = $conn->prepare("UPDATE products SET price = ? WHERE product_id = ? AND supplier_id = ?");
    $stmt->bind_param("dii", $new_price, $pid, $supplier_id);
    $stmt->execute();
    $stmt->close();

    if (isset($_POST['stock']) && is_array($_POST['stock'])) {
        $updateStmt = $conn->prepare("UPDATE product_variant SET quantity = ? WHERE variant_id = ?");
        foreach ($_POST['stock'] as $vid => $qty) {
            $updateStmt->bind_param("ii", $qty, $vid);
            $updateStmt->execute();
        }
        $updateStmt->close();
    }
    header("Location: inventory.php");
    exit;
}

// ==========================================
// 2. HANDLE ADDING NEW VARIANT
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_variant') {
    $pid = (int) $_POST['product_id'];
    $color = $_POST['new_color'];
    $size = $_POST['new_size'];
    $qty = (int) $_POST['new_qty'];

    if ($pid && $color && $size) {
        $stmt = $conn->prepare("INSERT INTO product_variant (product_id, color, size, quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $pid, $color, $size, $qty);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: inventory.php");
    exit;
}

// ==========================================
// 3. HANDLE ADDING NEW PRODUCT (FIXED)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_main'])) {

    $p_name = $_POST['product_name'];
    $p_desc = $_POST['description'];
    $p_cat = (int) $_POST['category_id'];
    $p_price = (float) $_POST['price'];
    $initial_stock = (int) $_POST['initial_stock'];

    // 1. Insert Product Info
    $stmt = $conn->prepare("INSERT INTO products (supplier_id, category_id, product_name, description, price, status, created_at) VALUES (?, ?, ?, ?, ?, 'available', NOW())");
    $stmt->bind_param("iisss", $supplier_id, $p_cat, $p_name, $p_desc, $p_price);

    if ($stmt->execute()) {
        $new_product_id = $conn->insert_id;
        $stmt->close();

        // 2. Handle Image Upload (ROBUST PATHING)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

            // Define path using __DIR__ to be safe (Current dir is 'supplier', so go up one to root, then uploads)
            $upload_dir = __DIR__ . '/../uploads/products/';

            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Get extension (e.g. png)
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            // Clean original name (remove spaces/weird chars) for safety
            $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($_FILES['image']['name'], PATHINFO_FILENAME));

            // Format: ID_OriginalName.ext (e.g. 15_nike.png)
            $final_filename = $new_product_id . "_" . $clean_name . "." . $file_ext;
            $target_file = $upload_dir . $final_filename;

            // Move the file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // UPDATE DATABASE with the Final Filename
                $updateImg = $conn->prepare("UPDATE products SET image = ? WHERE product_id = ?");
                $updateImg->bind_param("si", $final_filename, $new_product_id);
                $updateImg->execute();
                $updateImg->close();
            } else {
                // Optional: Error handling if move fails
                // echo "Failed to move file to " . $target_file; exit;
            }
        }

        // 3. Create Default Variant
        $def_color = "#000000";
        $def_size = "One Size";
        $varStmt = $conn->prepare("INSERT INTO product_variant (product_id, color, size, quantity) VALUES (?, ?, ?, ?)");
        $varStmt->bind_param("issi", $new_product_id, $def_color, $def_size, $initial_stock);
        $varStmt->execute();
        $varStmt->close();
    }

    header("Location: inventory.php");
    exit;
}
$catQuery = $conn->prepare("SELECT category_id, category_name FROM category WHERE supplier_id = ?");
$catQuery->bind_param("i", $supplier_id);
$catQuery->execute();
$categories_result = $catQuery->get_result();
$categories = [];
while ($c = $categories_result->fetch_assoc()) {
    $categories[] = $c;
}
$catQuery->close();

// Products
$productQuery = $conn->prepare('SELECT p.product_id, p.supplier_id, p.product_name, p.price, p.image, p.status, 
                                     v.variant_id, v.size, v.color, v.quantity
                                FROM products p 
                                LEFT JOIN product_variant v ON p.product_id = v.product_id 
                                WHERE p.supplier_id = ? ORDER BY p.product_id DESC');
$productQuery->bind_param('i', $supplier_id);
$productQuery->execute();
$result = $productQuery->get_result();

$grouped_products = [];
while ($row = $result->fetch_assoc()) {
    $pid = $row['product_id'];
    if (!isset($grouped_products[$pid])) {
        $grouped_products[$pid] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'price' => $row['price'],
            'image' => $row['image'],
            'status' => $row['status'],
            'total_stock' => 0,
            'variants' => []
        ];
    }
    if ($row['variant_id']) {
        $grouped_products[$pid]['variants'][] = [
            'variant_id' => $row['variant_id'],
            'size' => $row['size'],
            'color' => $row['color'],
            'quantity' => $row['quantity']
        ];
        $grouped_products[$pid]['total_stock'] += $row['quantity'];
    }
}
$my_products = array_values($grouped_products);

// Stats & Pagination
$total_items = count($my_products);
$total_value = 0;
$low_stock_count = 0;
foreach ($my_products as $p) {
    $total_value += ($p['price'] * $p['total_stock']);
    if ($p['total_stock'] < 10)
        $low_stock_count++;
}

$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$offset = ($page - 1) * $items_per_page;
$paginated_products = array_slice($my_products, $offset, $items_per_page);
$total_pages = ceil($total_items / $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <style>
        /* --- GENERAL MODAL STYLES --- */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .modal-overlay.open {
            opacity: 1;
        }

        .modal-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            display: flex;
        }

        /* --- ADD PRODUCT MODAL SPECIFIC (GRID LAYOUT) --- */
        .add-product-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            /* Image Col | Form Col */
            width: 850px;
            height: 600px;
            /* Fixed height for consistency */
        }

        /* Left Column: Image Upload */
        .ap-image-section {
            background: #f4f5f7;
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #eee;
        }

        .image-drop-zone {
            width: 100%;
            height: 100%;
            max-height: 400px;
            border: 2px dashed #ccc;
            border-radius: 10px;
            position: relative;
            background: white;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
        }

        .image-drop-zone:hover {
            border-color: #333;
            background: #fafafa;
        }

        /* The Preview Image Logic */
        .preview-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            /* Key: keeps aspect ratio inside box */
            display: none;
            z-index: 2;
        }

        .upload-msg {
            text-align: center;
            color: #888;
            pointer-events: none;
        }

        .file-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 3;
        }

        /* Right Column: Form Inputs */
        .ap-form-section {
            padding: 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .ap-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        /* Inputs & Pills */
        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }

        .input-std {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .input-row {
            display: flex;
            gap: 15px;
        }

        /* Pills */
        .pill-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pill-radio {
            display: none;
        }

        .pill-label {
            padding: 6px 14px;
            background: #eee;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: 0.2s;
            border: 1px solid transparent;
        }

        .pill-radio:checked+.pill-label {
            background: #333;
            color: white;
        }

        .pill-add-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
        }

        /* New Category Input */
        .new-cat-box {
            display: none;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }

        .new-cat-box.active {
            display: flex;
        }

        .btn-submit {
            background: #222;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
            margin-top: auto;
            /* Pushes to bottom */
        }

        .btn-submit:hover {
            background: #000;
        }
    </style>
</head>

<body>

    <section>
        <div class="page-container">

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-title">Total Products</span>
                    <div class="stat-value"><?= number_format($total_items) ?></div>
                </div>
                <div class="stat-card">
                    <span class="stat-title">Inventory Value</span>
                    <div class="stat-value">$<?= number_format($total_value, 2) ?></div>
                </div>
                <div class="stat-card"
                    style="border-bottom: 4px solid <?= $low_stock_count > 0 ? 'var(--danger)' : 'var(--success)' ?>">
                    <span class="stat-title">Low Stock Alerts</span>
                    <div class="stat-value"><?= $low_stock_count ?></div>
                </div>
            </div>

            <div class="action-bar">
                <div>
                    <h1 style="margin:0; font-weight:300;">Product <b>Inventory</b></h1>
                    <small style="color:#888;">Supplier ID: <?= $supplier_id ?></small>
                </div>
                <button class="btn-main" onclick="openModal('addModal')"><span>+</span> Add Product</button>
            </div>

            <div class="inventory-panel">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th width="45%">Product</th>
                            <th>Base Price</th>
                            <th>Total Stock</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginated_products as $p): ?>
                            <tr class="product-row" onclick='openVariantModal(<?= json_encode($p) ?>)'>
                                <td>
                                    <div class="product-flex">
                                        <img src="../uploads/products/<?= $p['product_id'] ?>_<?= $p['image'] ?>"
                                            class="thumb-img">
                                        <div>
                                            <div style="font-weight:700;"><?= htmlspecialchars($p['product_name']) ?></div>
                                            <small style="color:#aaa;">#<?= $p['product_id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight:600;">$<?= number_format($p['price'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $p['total_stock'] < 10 ? 'low' : 'ok' ?>">
                                        <?= $p['total_stock'] ?> Units
                                    </span>
                                </td>
                                <td><span style="color:<?= $p['status'] == 'available' ? 'green' : '#ccc' ?>">●</span>
                                    <?= ucfirst($p['status']) ?></td>
                                <td>Edit ✏️</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px; text-align:center;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>"
                        style="padding:10px 15px; margin:0 2px; background:<?= $i == $page ? '#333' : 'white' ?>; color:<?= $i == $page ? 'white' : '#333' ?>; text-decoration:none; border-radius:5px;"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>

        <div id="addModal" class="modal-overlay">
            <div class="modal-box add-product-container">

                <form method="POST" enctype="multipart/form-data" style="display:contents;">
                    <input type="hidden" name="add_product_main" value="1">

                    <div class="ap-image-section">
                        <div style="margin-bottom:10px; font-weight:600; color:#555;">Product Image</div>
                        <div class="image-drop-zone">
                            <input type="file" name="image" class="file-input" accept="image/*" required
                                onchange="previewFile(this)">
                            <div class="upload-msg" id="uploadPlaceholder">
                                <div style="font-size:2rem; margin-bottom:10px;">📷</div>
                                <span>Click to Upload</span><br>
                                <small style="color:#aaa">Supports PNG, JPG, JPEG</small>
                            </div>
                            <img id="imagePreview" class="preview-img">
                        </div>
                    </div>

                    <div class="ap-form-section">
                        <div class="ap-header">
                            <h2 style="margin:0;">New Product</h2>
                            <span onclick="closeModal('addModal')"
                                style="cursor:pointer; font-size:1.5rem; color:#888;">&times;</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="product_name" class="input-std" placeholder="e.g. Air Jordan 1"
                                required>
                        </div>

                        <div class="input-row">
                            <div class="form-group" style="flex:1;">
                                <label class="form-label">Price ($)</label>
                                <input type="number" step="0.01" name="price" class="input-std" required>
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label class="form-label">Initial Stock</label>
                                <input type="number" name="initial_stock" class="input-std" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <div class="pill-container" id="pillContainer">
                                <?php foreach ($categories as $cat): ?>
                                    <label>
                                        <input type="radio" name="category_id" value="<?= $cat['category_id'] ?>"
                                            class="pill-radio" required>
                                        <span class="pill-label"><?= htmlspecialchars($cat['category_name']) ?></span>
                                    </label>
                                <?php endforeach; ?>

                                <div class="pill-add-btn" onclick="toggleAddCategory()">+</div>
                            </div>

                            <div class="new-cat-box" id="newCatInput">
                                <input type="text" id="newCatName" class="input-std" placeholder="Category Name"
                                    style="padding:5px; height:30px;">
                                <button type="button" class="btn-sm" onclick="saveNewCategory()">Add</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="input-std" style="height:100px; resize:none;"
                                placeholder="Product details..."></textarea>
                        </div>

                        <button class="btn-submit">Create Product</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="variantModal" class="modal-overlay">
            <div class="modal-box">
                <div style="position:absolute; top:20px; right:20px; cursor:pointer; font-size:1.5rem; z-index:10;"
                    onclick="closeModal('variantModal')">&times;</div>
                <div class="modal-left">
                    <img id="varModalImg" class="modal-big-img">
                </div>
                <div class="modal-right">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_existing">
                        <input type="hidden" name="product_id" id="varModalId">
                        <div class="modal-header">
                            <h2 id="varModalTitle" style="margin:0; font-size:1.6rem;">Loading...</h2>
                            <div class="price-input-group">
                                <span style="font-size:1.5rem; color:#888;">$</span>
                                <input type="number" step="0.01" name="product_price" id="varModalPrice"
                                    class="price-edit-input">
                            </div>
                        </div>
                        <div style="max-height: 250px; overflow-y: auto; margin-bottom: 20px;">
                            <table class="var-table">
                                <thead>
                                    <tr>
                                        <th>Color</th>
                                        <th>Size</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody id="varModalTable"></tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </form>
                    <form method="POST" style="margin-top:20px;">
                        <input type="hidden" name="action" value="add_variant">
                        <input type="hidden" name="product_id" id="addVarId">
                        <div class="add-variant-box">
                            <div class="add-title"><span>+</span> Add Variant</div>
                            <div class="add-grid">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <input type="color" name="new_color"
                                        style="width:40px; height:40px; border:none; cursor:pointer;" required>
                                </div>
                                <input type="text" name="new_size" placeholder="Size" class="input-sm" required>
                                <input type="number" name="new_qty" placeholder="Qty" class="input-sm" required min="0">
                                <button type="submit" class="btn-sm">Add</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </section>

    <script>
        // --- GENERAL MODAL LOGIC ---
        function openModal(id) {
            const el = document.getElementById(id);
            el.style.display = 'flex';
            setTimeout(() => el.classList.add('open'), 10);
        }
        function closeModal(id) {
            const el = document.getElementById(id);
            el.classList.remove('open');
            setTimeout(() => el.style.display = 'none', 300);
        }
        window.onclick = function (e) {
            if (e.target.classList.contains('modal-overlay')) closeModal(e.target.id);
        }

        // --- IMAGE PREVIEW (Fixed Aspect Ratio) ---
        function previewFile(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.getElementById('imagePreview');
                    const msg = document.getElementById('uploadPlaceholder');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    msg.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        }

        // --- CATEGORY PILLS AJAX ---
        function toggleAddCategory() {
            document.getElementById('newCatInput').classList.toggle('active');
            document.getElementById('newCatName').focus();
        }
        function saveNewCategory() {
            const name = document.getElementById('newCatName').value;
            if (!name) return;
            const formData = new FormData();
            formData.append('ajax_action', 'add_category');
            formData.append('category_name', name);

            fetch('inventory.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        const container = document.getElementById('pillContainer');
                        const btn = document.querySelector('.pill-add-btn');
                        const label = document.createElement('label');
                        label.innerHTML = `<input type="radio" name="category_id" value="${data.id}" class="pill-radio" checked><span class="pill-label">${data.name}</span>`;
                        container.insertBefore(label, btn);
                        document.getElementById('newCatName').value = '';
                        document.getElementById('newCatInput').classList.remove('active');
                    }
                });
        }

        // --- EXISTING VARIANT MODAL ---
        function openVariantModal(p) {
            document.getElementById('varModalTitle').innerText = p.product_name;
            document.getElementById('varModalPrice').value = p.price;
            document.getElementById('varModalId').value = p.product_id;
            document.getElementById('addVarId').value = p.product_id;
            document.getElementById('varModalImg').src = "../uploads/products/" + p.product_id + '_' + p.image;
            const tbody = document.getElementById('varModalTable');
            tbody.innerHTML = '';
            if (p.variants && p.variants.length > 0) {
                p.variants.forEach(v => {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td><span style="display:inline-block;width:15px;height:15px;background:${v.color};border-radius:50%;margin-right:5px;vertical-align:middle;border:1px solid #ddd;"></span>${v.color}</td><td style="color:#666;">${v.size}</td><td><input type="number" name="stock[${v.variant_id}]" value="${v.quantity}" class="qty-box" min="0"></td>`;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#999;">No variants.</td></tr>';
            }
            openModal('variantModal');
        }
    </script>

</body>

</html>