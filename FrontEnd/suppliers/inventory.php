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
// 2. UNIFIED HANDLE: UPDATE (EDIT + DELETE VARIANTS + STATUS)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_existing') {
    $conn->begin_transaction();
    try {
        $pid = (int) $_POST['product_id'];
        $new_price = (float) $_POST['product_price'];
        $new_status = $_POST['product_status']; // 'available' or 'unavailable'

        // 1. Update Base Product
        $stmt = $conn->prepare("UPDATE products SET price = ?, status = ? WHERE product_id = ? AND supplier_id = ?");
        $stmt->bind_param("dsii", $new_price, $new_status, $pid, $supplier_id);
        $stmt->execute();
        $stmt->close();

        // 2. Delete Marked Variants
        if (!empty($_POST['deleted_variants_ids'])) {
            $ids_to_delete = explode(',', $_POST['deleted_variants_ids']);
            $ids_to_delete = array_map('intval', $ids_to_delete);

            if (!empty($ids_to_delete)) {
                $in_clause = implode(',', $ids_to_delete);
                $conn->query("DELETE FROM cart WHERE variant_id IN ($in_clause)");
                $conn->query("DELETE FROM product_variant WHERE variant_id IN ($in_clause) AND product_id = $pid");
            }
        }

        // 3. Update Existing Variants Stock
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

        // 4. Insert NEW Variants
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
$catQuery = $conn->prepare("SELECT category_id, category_name FROM category WHERE supplier_id = ?");
$catQuery->bind_param("i", $supplier_id);
$catQuery->execute();
$categories = $catQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$catQuery->close();

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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link rel="stylesheet" href="supplierCss.css">
    <style>
        /* --- CORE STYLES & OVERRIDES --- */
        :root {
            --primary: #2563eb;
            /* Standard Dashboard Blue */
            --primary-grad: linear-gradient(135deg, #2563eb, #1d4ed8);
            --bg: #f8f9fa;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
            --text-light: #64748b;
            --danger: #ef4444;
            --success: #22c55e;
            --modal-radius: 12px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
        }

        /* --- PAGE LAYOUT --- */
        .page-container {
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--surface);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border);
        }

        .stat-title {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-top: 5px;
            color: var(--text);
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-main {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }

        .btn-main:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .inventory-panel {
            background: var(--surface);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table th {
            background: #f8fafc;
            text-align: left;
            padding: 16px 24px;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        .custom-table td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            color: #334155;
        }

        .custom-table tr:hover {
            background: #f8fafc;
            cursor: pointer;
        }

        .product-flex {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .thumb-img {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--border);
            background: #eee;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge.ok {
            background: #dcfce7;
            color: #15803d;
        }

        .badge.low {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* --- MODALS --- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.open {
            opacity: 1;
        }

        .modal-box {
            background: white;
            width: 1000px;
            max-width: 95vw;
            height: 85vh;
            border-radius: var(--modal-radius);
            display: flex;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.98);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .modal-overlay.open .modal-box {
            transform: scale(1);
        }

        .modal-sidebar {
            width: 300px;
            background: #f8fafc;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            flex-shrink: 0;
        }

        .modal-content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* -- HEADER STYLES (NEW VS OLD) -- */
        /* New Product Header (Gradient) */
        .modal-header-new {
            padding: 24px 32px;
            background: var(--primary-grad);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header-new .modal-title {
            color: white;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .modal-header-new .modal-close {
            color: rgba(255, 255, 255, 0.8);
        }

        .modal-header-new .modal-close:hover {
            color: white;
        }

        /* Old Edit Header (Standard) */
        .modal-header-edit {
            padding: 24px 32px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header-edit .modal-title {
            color: #0f172a;
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .modal-close {
            cursor: pointer;
            font-size: 1.8rem;
            line-height: 1;
            transition: 0.2s;
        }

        .modal-body-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 32px;
        }

        .modal-footer {
            padding: 20px 32px;
            border-top: 1px solid var(--border);
            background: #fff;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-shrink: 0;
        }

        /* --- FORM ELEMENTS --- */
        .form-section-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 16px;
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            color: #334155;
        }

        .input-std {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #0f172a;
            transition: all 0.2s ease;
            box-sizing: border-box;
            background: #fff;
        }

        .input-std:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* IMAGE FIX */
        .image-drop-zone {
            width: 100%;
            height: auto;
            min-height: 250px;
            /* Fixed min height for consistency */
            aspect-ratio: 1;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: 0.2s;
            background: white;
        }

        .image-drop-zone:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }

        .preview-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            /* Ensures image fits nicely */
            border-radius: 12px;
            display: none;
            padding: 10px;
            /* Padding so image doesn't touch dashed border */
            box-sizing: border-box;
            background: white;
        }

        .file-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 5;
        }

        /* --- NEW: SIZE CHIPS & STEPPER --- */
        .size-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .size-chip {
            min-width: 40px;
            height: 40px;
            padding: 0 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            transition: 0.2s;
            font-size: 0.85rem;
        }

        .size-chip.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }

        .qty-stepper {
            display: flex;
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
            overflow: hidden;
            width: 120px;
        }

        .qty-btn {
            width: 36px;
            height: 38px;
            border: none;
            background: #f1f5f9;
            color: #475569;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            background: #e2e8f0;
        }

        .qty-input-real {
            flex: 1;
            border: none;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
            color: #0f172a;
            height: 38px;
            width: 40px;
            outline: none;
        }

        /* Color Picker Styling */
        .color-wrapper {
            position: relative;
            width: 100%;
            height: 40px;
        }

        .color-display {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: white;
            display: flex;
            align-items: center;
            padding: 0 10px;
            box-sizing: border-box;
            gap: 10px;
        }

        .color-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .color-input-hidden {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        /* Variant Grid for Add Modal */
        .variant-creator-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }

        .added-variants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }

        .variant-card-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .variant-card-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--card-color, #000);
        }

        .btn-add-var {
            background: #1e293b;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0 20px;
            height: 40px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* --- ORIGINAL EDIT MODAL STYLES --- */
        .var-table {
            width: 100%;
            font-size: 0.9rem;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .var-table th {
            text-align: left;
            color: #64748b;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .var-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .qty-box {
            width: 70px;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            text-align: center;
        }

        .variant-input-group {
            display: grid;
            grid-template-columns: auto 1fr 100px auto;
            gap: 12px;
            align-items: center;
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        /* Standard Buttons */
        .btn-secondary {
            background: #fff;
            border: 1px solid #cbd5e1;
            color: #475569;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
        }

        .btn-secondary:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .btn-create {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 32px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        .btn-create:hover {
            background: #1d4ed8;
        }

        /* Toggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            vertical-align: middle;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #22c55e;
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }

        .btn-icon-danger {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fee2e2;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        /* Category Pills */
        .pill-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .pill-radio {
            display: none;
        }

        .pill-label {
            padding: 8px 16px;
            background: white;
            border-radius: 50px;
            font-size: 0.9rem;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            transition: all 0.2s;
            color: #64748b;
            font-weight: 500;
        }

        .pill-radio:checked+.pill-label {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pill-add-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px dashed #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
        }

        .new-cat-box {
            display: none;
            gap: 8px;
            margin-top: 12px;
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
                <div class="stat-card" style="border-bottom: 4px solid <?= $low_stock_count > 0 ? 'var(--danger)' : 'var(--success)' ?>">
                    <span class="stat-title">Low Stock Alerts</span>
                    <div class="stat-value"><?= $low_stock_count ?></div>
                </div>
            </div>

            <div class="action-bar">
                <div>
                    <h1 style="margin:0; font-weight:300; font-size:2rem; color:#0f172a;">Product <b>Inventory</b></h1>
                    <small style="color:#64748b;">Managing Supplier ID: <?= $supplier_id ?></small>
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
                                        <img src="../uploads/products/<?= $p['product_id'] ?>_<?= $p['image'] ?>" class="thumb-img">
                                        <div>
                                            <div style="font-weight:600; color:#0f172a;"><?= htmlspecialchars($p['product_name']) ?></div>
                                            <small style="color:#94a3b8;">SKU: <?= $p['product_id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight:600; color:#334155;">$<?= number_format($p['price'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $p['total_stock'] < 10 ? 'low' : 'ok' ?>">
                                        <?= $p['total_stock'] ?> Units
                                    </span>
                                </td>
                                <td><span style="color:<?= $p['status'] == 'available' ? '#22c55e' : '#cbd5e1' ?>; font-size:1.2em; vertical-align:middle;">•</span> <?= ucfirst($p['status']) ?></td>
                                <td style="color:#64748b; font-weight:500;">Edit <span style="font-size:0.8em;">✏️</span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($paginated_products)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 40px; color:#94a3b8;">No products found in inventory.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px; text-align:center;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" style="padding:8px 14px; margin:0 3px; background:<?= $i == $page ? 'var(--primary)' : 'white' ?>; color:<?= $i == $page ? 'white' : 'var(--primary)' ?>; text-decoration:none; border-radius:6px; border: 1px solid #e2e8f0; font-size:0.9rem; font-weight:500;"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>

        <div id="addModal" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-sidebar">
                    <div style="text-align:center; margin-bottom:15px;">
                        <div style="font-weight:600; color:#334155; margin-bottom:5px;">Product Image</div>
                        <small style="color:#94a3b8;">Primary display image</small>
                    </div>
                    <div class="image-drop-zone">
                        <input type="file" name="image" form="createProductForm" class="file-input" accept="image/*" required onchange="previewFile(this)">
                        <div class="upload-msg" id="uploadPlaceholder" style="text-align:center; pointer-events:none;">
                            <div style="font-size:2rem; margin-bottom:10px;">📷</div>
                            <span style="font-weight:600; color:#64748b; font-size:0.9rem;">Upload Photo</span>
                        </div>
                        <img id="imagePreview" class="preview-img">
                    </div>
                </div>

                <div class="modal-content-area">
                    <form method="POST" id="createProductForm" enctype="multipart/form-data" style="display:flex; flex-direction:column; height:100%;" onsubmit="return prepareSubmission()">
                        <input type="hidden" name="action" value="create_full_product">
                        <input type="hidden" name="variants_data" id="hiddenVariantsJson">
                        <input type="hidden" name="new_category_name" id="hiddenNewCatName">

                        <div class="modal-header-new">
                            <h2 class="modal-title">Add New Product</h2>
                            <span class="modal-close" onclick="closeModal('addModal')">&times;</span>
                        </div>

                        <div class="modal-body-scroll">
                            <div class="form-section-title">General Info</div>
                            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
                                <div class="form-group">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" name="product_name" class="input-std" placeholder="e.g. Classic Cotton Tee" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Price</label>
                                    <div style="position:relative;">
                                        <span style="position:absolute; left:12px; top:10px; color:#64748b;">$</span>
                                        <input type="number" step="0.01" name="price" class="input-std" style="padding-left:25px;" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="input-std" placeholder="Describe the product details, material, and fit..."></textarea>
                            </div>

                            <div class="form-section-title">Category</div>
                            <div class="form-group">
                                <div class="pill-container">
                                    <?php foreach ($categories as $cat): ?>
                                        <label>
                                            <input type="radio" name="category_id" value="<?= $cat['category_id'] ?>" class="pill-radio" onclick="clearNewCat()">
                                            <span class="pill-label"><?= htmlspecialchars($cat['category_name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                    <label id="tempNewCatPill" style="display:none;">
                                        <input type="radio" checked class="pill-radio">
                                        <span class="pill-label" style="background:#0f172a; color:white; border-color:#0f172a;">
                                            <span id="tempNewCatText"></span>
                                            <span onclick="removeNewCat()" style="margin-left:8px; cursor:pointer; opacity:0.7;">&times;</span>
                                        </span>
                                    </label>
                                    <div class="pill-add-btn" onclick="toggleCatInput()" title="Add New Category">+</div>
                                </div>
                                <div class="new-cat-box" id="newCatInputBox">
                                    <input type="text" id="inputNewCatName" class="input-std" style="width:200px;" placeholder="New Category Name">
                                    <button type="button" class="btn-secondary" style="padding:10px 15px;" onclick="stageNewCategory()">Add</button>
                                </div>
                            </div>

                            <div class="form-section-title">Variants & Stock</div>
                            <div class="variant-creator-card">
                                <div style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">
                                    <div style="flex:2; min-width:200px;">
                                        <label class="form-label">Size</label>
                                        <div class="size-chips">
                                            <div class="size-chip" onclick="selectSize(this, 'S')">S</div>
                                            <div class="size-chip" onclick="selectSize(this, 'M')">M</div>
                                            <div class="size-chip" onclick="selectSize(this, 'L')">L</div>
                                            <div class="size-chip" onclick="selectSize(this, 'XL')">XL</div>
                                            <input type="text" id="stageSize" class="input-std" style="width:80px; padding:0 10px; height:40px;" placeholder="Custom">
                                        </div>
                                    </div>
                                    <div style="flex:1; min-width:120px;">
                                        <label class="form-label">Color</label>
                                        <div class="color-wrapper">
                                            <input type="color" id="stageColor" value="#000000" class="color-input-hidden" onchange="updateColorDisplay(this)">
                                            <div class="color-display">
                                                <div id="colorCircle" class="color-circle" style="background:#000;"></div>
                                                <span id="colorHexText" style="font-size:0.85rem; color:#64748b;">#000000</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="flex:0 0 auto;">
                                        <label class="form-label">Quantity</label>
                                        <div class="qty-stepper">
                                            <button type="button" class="qty-btn" onclick="adjustQty(-1)">-</button>
                                            <input type="number" id="stageQty" class="qty-input-real" value="1" min="1">
                                            <button type="button" class="qty-btn" onclick="adjustQty(1)">+</button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-add-var" onclick="addVariantToQueue()">+ Add</button>
                                </div>

                                <div id="variantPreviewBody" class="added-variants-grid">
                                </div>
                                <div id="noVariantsMsg" style="text-align:center; color:#94a3b8; font-size:0.9rem; margin-top:10px;">
                                    No variants added. Default "One Size" will be created.
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                            <button type="submit" class="btn-create">Publish Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="variantModal" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-sidebar">
                    <img id="varModalImg" style="width:100%; height:auto; object-fit:contain; max-height:400px;" src="">
                </div>

                <div class="modal-content-area">
                    <form method="POST" id="editForm" onsubmit="return submitEditForm()" style="display:flex; flex-direction:column; height:100%;">
                        <input type="hidden" name="action" value="update_existing">
                        <input type="hidden" name="product_id" id="varModalId">
                        <input type="hidden" name="new_variants_json" id="editNewVariantsJson">
                        <input type="hidden" name="deleted_variants_ids" id="editDeletedVariantsIds">
                        <input type="hidden" name="product_status" id="editProductStatus">

                        <div class="modal-header-edit">
                            <div>
                                <h2 id="varModalTitle" class="modal-title">Product Name</h2>
                                <div style="display:flex; align-items:center; margin-top:5px;">
                                    <span class="status-label" id="statusLabelText" style="margin-right:10px; font-weight:600; color:#22c55e;">Available</span>
                                    <label class="switch">
                                        <input type="checkbox" id="statusToggle" onchange="toggleStatus(this)">
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <label style="font-size:0.75rem; color:#64748b; font-weight:600; display:block; margin-bottom:2px;">BASE PRICE</label>
                                <div style="position:relative; display:inline-block;">
                                    <span style="position:absolute; left:0; top:5px; font-weight:bold; color:#64748b;">$</span>
                                    <input type="number" step="0.01" name="product_price" id="varModalPrice" class="input-std" style="width:100px; padding: 5px 5px 5px 15px; border:none; border-bottom:2px solid #e2e8f0; border-radius:0; font-weight:700; font-size:1.2rem; text-align:right;">
                                </div>
                            </div>
                        </div>

                        <div class="modal-body-scroll">
                            <div class="form-section-title">Current Inventory</div>

                            <table class="var-table">
                                <thead>
                                    <tr>
                                        <th>Color</th>
                                        <th>Size</th>
                                        <th>Stock Level</th>
                                        <th style="width:50px;">Delete</th>
                                    </tr>
                                </thead>
                                <tbody id="varModalTable">
                                </tbody>
                            </table>

                            <div style="border-top:1px solid #f1f5f9; margin: 30px 0;"></div>

                            <div class="form-section-title">Add New Variant</div>
                            <div class="variant-input-group" style="background:#fff; border:1px dashed #cbd5e1;">
                                <input type="color" id="editNewColor" value="#000000" style="width:35px; height:35px; border:none; cursor:pointer; background:none;">
                                <input type="text" id="editNewSize" placeholder="New Size" class="input-std">
                                <input type="number" id="editNewQty" placeholder="Qty" class="input-std">
                                <button type="button" class="btn-secondary" onclick="stageNewVariantOnEdit()">+ Add</button>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" onclick="closeModal('variantModal')">Cancel</button>
                            <button type="submit" class="btn-create">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </section>

    <script>
        // --- GLOBAL STATE ---
        let variantQueue = [];
        let editVariantQueue = [];
        let deletedVariantsIds = []; // Stores IDs of variants to delete on save
        let isNewCategoryMode = false;

        // --- MODAL UTILS ---
        function openModal(id) {
            const el = document.getElementById(id);
            el.style.display = 'flex';
            setTimeout(() => el.classList.add('open'), 10);
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            el.classList.remove('open');
            setTimeout(() => el.style.display = 'none', 300);
            document.body.style.overflow = '';
        }
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) closeModal(e.target.id);
        }

        // --- IMAGE PREVIEW ---
        function previewFile(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('imagePreview');
                    const msg = document.getElementById('uploadPlaceholder');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    msg.style.opacity = '0';
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

        function clearNewCat() {
            if (isNewCategoryMode) removeNewCat();
        }

        function removeNewCat() {
            document.getElementById('tempNewCatPill').style.display = 'none';
            document.getElementById('hiddenNewCatName').value = '';
            isNewCategoryMode = false;
        }

        // --- NEW VARIANT UI HELPERS ---
        function selectSize(el, size) {
            document.querySelectorAll('.size-chip').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('stageSize').value = size;
        }

        function adjustQty(amount) {
            const input = document.getElementById('stageQty');
            let current = parseInt(input.value) || 0;
            let newVal = current + amount;
            if (newVal < 1) newVal = 1;
            input.value = newVal;
        }

        function updateColorDisplay(input) {
            document.getElementById('colorCircle').style.background = input.value;
            document.getElementById('colorHexText').innerText = input.value;
        }

        // --- ADD PRODUCT: VARIANT QUEUE ---
        function addVariantToQueue() {
            const color = document.getElementById('stageColor').value;
            const size = document.getElementById('stageSize').value.trim();
            const qty = document.getElementById('stageQty').value;
            if (!size || qty === '') {
                alert("Please enter Size and Quantity");
                return;
            }
            variantQueue.push({
                id: Date.now(),
                color: color,
                size: size,
                qty: parseInt(qty)
            });
            renderVariantGrid();
            // Reset
            document.querySelectorAll('.size-chip').forEach(c => c.classList.remove('active'));
            document.getElementById('stageSize').value = '';
            document.getElementById('stageQty').value = '1';
        }

        function removeVariantFromQueue(id) {
            variantQueue = variantQueue.filter(v => v.id !== id);
            renderVariantGrid();
        }

        function renderVariantGrid() {
            const container = document.getElementById('variantPreviewBody');
            const msg = document.getElementById('noVariantsMsg');
            container.innerHTML = '';
            if (variantQueue.length === 0) {
                msg.style.display = 'block';
                return;
            }
            msg.style.display = 'none';

            variantQueue.forEach(v => {
                const div = document.createElement('div');
                div.className = 'variant-card-item';
                div.style.setProperty('--card-color', v.color);
                div.innerHTML = `
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:#1e293b;">${v.size}</div>
                        <div style="font-size:0.8rem; color:#64748b;">${v.qty} units</div>
                    </div>
                    <div style="width:20px; height:20px; border-radius:50%; background:${v.color}; border:1px solid #ddd; margin:0 10px;"></div>
                    <span onclick="removeVariantFromQueue(${v.id})" style="color:#ef4444; cursor:pointer; font-weight:bold; padding:5px;">&times;</span>
                `;
                container.appendChild(div);
            });
        }

        function prepareSubmission() {
            const existingCat = document.querySelector('input[name="category_id"]:checked');
            const newCat = document.getElementById('hiddenNewCatName').value;
            if (!existingCat && !newCat) {
                alert("Please select or create a category.");
                return false;
            }
            document.getElementById('hiddenVariantsJson').value = JSON.stringify(variantQueue);
            return true;
        }

        // --- EDIT MODAL LOGIC (ORIGINAL) ---
        function openVariantModal(p) {
            editVariantQueue = [];
            deletedVariantsIds = []; // Reset delete queue

            // Populate Basic Info
            document.getElementById('varModalTitle').innerText = p.product_name;
            document.getElementById('varModalPrice').value = p.price;
            document.getElementById('varModalId').value = p.product_id;

            // Status Toggle Logic
            const statusToggle = document.getElementById('statusToggle');
            const isAvailable = p.status === 'available';
            statusToggle.checked = isAvailable;
            updateStatusUI(isAvailable);

            const imgPath = p.image ? "../uploads/products/" + p.product_id + '_' + p.image : "placeholder.png";
            document.getElementById('varModalImg').src = imgPath;

            const tbody = document.getElementById('varModalTable');
            tbody.innerHTML = '';

            // Populate Existing Variants
            if (p.variants && p.variants.length > 0) {
                p.variants.forEach(v => {
                    const row = document.createElement('tr');
                    row.id = 'var-row-' + v.variant_id;
                    row.innerHTML = `
                        <td><div style="display:flex; align-items:center; gap:8px;"><span style="display:inline-block;width:18px;height:18px;background:${v.color};border-radius:50%;border:1px solid #ddd;"></span> ${v.color}</div></td>
                        <td style="color:#475569; font-weight:500;">${v.size}</td>
                        <td><input type="number" name="stock[${v.variant_id}]" value="${v.quantity}" class="qty-box" min="0"></td>
                        <td>
                            <button type="button" class="btn-icon-danger" onclick="markVariantForDeletion(${v.variant_id})" title="Delete Variant">🗑️</button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr id="noExistingVars"><td colspan="4" style="text-align:center;color:#94a3b8; padding:15px;">No existing variants found.</td></tr>';
            }
            openModal('variantModal');
        }

        function toggleStatus(checkbox) {
            updateStatusUI(checkbox.checked);
        }

        function updateStatusUI(isChecked) {
            const label = document.getElementById('statusLabelText');
            const hiddenInput = document.getElementById('editProductStatus');

            if (isChecked) {
                label.innerText = 'Available';
                label.style.color = '#22c55e';
                hiddenInput.value = 'available';
            } else {
                label.innerText = 'Unavailable';
                label.style.color = '#cbd5e1';
                hiddenInput.value = 'unavailable';
            }
        }

        function markVariantForDeletion(variantId) {
            if (!confirm("Are you sure you want to remove this variant? It will be deleted when you click Save.")) return;

            // Add to delete queue
            deletedVariantsIds.push(variantId);

            // Visually hide the row
            const row = document.getElementById('var-row-' + variantId);
            if (row) row.style.display = 'none';
        }

        function stageNewVariantOnEdit() {
            const color = document.getElementById('editNewColor').value;
            const size = document.getElementById('editNewSize').value.trim();
            const qty = document.getElementById('editNewQty').value;

            if (!size || qty === '') {
                alert("Enter size and quantity");
                return;
            }

            const tempId = Date.now();
            editVariantQueue.push({
                id: tempId,
                color: color,
                size: size,
                qty: parseInt(qty)
            });

            const tbody = document.getElementById('varModalTable');
            const noMsg = document.getElementById('noExistingVars');
            if (noMsg) noMsg.remove();

            const row = document.createElement('tr');
            row.style.background = "#eff6ff";
            row.innerHTML = `
                <td>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="display:inline-block;width:18px;height:18px;background:${color};border-radius:50%;border:1px solid #ddd;"></span>
                        <span style="font-size:0.7rem; background:#3b82f6; color:white; padding:2px 6px; border-radius:4px; text-transform:uppercase; font-weight:bold;">New</span>
                    </div>
                </td>
                <td style="color:#1e293b; font-weight:600;">${size}</td>
                <td style="font-weight:700; color:#2563eb;">${qty}</td>
                <td></td>
            `;
            tbody.appendChild(row);

            document.getElementById('editNewSize').value = '';
            document.getElementById('editNewQty').value = '';
            document.getElementById('editNewSize').focus();
        }

        function submitEditForm() {
            document.getElementById('editNewVariantsJson').value = JSON.stringify(editVariantQueue);
            document.getElementById('editDeletedVariantsIds').value = deletedVariantsIds.join(',');
            return true;
        }
    </script>
</body>

</html>