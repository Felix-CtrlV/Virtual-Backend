<?php
ob_start();
session_start();

include("partials/nav.php");

// --- CONFIGURATION ---
$items_per_page = 6;
$supplier_id = $_SESSION['supplierid'] ?? 6;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ==========================================
// 1. UNIFIED HANDLE: ADD PRODUCT (CREATE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_full_product') {
    $conn->begin_transaction();
    try {
        $p_name = $_POST['product_name'];
        $p_desc = $_POST['description'];
        $p_price = (float) $_POST['price'];

        // A. Handle Category
        $final_cat_id = null;
        if (!empty($_POST['new_category_name'])) {
            $new_cat_name = trim($_POST['new_category_name']);
            $checkCat = $conn->prepare("SELECT category_id FROM category WHERE category_name = ? AND supplier_id = ?");
            $checkCat->bind_param("si", $new_cat_name, $supplier_id);
            $checkCat->execute();
            $checkCat->store_result();
            if ($checkCat->num_rows > 0) {
                $checkCat->bind_result($existing_id);
                $checkCat->fetch();
                $final_cat_id = $existing_id;
            } else {
                $insCat = $conn->prepare("INSERT INTO category (supplier_id, category_name) VALUES (?, ?)");
                $insCat->bind_param("is", $supplier_id, $new_cat_name);
                $insCat->execute();
                $final_cat_id = $conn->insert_id;
                $insCat->close();
            }
            $checkCat->close();
        } else {
            $final_cat_id = (int) $_POST['category_id'];
        }

        if (!$final_cat_id)
            throw new Exception("Category selection is required.");

        // B. Insert Product
        $temp_image_name = 'placeholder.png';
        $stmt = $conn->prepare("INSERT INTO products (supplier_id, category_id, product_name, description, price, image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'available', NOW())");
        $stmt->bind_param("iissds", $supplier_id, $final_cat_id, $p_name, $p_desc, $p_price, $temp_image_name);
        $stmt->execute();
        $new_product_id = $conn->insert_id;
        $stmt->close();

        // C. Handle Image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/products/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', $p_name);
            $clean_name = substr($clean_name, 0, 20);

            // Unique name logic
            $base_final = $new_product_id . "_" . $clean_name;
            $final_filename = $base_final . '.' . $file_ext;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $final_filename)) {
                $image_name_db = $clean_name . '.' . $file_ext;
                $updImg = $conn->prepare("UPDATE products SET image = ? WHERE product_id = ?");
                $updImg->bind_param("si", $image_name_db, $new_product_id);
                $updImg->execute();
                $updImg->close();
            }
        }

        // D. Handle Variants
        $variants_json = $_POST['variants_data'] ?? '[]';
        $variants = json_decode($variants_json, true);
        $vStmt = $conn->prepare("INSERT INTO product_variant (product_id, color, size, quantity) VALUES (?, ?, ?, ?)");

        if (!empty($variants)) {
            foreach ($variants as $var) {
                $vStmt->bind_param("issi", $new_product_id, $var['color'], $var['size'], $var['qty']);
                $vStmt->execute();
            }
        } else {
            // Default
            $def = ["#000000", "One Size", 0];
            $vStmt->bind_param("issi", $new_product_id, $def[0], $def[1], $def[2]);
            $vStmt->execute();
        }
        $vStmt->close();

        $conn->commit();
        ob_end_clean();
        header("Location: inventory.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
}

// ==========================================
// 2. UNIFIED HANDLE: UPDATE (EDIT + ADD NEW VARIANTS)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_existing') {
    $conn->begin_transaction();
    try {
        $pid = (int) $_POST['product_id'];
        $new_price = (float) $_POST['product_price'];

        // 1. Update Base Product
        $stmt = $conn->prepare("UPDATE products SET price = ? WHERE product_id = ? AND supplier_id = ?");
        $stmt->bind_param("dii", $new_price, $pid, $supplier_id);
        $stmt->execute();
        $stmt->close();

        // 2. Update Existing Variants Stock
        if (isset($_POST['stock']) && is_array($_POST['stock'])) {
            $updateStmt = $conn->prepare("UPDATE product_variant SET quantity = ? WHERE variant_id = ?");
            foreach ($_POST['stock'] as $vid => $qty) {
                $vid = (int) $vid;
                $qty = (int) $qty;
                $updateStmt->bind_param("ii", $qty, $vid);
                $updateStmt->execute();
            }
            $updateStmt->close();
        }

        // 3. Insert NEW Variants (from the Staging Queue)
        if (!empty($_POST['new_variants_json'])) {
            $new_vars = json_decode($_POST['new_variants_json'], true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($new_vars)) {
                $insVar = $conn->prepare("INSERT INTO product_variant (product_id, color, size, quantity) VALUES (?, ?, ?, ?)");
                foreach ($new_vars as $nv) {
                    $insVar->bind_param("issi", $pid, $nv['color'], $nv['size'], $nv['qty']);
                    $insVar->execute();
                }
                $insVar->close();
            }
        }

        $conn->commit();
        ob_end_clean();
        header("Location: inventory.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Error updating: " . $e->getMessage());
    }
}

