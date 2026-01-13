<?php
ob_start(); // Buffer output to prevent "Headers already sent" errors during redirects
session_start();

// Assuming nav.php contains your database connection ($conn)
include("partials/nav.php"); 

// --- CONFIGURATION ---
$items_per_page = 6;
// Ensure session variable exists, fallback to a default for testing
$supplier_id = $_SESSION['supplierid'] ?? 6; 

// ENABLE ERROR REPORTING
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ==========================================
// 1. UNIFIED HANDLE: ADD PRODUCT + VARIANTS + CATEGORY
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_full_product') {

    $conn->begin_transaction();

    try {
        $p_name = $_POST['product_name'];
        $p_desc = $_POST['description'];
        $p_price = (float) $_POST['price'];

        // --- A. HANDLE CATEGORY ---
        $final_cat_id = null;

        if (!empty($_POST['new_category_name'])) {  
            $new_cat_name = trim($_POST['new_category_name']);

            // Check existing
            $checkCat = $conn->prepare("SELECT category_id FROM category WHERE category_name = ? AND supplier_id = ?");
            $checkCat->bind_param("si", $new_cat_name, $supplier_id);
            $checkCat->execute();
            $checkCat->store_result();

            if ($checkCat->num_rows > 0) {
                $checkCat->bind_result($existing_id);
                $checkCat->fetch();
                $final_cat_id = $existing_id;
            } else {
                // Insert New
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

        if (!$final_cat_id) {
            throw new Exception("Category selection is required.");
        }

        // --- B. INSERT PRODUCT (First pass with placeholder image) ---
        // We do this BEFORE file upload to get the generated product_id
        $temp_image_name = 'placeholder.png'; 
        
        $stmt = $conn->prepare("INSERT INTO products (supplier_id, category_id, product_name, description, price, image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'available', NOW())");
        // Correct types: supplier_id (i), category_id (i), product_name (s), description (s), price (d), image (s)
        $stmt->bind_param("iissds", $supplier_id, $final_cat_id, $p_name, $p_desc, $p_price, $temp_image_name);
        $stmt->execute();
        
        $new_product_id = $conn->insert_id; // WE HAVE THE ID NOW
        $stmt->close();

        // --- C. HANDLE IMAGE UPLOAD (Now that we have the ID) ---
        if (isset($_FILES['image']) && isset($_FILES['image']['error']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

            $upload_dir = __DIR__ . '/../uploads/products/';

            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
                    throw new Exception("Unable to create upload directory: " . $upload_dir);
                }
            }

            if (!is_writable($upload_dir)) {
                throw new Exception("Server cannot write to directory: " . $upload_dir);
            }

            if (!is_uploaded_file($_FILES['image']['tmp_name'])) {
                throw new Exception("Upload failed: temporary file missing.");
            }

            $original_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception("Invalid image type. Allowed: jpg, jpeg, png, webp.");
            }

            // Clean product name to build a readable filename
            $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', $p_name);
            $clean_name = substr($clean_name, 0, 20);

            // Ensure unique final filename (avoid collisions)
            $base_final = $new_product_id . "_" . $clean_name;
            $final_filename = $base_final . '.' . $file_ext;
            $target_file = $upload_dir . $final_filename;
            if (file_exists($target_file)) {
                $final_filename = $base_final . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . $final_filename;
            }

            // The DB stores the part after the id_ (so display path is product_id + '_' + image)
            $image_name = $clean_name . '.' . $file_ext;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                throw new Exception("Failed to move uploaded file to destination: " . $target_file);
            }

            // Set permissive read for webserver (best-effort)
            @chmod($target_file, 0644);

            $updImg = $conn->prepare("UPDATE products SET image = ? WHERE product_id = ?");
            $updImg->bind_param("si", $image_name, $new_product_id);
            $updImg->execute();
            $updImg->close();
        }

        // --- D. HANDLE VARIANTS ---
        $variants_json = $_POST['variants_data'] ?? '[]';
        $variants = json_decode($variants_json, true);

        $vStmt = $conn->prepare("INSERT INTO product_variant (product_id, color, size, quantity) VALUES (?, ?, ?, ?)");

        if (json_last_error() === JSON_ERROR_NONE && !empty($variants)) {
            foreach ($variants as $var) {
                $vColor = $var['color'];
                $vSize = $var['size'];
                $vQty = (int) $var['qty'];
                $vStmt->bind_param("issi", $new_product_id, $vColor, $vSize, $vQty);
                $vStmt->execute();
            }
        } else {
            // Fallback default variant
            $defC = "#000000";
            $defS = "One Size";
            $defQ = 0;
            $vStmt->bind_param("issi", $new_product_id, $defC, $defS, $defQ);
            $vStmt->execute();
        }
        $vStmt->close();

        // All good? Commit.
        $conn->commit();
        
        // Clear buffer and redirect
        ob_end_clean(); 
        header("Location: inventory.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("<h3>Error Occurred:</h3><p>" . $e->getMessage() . "</p><p><a href='inventory.php'>Go Back</a></p>");
    }
}