// --- DATA FETCHING ---
// Categories
$catQuery = $conn->prepare("SELECT category_id, category_name FROM category WHERE supplier_id = ?");
$catQuery->bind_param("i", $supplier_id);
$catQuery->execute();
$categories = $catQuery->get_result()->fetch_all(MYSQLI_ASSOC);
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
            'image' => $row['image'] ?: 'placeholder.png',
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

// Pagination
$total_items = count($my_products);
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$offset = ($page - 1) * $items_per_page;
$paginated_products = array_slice($my_products, $offset, $items_per_page);
$total_pages = ceil($total_items / $items_per_page);

// Stats
$total_value = 0;
$low_stock_count = 0;
foreach ($my_products as $p) {
    $total_value += ($p['price'] * $p['total_stock']);
    if ($p['total_stock'] < 10)
        $low_stock_count++;
}

ob_end_flush();
?>

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
                            <th>Action</th>
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
                        <?php if (empty($paginated_products)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 20px;">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px; text-align:center;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>"
                        style="padding:10px 15px; margin:0 2px; background:<?= $i == $page ? '#333' : 'white' ?>; color:<?= $i == $page ? 'white' : '#333' ?>; text-decoration:none; border-radius:5px; border: 1px solid #ddd;"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>

        <div id="addModal" class="modal-overlay">
            <div class="modal-box">
                <form method="POST" enctype="multipart/form-data" style="display:flex; width:100%;"
                    id="createProductForm" onsubmit="return prepareSubmission()">

                    <input type="hidden" name="action" value="create_full_product">
                    <input type="hidden" name="variants_data" id="hiddenVariantsJson">
                    <input type="hidden" name="new_category_name" id="hiddenNewCatName">

                    <div class="ap-image-section">
                        <div style="margin-bottom:15px; font-weight:600; color:#64748b;">Product Image</div>
                        <div class="image-drop-zone">
                            <input type="file" name="image" class="file-input" accept="image/*" required
                                onchange="previewFile(this)">
                            <div class="upload-msg" id="uploadPlaceholder" style="text-align:center;">
                                <div style="font-size:2rem; margin-bottom:5px;">📷</div>
                                <span style="font-weight:500; color:#64748b;">Click to Upload</span>
                                <br><small style="color:#94a3b8;">PNG, JPG</small>
                            </div>
                            <img id="imagePreview" class="preview-img">
                        </div>
                    </div>

                    <div class="ap-form-section">
                        <div class="ap-header" style="display:flex; justify-content:space-between; margin-bottom:20px;">
                            <h2 style="margin:0;">New Product</h2>
                            <span onclick="closeModal('addModal')"
                                style="cursor:pointer; font-size:1.5rem; color:#888;">&times;</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="product_name" class="input-std" placeholder="e.g. Vintage T-Shirt"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Price ($)</label>
                            <input type="number" step="0.01" name="price" class="input-std" placeholder="0.00" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <div class="pill-container">
                                <?php foreach ($categories as $cat): ?>
                                    <label class="cat-pill-wrapper">
                                        <input type="radio" name="category_id" value="<?= $cat['category_id'] ?>"
                                            class="pill-radio" onclick="clearNewCat()">
                                        <span class="pill-label"><?= htmlspecialchars($cat['category_name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <label id="tempNewCatPill" style="display:none;">
                                    <input type="radio" checked class="pill-radio">
                                    <span class="pill-label" style="background:#333; color:white;"
                                        id="tempNewCatText"></span>
                                    <span onclick="removeNewCat()"
                                        style="margin-left:5px; cursor:pointer;">&times;</span>
                                </label>
                                <div class="pill-add-btn" onclick="toggleCatInput()">+</div>
                            </div>
                            <div class="new-cat-box" id="newCatInputBox">
                                <input type="text" id="inputNewCatName" class="input-std" placeholder="Category Name">
                                <button type="button" class="btn-sm" onclick="stageNewCategory()">Add</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Variants</label>
                            <div style="display:flex; gap:8px; margin-bottom:10px;">
                                <input type="color" id="stageColor" value="#000000"
                                    style="height:38px; width:40px; border:none; cursor:pointer; background:none; padding:0;">
                                <input type="text" id="stageSize" placeholder="Size (e.g. M)" class="input-std"
                                    style="flex:1;">
                                <input type="number" id="stageQty" placeholder="Qty" class="input-std"
                                    style="width:80px;">
                                <button type="button" class="btn-sm" onclick="addVariantToQueue()">+ Add</button>
                            </div>
                            <div
                                style="max-height:150px; overflow-y:auto; border:1px solid var(--border); border-radius:8px;">
                                <table class="var-table">
                                    <tbody id="variantPreviewBody">
                                        <tr>
                                            <td colspan="4" style="text-align:center; padding:15px; color:#94a3b8;">No
                                                variants added.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="input-std" style="height:80px; resize:none;"></textarea>
                        </div>

                        <button type="submit" class="btn-submit">Create Product</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="variantModal" class="modal-overlay">
            <div class="modal-box edit-layout">
                <div class="modal-left">
                    <img id="varModalImg" class="modal-big-img" src="">
                </div>

                <div class="modal-right">
                    <form method="POST" id="editForm" onsubmit="return submitEditForm()">
                        <input type="hidden" name="action" value="update_existing">
                        <input type="hidden" name="product_id" id="varModalId">

                        <input type="hidden" name="new_variants_json" id="editNewVariantsJson">

                        <div class="modal-header">
                            <div>
                                <h2 id="varModalTitle" style="margin:0; font-size:1.5rem;">Product</h2>
                                <small style="color:#64748b;">Update stock & prices</small>
                            </div>
                            <div class="price-input-group">
                                <span style="font-size:1.2rem; color:#64748b;">$</span>
                                <input type="number" step="0.01" name="product_price" id="varModalPrice"
                                    class="price-edit-input">
                            </div>
                        </div>

                        <div style="flex:1; overflow-y:auto; margin-bottom:20px;">
                            <table class="var-table">
                                <thead>
                                    <tr>
                                        <th>Color</th>
                                        <th>Size</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody id="varModalTable">
                                </tbody>
                            </table>
                        </div>

                        <div class="add-variant-box">
                            <div style="font-weight:600; font-size:0.85rem; margin-bottom:8px; color:#475569;">Add New
                                Variant</div>
                            <div class="add-grid">
                                <input type="color" id="editNewColor" value="#000000"
                                    style="width:35px; height:35px; border:none; cursor:pointer; padding:0; background:none;">
                                <input type="text" id="editNewSize" placeholder="Size" class="input-std"
                                    style="padding:6px 10px;">
                                <input type="number" id="editNewQty" placeholder="Qty" class="input-std" min="0"
                                    style="padding:6px 10px;">
                                <button type="button" class="btn-sm" onclick="stageNewVariantOnEdit()">Add</button>
                            </div>
                        </div>

                        <div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" onclick="closeModal('variantModal')"
                                style="padding:10px 20px; background:white; border:1px solid var(--border); border-radius:6px; cursor:pointer;">Cancel</button>
                            <button type="submit" class="btn-main" style="width:auto;">Save Changes</button>
                        </div>
                    </form>
                </div>
                <div style="position:absolute; top:15px; right:15px; cursor:pointer; color:white; background:rgba(0,0,0,0.2); width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center;"
                    onclick="closeModal('variantModal')">&times;</div>
            </div>
        </div>

    </section>

    <script>
        // --- GLOBAL STATE ---
        let variantQueue = [];
        let editVariantQueue = []; // For the edit modal
        let isNewCategoryMode = false;

        // --- MODAL UTILS ---
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

        // --- IMAGE PREVIEW ---
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

        // --- CATEGORY LOGIC ---
        function toggleCatInput() {
            document.getElementById('newCatInputBox').style.display = 'flex';
            document.getElementById('inputNewCatName').focus();
        }
        function stageNewCategory() {
            const name = document.getElementById('inputNewCatName').value.trim();
            if (!name) return;
            document.getElementById('newCatInputBox').style.display = 'none';
            document.getElementById('tempNewCatPill').style.display = 'inline-flex';
            document.getElementById('tempNewCatText').innerText = name;
            document.getElementById('hiddenNewCatName').value = name;
            document.querySelectorAll('input[name="category_id"]').forEach(r => r.checked = false);
            isNewCategoryMode = true;
            document.getElementById('inputNewCatName').value = '';
        }
        function clearNewCat() { if (isNewCategoryMode) removeNewCat(); }
        function removeNewCat() {
            document.getElementById('tempNewCatPill').style.display = 'none';
            document.getElementById('hiddenNewCatName').value = '';
            isNewCategoryMode = false;
        }

        // --- ADD PRODUCT: VARIANT QUEUE ---
        function addVariantToQueue() {
            const color = document.getElementById('stageColor').value;
            const size = document.getElementById('stageSize').value.trim();
            const qty = document.getElementById('stageQty').value;

            if (!size || qty === '') { alert("Please enter Size and Quantity"); return; }

            variantQueue.push({ id: Date.now(), color: color, size: size, qty: parseInt(qty) });
            renderVariantTable();
            document.getElementById('stageSize').value = '';
            document.getElementById('stageQty').value = '';
            document.getElementById('stageSize').focus();
        }
        function removeVariantFromQueue(id) {
            variantQueue = variantQueue.filter(v => v.id !== id);
            renderVariantTable();
        }
        function renderVariantTable() {
            const tbody = document.getElementById('variantPreviewBody');
            tbody.innerHTML = '';
            if (variantQueue.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:15px; color:#94a3b8;">No variants added.</td></tr>';
                return;
            }
            variantQueue.forEach(v => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding-left:10px;"><span style="display:inline-block;width:15px;height:15px;background:${v.color};border-radius:50%;border:1px solid #ddd; vertical-align:middle;"></span></td>
                    <td style="color:#334155;">${v.size}</td>
                    <td style="font-weight:600;">${v.qty}</td>
                    <td style="text-align:right; padding-right:10px;"><span onclick="removeVariantFromQueue(${v.id})" style="color:#ef4444; cursor:pointer; font-weight:bold;">&times;</span></td>
                `;
                tbody.appendChild(tr);
            });
        }
        function prepareSubmission() {
            const existingCat = document.querySelector('input[name="category_id"]:checked');
            const newCat = document.getElementById('hiddenNewCatName').value;
            if (!existingCat && !newCat) { alert("Please select or create a category."); return false; }
            document.getElementById('hiddenVariantsJson').value = JSON.stringify(variantQueue);
            return true;
        }

        // --- EDIT MODAL LOGIC (IMPROVED) ---
        function openVariantModal(p) {
            // Reset Edit Queue
            editVariantQueue = [];

            document.getElementById('varModalTitle').innerText = p.product_name;
            document.getElementById('varModalPrice').value = p.price;
            document.getElementById('varModalId').value = p.product_id;

            const imgPath = p.image ? "../uploads/products/" + p.product_id + '_' + p.image : "placeholder.png";
            document.getElementById('varModalImg').src = imgPath;

            const tbody = document.getElementById('varModalTable');
            tbody.innerHTML = '';

            // Render Existing DB Variants
            if (p.variants && p.variants.length > 0) {
                p.variants.forEach(v => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><span style="display:inline-block;width:16px;height:16px;background:${v.color};border-radius:50%;margin-right:8px;vertical-align:middle;border:1px solid #ddd;"></span>${v.color}</td>
                        <td style="color:#475569;">${v.size}</td>
                        <td><input type="number" name="stock[${v.variant_id}]" value="${v.quantity}" class="qty-box" min="0"></td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr id="noExistingVars"><td colspan="3" style="text-align:center;color:#94a3b8; padding:10px;">No existing variants.</td></tr>';
            }
            openModal('variantModal');
        }

        // Logic for "Adding to Preview" in Edit Mode
        function stageNewVariantOnEdit() {
            const color = document.getElementById('editNewColor').value;
            const size = document.getElementById('editNewSize').value.trim();
            const qty = document.getElementById('editNewQty').value;

            if (!size || qty === '') { alert("Enter size and quantity"); return; }

            // Add to temp queue
            const tempId = Date.now();
            editVariantQueue.push({ id: tempId, color: color, size: size, qty: parseInt(qty) });

            // Visual Append
            const tbody = document.getElementById('varModalTable');
            const noMsg = document.getElementById('noExistingVars');
            if (noMsg) noMsg.remove();

            const row = document.createElement('tr');
            row.style.background = "#f0f9ff"; // Highlight new row
            row.innerHTML = `
                <td>
                    <span style="display:inline-block;width:16px;height:16px;background:${color};border-radius:50%;margin-right:8px;vertical-align:middle;border:1px solid #ddd;"></span>
                    <span class="new-row-badge">NEW</span>
                </td>
                <td style="color:#475569;">${size}</td>
                <td style="font-weight:700; color:#2563eb;">${qty}</td>
            `;
            tbody.appendChild(row);

            // Clear inputs
            document.getElementById('editNewSize').value = '';
            document.getElementById('editNewQty').value = '';
            document.getElementById('editNewSize').focus();
        }

        function submitEditForm() {
            // Put the queue into the hidden input
            document.getElementById('editNewVariantsJson').value = JSON.stringify(editVariantQueue);
            return true; // proceed with submit
        }
    </script>
</body>

</html>