// ==========================================
// 2. HANDLE UPDATES (Edit Modal - Price & Stock)
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
            $vid = (int)$vid;
            $qty = (int)$qty;
            $updateStmt->bind_param("ii", $qty, $vid);
            $updateStmt->execute();
        }
        $updateStmt->close();
    }
    ob_end_clean();
    header("Location: inventory.php");
    exit;
}

// ==========================================
// 3. HANDLE ADDING NEW VARIANT (Edit Modal)
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
    ob_end_clean();
    header("Location: inventory.php");
    exit;
}

// --- DATA FETCHING ---
// 1. Categories
$catQuery = $conn->prepare("SELECT category_id, category_name FROM category WHERE supplier_id = ?");
$catQuery->bind_param("i", $supplier_id);
$catQuery->execute();
$categories_result = $catQuery->get_result();
$categories = [];
while ($c = $categories_result->fetch_assoc()) {
    $categories[] = $c;
}
$catQuery->close();

// 2. Products
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
            'image' => $row['image'] ?: 'placeholder.png', // Fallback image
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

// Flush buffer to output HTML
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
                                        <img src="../uploads/products/<?= $p['product_id']?>_<?= $p['image'] ?>" 
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
                        <?php if(empty($paginated_products)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 20px;">No products found.</td></tr>
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
            <div class="modal-box add-product-container">
                <form method="POST" enctype="multipart/form-data" style="display:contents;" id="createProductForm"
                    onsubmit="return prepareSubmission()">

                    <input type="hidden" name="action" value="create_full_product">
                    <input type="hidden" name="variants_data" id="hiddenVariantsJson">
                    <input type="hidden" name="new_category_name" id="hiddenNewCatName">

                    <div class="ap-image-section">
                        <div style="margin-bottom:10px; font-weight:600; color:#555;">Product Image</div>
                        <div class="image-drop-zone">
                            <input type="file" name="image" class="file-input" accept="image/*" required
                                onchange="previewFile(this)">
                            <div class="upload-msg" id="uploadPlaceholder">
                                <div style="font-size:2rem; margin-bottom:10px;">📷</div>
                                <span>Click to Upload</span>
                                <br><small style="color:#aaa">PNG, JPG</small>
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

                        <div class="form-group">
                            <label class="form-label">Price ($)</label>
                            <input type="number" step="0.01" name="price" class="input-std" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <div class="pill-container" id="categoryPillContainer">
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
                                        style="margin-left:5px; cursor:pointer; color:#ccc;">&times;</span>
                                </label>

                                <div class="pill-add-btn" onclick="toggleCatInput()">+</div>
                            </div>

                            <div class="new-cat-box" id="newCatInputBox">
                                <input type="text" id="inputNewCatName" class="input-std" placeholder="Category Name"
                                    style="padding:5px; height:30px;">
                                <button type="button" class="btn-sm" onclick="stageNewCategory()">Add</button>
                            </div>
                        </div>

                        <hr style="margin: 15px 0; border:0; border-top:1px solid #eee;">

                        <div class="form-group">
                            <label class="form-label">Variants (Add at least one)</label>
                            <div style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                                <input type="color" id="stageColor" value="#000000"
                                    style="height:35px; width:40px; border:none; padding:0; cursor:pointer;">
                                <input type="text" id="stageSize" placeholder="Size (e.g. L)" class="input-std"
                                    style="flex:1;">
                                <input type="number" id="stageQty" placeholder="Qty" class="input-std"
                                    style="width:70px;">
                                <button type="button" class="btn-sm" style="background:#444; color:white; height:38px;"
                                    onclick="addVariantToQueue()">+ Add</button>
                            </div>

                            <div style="max-height:120px; overflow-y:auto; border:1px solid #eee; border-radius:6px;">
                                <table style="width:100%; font-size:0.85rem; border-collapse:collapse;">
                                    <thead style="background:#f9f9f9; position:sticky; top:0;">
                                        <tr style="text-align:left;">
                                            <th style="padding:5px 10px;">Color</th>
                                            <th style="padding:5px 10px;">Size</th>
                                            <th style="padding:5px 10px;">Qty</th>
                                            <th style="padding:5px 10px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="variantPreviewBody">
                                        <tr id="noVarMsg">
                                            <td colspan="4" style="text-align:center; padding:10px; color:#999;">No
                                                variants added yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="input-std" style="height:60px; resize:none;"
                                placeholder="Product details..."></textarea>
                        </div>

                        <button type="submit" class="btn-submit">Create Product</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="variantModal" class="modal-overlay">
            <div class="modal-box">
                <div style="position:absolute; top:20px; right:20px; cursor:pointer; font-size:1.5rem; z-index:10;"
                    onclick="closeModal('variantModal')">&times;</div>
                <div class="modal-left">
                    <img id="varModalImg" class="modal-big-img" src="">
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
        // --- GLOBAL STATE ---
        let variantQueue = [];
        let isNewCategoryMode = false;

        // --- MODAL UTILS ---
        function openModal(id) {
            const el = document.getElementById(id);
            if(el) {
                el.style.display = 'flex';
                setTimeout(() => el.classList.add('open'), 10);
            }
        }
        function closeModal(id) {
            const el = document.getElementById(id);
            if(el) {
                el.classList.remove('open');
                setTimeout(() => el.style.display = 'none', 300);
            }
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

        // --- CATEGORY STAGING LOGIC ---
        function toggleCatInput() {
            document.getElementById('newCatInputBox').style.display = 'flex';
            document.getElementById('inputNewCatName').focus();
        }
        function stageNewCategory() {
            const name = document.getElementById('inputNewCatName').value.trim();
            if (!name) return;

            // UI Toggle
            document.getElementById('newCatInputBox').style.display = 'none';
            document.getElementById('tempNewCatPill').style.display = 'inline-flex';
            document.getElementById('tempNewCatText').innerText = name;

            // Update Data
            document.getElementById('hiddenNewCatName').value = name;

            // Deselect existing
            const radios = document.querySelectorAll('input[name="category_id"]');
            radios.forEach(r => r.checked = false);

            isNewCategoryMode = true;
            document.getElementById('inputNewCatName').value = '';
        }
        function clearNewCat() {
            if (isNewCategoryMode) removeNewCat();
        }
        function removeNewCat() {
            document.getElementById('tempNewCatPill').style.display = 'none';
            document.getElementById('hiddenNewCatName').value = '';
            isNewCategoryMode = false;
        }

        // --- VARIANT QUEUE LOGIC ---
        function addVariantToQueue() {
            const color = document.getElementById('stageColor').value;
            const size = document.getElementById('stageSize').value.trim();
            const qty = document.getElementById('stageQty').value;

            if (!size || qty === '') {
                alert("Please enter Size and Quantity");
                return;
            }

            variantQueue.push({ id: Date.now(), color: color, size: size, qty: parseInt(qty) });
            renderVariantTable();

            // Reset
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
                tbody.innerHTML = '<tr id="noVarMsg"><td colspan="4" style="text-align:center; padding:10px; color:#999;">No variants added yet.</td></tr>';
                return;
            }
            variantQueue.forEach(v => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #eee';
                tr.innerHTML = `
                    <td style="padding:5px 10px;"><span style="display:inline-block;width:12px;height:12px;background:${v.color};border-radius:50%;border:1px solid #ddd;"></span></td>
                    <td style="padding:5px 10px; color:#555;">${v.size}</td>
                    <td style="padding:5px 10px; font-weight:600;">${v.qty}</td>
                    <td style="padding:5px 10px; text-align:right;"><span onclick="removeVariantFromQueue(${v.id})" style="color:red; cursor:pointer; font-weight:bold;">&times;</span></td>
                `;
                tbody.appendChild(tr);
            });
        }

        // --- FORM SUBMIT VALIDATION ---
        function prepareSubmission() {
            // 1. Check Category
            const existingCat = document.querySelector('input[name="category_id"]:checked');
            const newCat = document.getElementById('hiddenNewCatName').value;
            if (!existingCat && !newCat) {
                alert("Please select or create a category.");
                return false;
            }
            // 2. Check Variants
            if (variantQueue.length === 0) {
                if (!confirm("No variants added. A default 'One Size' variant with 0 stock will be created. Continue?")) {
                    return false;
                }
            }
            // 3. Serialize
            document.getElementById('hiddenVariantsJson').value = JSON.stringify(variantQueue);

            console.log("Here")
            return true;
        }

        // --- EDIT EXISTING VARIANT MODAL LOGIC ---
        function openVariantModal(p) {
            document.getElementById('varModalTitle').innerText = p.product_name;
            document.getElementById('varModalPrice').value = p.price;
            document.getElementById('varModalId').value = p.product_id;
            document.getElementById('addVarId').value = p.product_id;
            
            // Fix image source path
            const imgPath = p.image ? "../uploads/products/" + p.product_id + '_' + p.image : "https://via.placeholder.com/300";
            document.getElementById('varModalImg').src = imgPath;

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