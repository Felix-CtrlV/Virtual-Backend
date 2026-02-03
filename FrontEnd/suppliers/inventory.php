<?php
ob_start();
session_start();

include("partials/nav.php");

// --- CONFIGURATION ---
$items_per_page = 6;
$supplier_id = $_SESSION['supplierid'] ?? 0;
$company_id = $row['company_id'] ?? 0;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
            $checkCat = $conn->prepare("SELECT category_id FROM category WHERE category_name = ? AND company_id = ?");
            $checkCat->bind_param("si", $new_cat_name, $company_id);
            $checkCat->execute();
            $checkCat->store_result();
            if ($checkCat->num_rows > 0) {
                $checkCat->bind_result($existing_id);
                $checkCat->fetch();
                $final_cat_id = $existing_id;
            } else {
                $insCat = $conn->prepare("INSERT INTO category (company_id, category_name) VALUES (?, ?)");
                $insCat->bind_param("is", $company_id, $new_cat_name);
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
        $stmt = $conn->prepare("INSERT INTO products (company_id, category_id, product_name, description, price, image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'available', NOW())");
        $stmt->bind_param("iissds", $company_id, $final_cat_id, $p_name, $p_desc, $p_price, $temp_image_name);
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
        $stmt = $conn->prepare("UPDATE products SET price = ?, status = ? WHERE product_id = ? AND company_id = ?");
        $stmt->bind_param("dsii", $new_price, $new_status, $pid, $company_id);
        $stmt->execute();
        $stmt->close();

        // 2. Soft Delete Marked Variants (Set to Unavailable)
        if (!empty($_POST['deleted_variants_ids'])) {
            $ids_to_delete = explode(',', $_POST['deleted_variants_ids']);
            $ids_to_delete = array_map('intval', $ids_to_delete);

            if (!empty($ids_to_delete)) {
                $in_clause = implode(',', $ids_to_delete);

                // Remove from carts first (optional, but good practice)
                $conn->query("DELETE FROM cart WHERE variant_id IN ($in_clause)");

                // SOFT DELETE: Update status instead of DELETE FROM
                $conn->query("UPDATE product_variant SET status = 'unavailable' WHERE variant_id IN ($in_clause) AND product_id = $pid");
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
$catQuery = $conn->prepare("SELECT category_id, category_name FROM category WHERE company_id = ?");
$catQuery->bind_param("i", $company_id);
$catQuery->execute();
$categories = $catQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$catQuery->close();

// UPDATED QUERY: Added v.status
$productQuery = $conn->prepare('SELECT p.product_id, p.company_id, p.product_name, p.price, p.image, p.status, 
                                     v.variant_id, v.size, v.color, v.quantity, v.status as variant_status
                                FROM products p 
                                LEFT JOIN product_variant v ON p.product_id = v.product_id 
                                WHERE p.company_id = ? ORDER BY p.product_id DESC');
$productQuery->bind_param('i', $company_id);
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
        $v_status = $row['variant_status'] ?? 'available';

        $grouped_products[$pid]['variants'][] = [
            'variant_id' => $row['variant_id'],
            'size' => $row['size'],
            'color' => $row['color'],
            'quantity' => $row['quantity'],
            'status' => $v_status // Store status
        ];

        // Only add to total stock count if available
        if ($v_status === 'available') {
            $grouped_products[$pid]['total_stock'] += $row['quantity'];
        }
    }
}

// SORTING LOGIC: Move 'unavailable' variants to the bottom for every product
foreach ($grouped_products as &$prod) {
    usort($prod['variants'], function ($a, $b) {
        if ($a['status'] === $b['status'])
            return 0;
        return ($a['status'] === 'unavailable') ? 1 : -1;
    });
}
unset($prod); // Break reference

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
            --primary-grad: color-mix(in srgb, var(--primary) 100%, #FFFFFF 10%);
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
            box-sizing: border-box;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        /* --- PAGE LAYOUT --- */
        .page-container {
            margin: 30px auto;
            padding: 0 20px;
            max-width: 1400px;
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
            flex-wrap: wrap;
            gap: 15px;
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
            white-space: nowrap;
        }

        .btn-main:hover {
            background: var(--primary-grad);
            transform: translateY(-1px);
        }

        .inventory-panel {
            background: var(--surface);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            /* Important for table responsiveness */
            width: 100%;
            overflow-x: auto;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
            /* Forces scroll on small screens */
        }

        .custom-table th {
            background: #f8fafc;
            text-align: left;
            padding: 16px 24px;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
            white-space: nowrap;
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
            flex-shrink: 0;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
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
            padding: 10px;
        }

        .modal-overlay.open {
            opacity: 1;
        }

        .modal-box {
            background: white;
            width: 1000px;
            max-width: 100%;
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
            overflow: hidden;
            /* Contains scroll inside */
        }

        /* -- HEADER STYLES -- */
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

        .form-row-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
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
            border-radius: 12px;
            display: none;
            padding: 10px;
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

        .var-input-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
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

        /* ========================================= */
        /* RESPONSIVE MEDIA QUERIES */
        /* ========================================= */
        @media (max-width: 1024px) {
            .modal-box {
                width: 95vw;
                height: 90vh;
            }
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 0 15px;
            }

            /* Stack Action Bar */
            .action-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .action-bar .btn-main {
                width: 100%;
                justify-content: center;
            }

            /* Table Scrolling */
            .inventory-panel {
                overflow-x: auto;
            }

            /* Modal Mobile Layout - Stack Sidebar on Top */
            .modal-box {
                flex-direction: column;
                height: 95vh;
                width: 100vw;
                border-radius: 0;
            }

            .modal-sidebar {
                width: 100%;
                height: auto;
                /* Shrink to fit content */
                max-height: 250px;
                flex-direction: row;
                padding: 15px;
                border-right: none;
                border-bottom: 1px solid var(--border);
                overflow-y: auto;
                gap: 15px;
            }

            .image-drop-zone {
                width: 150px;
                min-height: 150px;
            }

            .modal-content-area {
                width: 100%;
                flex: 1;
                /* Take remaining height */
            }

            .modal-body-scroll {
                padding: 20px;
            }

            .modal-header-new,
            .modal-header-edit {
                padding: 15px 20px;
            }

            /* Stack Form Grid */
            .form-row-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            /* Variant Creator adjustments */
            .var-input-row {
                flex-direction: column;
                align-items: stretch;
            }

            .var-input-row>div {
                flex: none;
                width: 100%;
            }

            .btn-add-var {
                margin-top: 5px;
                width: 100%;
                justify-content: center;
            }

            /* Edit Modal Specifics */
            .modal-header-edit {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            /* Variant Table in Edit */
            .var-table th,
            .var-table td {
                padding: 10px 5px;
                font-size: 0.85rem;
            }

            /* Hide large preview image in Edit Sidebar on mobile to save space */
            #variantModal .modal-sidebar {
                display: none;
            }

            /* Make inputs in edit table grid-like or smaller */
            .variant-input-group {
                grid-template-columns: 1fr 1fr;
                grid-auto-flow: row;
            }

            .variant-input-group button {
                grid-column: span 2;
                width: 100%;
            }
        }

        /* Container for the table to prevent layout overflow */
        <style>
        /* ... (Keep your root variables and basic body styles) ... */

        /* --- MINIMAL TABLE STYLES --- */
        .inventory-panel {
            background: transparent;
            /* Remove container bg to let cards breathe on mobile */
            border: none;
            box-shadow: none;
            width: 100%;
        }

        .custom-table {
            width: 100%;
            border-collapse: separate;
            /* Allows spacing between rows */
            border-spacing: 0 10px;
            /* Space between rows (Desktop) */
        }

        /* Table Head (Desktop) */
        .custom-table thead th {
            background: transparent;
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0 20px 10px 20px;
            border: none;
        }

        /* Table Body Rows (Desktop Card-Row) */
        .custom-table tbody tr {
            background: #ffffff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .custom-table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
        }

        .custom-table td {
            padding: 20px;
            border: none;
            vertical-align: middle;
        }

        /* First and Last Cells Rounding */
        .custom-table td:first-child {
            border-radius: 12px 0 0 12px;
        }

        .custom-table td:last-child {
            border-radius: 0 12px 12px 0;
        }

        /* Product Column Specifics */
        .product-flex {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .thumb-img {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .product-meta h4 {
            margin: 0 0 4px 0;
            font-size: 1rem;
            color: #0f172a;
            font-weight: 600;
        }

        .product-meta span {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge.ok {
            background: #f0fdf4;
            color: #166534;
        }

        .badge.low {
            background: #fef2f2;
            color: #991b1b;
        }

        /* Action Icon */
        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            transition: 0.2s;
        }

        .custom-table tr:hover .action-icon {
            background: #f1f5f9;
            color: var(--primary);
        }

        /* ========================================= */
        /* RESPONSIVE MOBILE VIEW (The Minimal Transform) */
        /* ========================================= */
        @media (max-width: 768px) {
            .custom-table {
                display: block;
            }

            .custom-table thead {
                display: none;
                /* Hide headers */
            }

            .custom-table tbody {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                /* Auto Grid */
                gap: 33px;
            }

            .custom-table tbody tr {
                width: 87vw;
                padding: 16px;
                border: 1px solid #e2e8f0;
                box-shadow: none;
            }

            .custom-table td {
                display: block;
                padding: 0;
                border: none;
            }

            .custom-table td:first-child,
            .custom-table td:last-child {
                border-radius: 0;
            }

            /* 1. Product Info (Image + Title) */
            .custom-table td:nth-child(1) {
                margin-bottom: 15px;
            }

            /* 2. Price (Make big) */
            .custom-table td:nth-child(2) {
                order: 2;
                font-size: 1.2rem;
                margin-bottom: 10px;
            }

            .custom-table td:nth-child(2)::before {
                content: "Price: ";
                font-size: 0.8rem;
                color: #94a3b8;
                font-weight: 500;
                vertical-align: middle;
                margin-right: 5px;
            }

            /* 3. Stock & Status (Row layout) */
            .stock-status-wrapper {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: auto;
                padding-top: 12px;
                border-top: 1px solid #f1f5f9;
            }

            /* Hide original cells for 3 & 4 and reuse in wrapper logic if needed, 
           but CSS-only reordering is tricky with tables. 
           Instead, we style them to sit next to each other visually. */

            .custom-table td:nth-child(3) {
                /* Stock */
                display: inline-block;
                width: auto;
                margin-right: 10px;
            }

            .custom-table td:nth-child(4) {
                /* Status */
                display: inline-block;
                width: auto;
            }

            /* Hide the Action Text/Column on mobile, click card to edit */
            .custom-table td:nth-child(5) {
                display: none;
            }
        }
    </style>
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
                    <h1 style="margin:0; font-weight:300; font-size:2rem; color:#0f172a;">Product <b>Inventory</b></h1>
                    <small style="color:#64748b;">Company ID: <?= (int) ($company_id ?? 0) ?></small>
                </div>
                <button class="btn-main" onclick="openModal('addModal')"><span>+</span> Add Product</button>
            </div>

            <div class="inventory-panel">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Price</th>
                            <th>Stock Level</th>
                            <th>Status</th>
                            <th style="text-align:right">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginated_products as $p): ?>
                            <tr onclick='openVariantModal(<?= json_encode($p) ?>)'>
                                <td>
                                    <div class="product-flex">
                                        <img src="../uploads/products/<?= $p['image'] ? $p['product_id'] . '_' . $p['image'] : 'placeholder.png' ?>"
                                            class="thumb-img">
                                        <div class="product-meta">
                                            <h4><?= htmlspecialchars($p['product_name']) ?></h4>
                                            <span>ID: #<?= $p['product_id'] ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td style="font-weight: 700; color: #334155;">
                                    $<?= number_format($p['price'], 2) ?>
                                </td>

                                <td>
                                    <span class="badge <?= $p['total_stock'] < 10 ? 'low' : 'ok' ?>">
                                        <?= $p['total_stock'] ?> Units
                                    </span>
                                </td>

                                <td>
                                    <div
                                        style="display:flex; align-items:center; gap:6px; font-size:0.9rem; color: #475569;">
                                        <span
                                            style="height:8px; width:8px; border-radius:50%; background:<?= $p['status'] == 'available' ? '#22c55e' : '#cbd5e1' ?>; display:block;"></span>
                                        <?= ucfirst($p['status']) ?>
                                    </div>
                                </td>

                                <td style="text-align:right">
                                    <div class="action-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M9 18l6-6-6-6" />
                                        </svg>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px; text-align:center;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>"
                        style="padding:8px 14px; margin:0 3px; background:<?= $i == $page ? 'var(--primary)' : 'white' ?>; color:<?= $i == $page ? 'white' : 'var(--primary)' ?>; text-decoration:none; border-radius:6px; border: 1px solid #e2e8f0; font-size:0.9rem; font-weight:500;"><?= $i ?></a>
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
                        <input type="file" name="image" form="createProductForm" class="file-input" accept="image/*"
                            required onchange="previewFile(this)">
                        <div class="upload-msg" id="uploadPlaceholder" style="text-align:center; pointer-events:none;">
                            <div style="font-size:2rem; margin-bottom:10px;">📷</div>
                            <span style="font-weight:600; color:#64748b; font-size:0.9rem;">Upload Photo</span>
                        </div>
                        <img id="imagePreview" class="preview-img">
                    </div>
                </div>

                <div class="modal-content-area">
                    <form method="POST" id="createProductForm" enctype="multipart/form-data"
                        style="display:flex; flex-direction:column; height:100%;" onsubmit="return prepareSubmission()">
                        <input type="hidden" name="action" value="create_full_product">
                        <input type="hidden" name="variants_data" id="hiddenVariantsJson">
                        <input type="hidden" name="new_category_name" id="hiddenNewCatName">

                        <div class="modal-header-new">
                            <h2 class="modal-title">Add New Product</h2>
                            <span class="modal-close" onclick="closeModal('addModal')">&times;</span>
                        </div>

                        <div class="modal-body-scroll">
                            <div class="form-section-title">General Info</div>
                            <div class="form-row-grid">
                                <div class="form-group">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" name="product_name" class="input-std"
                                        placeholder="e.g. Classic Cotton Tee" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Price</label>
                                    <div style="position:relative;">
                                        <span style="position:absolute; left:12px; top:10px; color:#64748b;">$</span>
                                        <input type="number" step="0.01" name="price" class="input-std"
                                            style="padding-left:25px;" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="input-std"
                                    placeholder="Describe the product details, material, and fit..."></textarea>
                            </div>

                            <div class="form-section-title">Category</div>
                            <div class="form-group">
                                <div class="pill-container">
                                    <?php foreach ($categories as $cat): ?>
                                        <label>
                                            <input type="radio" name="category_id" value="<?= $cat['category_id'] ?>"
                                                class="pill-radio" onclick="clearNewCat()">
                                            <span class="pill-label"><?= htmlspecialchars($cat['category_name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                    <label id="tempNewCatPill" style="display:none;">
                                        <input type="radio" checked class="pill-radio">
                                        <span class="pill-label"
                                            style="background:#0f172a; color:white; border-color:#0f172a;">
                                            <span id="tempNewCatText"></span>
                                            <span onclick="removeNewCat()"
                                                style="margin-left:8px; cursor:pointer; opacity:0.7;">&times;</span>
                                        </span>
                                    </label>
                                    <div class="pill-add-btn" onclick="toggleCatInput()" title="Add New Category">+
                                    </div>
                                </div>
                                <div class="new-cat-box" id="newCatInputBox">
                                    <input type="text" id="inputNewCatName" class="input-std" style="width:200px;"
                                        placeholder="New Category Name">
                                    <button type="button" class="btn-secondary" style="padding:10px 15px;"
                                        onclick="stageNewCategory()">Add</button>
                                </div>
                            </div>

                            <div class="form-section-title">Variants & Stock</div>
                            <div class="variant-creator-card">
                                <div class="var-input-row">
                                    <div style="flex:2; min-width:200px;">
                                        <label class="form-label">Size</label>
                                        <div class="size-chips">
                                            <div class="size-chip" onclick="selectSize(this, 'S')">S</div>
                                            <div class="size-chip" onclick="selectSize(this, 'M')">M</div>
                                            <div class="size-chip" onclick="selectSize(this, 'L')">L</div>
                                            <div class="size-chip" onclick="selectSize(this, 'XL')">XL</div>
                                            <input type="text" id="stageSize" class="input-std"
                                                style="width:80px; padding:0 10px; height:40px;" placeholder="Custom">
                                        </div>
                                    </div>
                                    <div style="flex:1; min-width:120px;">
                                        <label class="form-label">Color</label>
                                        <div class="color-wrapper">
                                            <input type="color" id="stageColor" value="#000000"
                                                class="color-input-hidden" onchange="updateColorDisplay(this)">
                                            <div class="color-display">
                                                <div id="colorCircle" class="color-circle" style="background:#000;">
                                                </div>
                                                <span id="colorHexText"
                                                    style="font-size:0.85rem; color:#64748b;">#000000</span>
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
                                    <button type="button" class="btn-add-var" onclick="addVariantToQueue()">+
                                        Add</button>
                                </div>

                                <div id="variantPreviewBody" class="added-variants-grid">
                                </div>
                                <div id="noVariantsMsg"
                                    style="text-align:center; color:#94a3b8; font-size:0.9rem; margin-top:10px;">
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
                    <form method="POST" id="editForm" onsubmit="return submitEditForm()"
                        style="display:flex; flex-direction:column; height:100%;">
                        <input type="hidden" name="action" value="update_existing">
                        <input type="hidden" name="product_id" id="varModalId">
                        <input type="hidden" name="new_variants_json" id="editNewVariantsJson">
                        <input type="hidden" name="deleted_variants_ids" id="editDeletedVariantsIds">
                        <input type="hidden" name="product_status" id="editProductStatus">

                        <div class="modal-header-edit">
                            <div>
                                <h2 id="varModalTitle" class="modal-title">Product Name</h2>
                                <div style="display:flex; align-items:center; margin-top:5px;">
                                    <span class="status-label" id="statusLabelText"
                                        style="margin-right:10px; font-weight:600; color:#22c55e;">Available</span>
                                    <label class="switch">
                                        <input type="checkbox" id="statusToggle" onchange="toggleStatus(this)">
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <label
                                    style="font-size:0.75rem; color:#64748b; font-weight:600; display:block; margin-bottom:2px;">BASE
                                    PRICE</label>
                                <div style="position:relative; display:inline-block;">
                                    <span
                                        style="position:absolute; left:0; top:5px; font-weight:bold; color:#64748b;">$</span>
                                    <input type="number" step="0.01" name="product_price" id="varModalPrice"
                                        class="input-std"
                                        style="width:100px; padding: 5px 5px 5px 15px; border:none; border-bottom:2px solid #e2e8f0; border-radius:0; font-weight:700; font-size:1.2rem; text-align:right;">
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
                                <input type="color" id="editNewColor" value="#000000"
                                    style="width:35px; height:35px; border:none; cursor:pointer; background:none;">
                                <input type="text" id="editNewSize" placeholder="New Size" class="input-std">
                                <input type="number" id="editNewQty" placeholder="Qty" class="input-std">
                                <button type="button" class="btn-secondary" onclick="stageNewVariantOnEdit()">+
                                    Add</button>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary"
                                onclick="closeModal('variantModal')">Cancel</button>
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
                    const isUnavailable = (v.status === 'unavailable');

                    // UI STYLES FOR UNAVAILABLE
                    const rowBg = isUnavailable ? '#f1f5f9' : 'transparent';
                    const textColor = isUnavailable ? '#94a3b8' : '#475569';
                    const opacity = isUnavailable ? '0.6' : '1';
                    const decoration = isUnavailable ? 'line-through' : 'none';

                    const row = document.createElement('tr');
                    row.id = 'var-row-' + v.variant_id;
                    row.style.background = rowBg;
                    row.style.color = textColor;
                    // Note: We don't set display:none, we show them!

                    row.innerHTML = `
                <td style="opacity:${opacity}">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="display:inline-block;width:18px;height:18px;background:${v.color};border-radius:50%;border:1px solid #ddd;"></span> 
                        ${v.color}
                        ${isUnavailable ? '<span style="font-size:0.7rem; border:1px solid #cbd5e1; padding:0 4px; border-radius:4px;">Inactive</span>' : ''}
                    </div>
                </td>
                <td style="font-weight:500; text-decoration:${decoration}; opacity:${opacity}">
                    ${v.size}
                </td>
                <td style="opacity:${opacity}">
                    <input type="number" name="stock[${v.variant_id}]" value="${v.quantity}" class="qty-box" min="0" ${isUnavailable ? 'disabled' : ''}>
                </td>
                <td>
                    ${isUnavailable
                            ? `<button type="button" class="btn-icon-danger" style="filter:grayscale(1); opacity:0.5; cursor:not-allowed;" disabled>🚫</button>`
                            : `<button type="button" class="btn-icon-danger" onclick="markVariantForDeletion(${v.variant_id})" title="Mark Unavailable">🗑️</button>`
                        }
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
            if (!confirm("Are you sure? This will mark the variant as unavailable upon saving.")) return;

            // Add to delete queue (The PHP will treat these IDs as "set to unavailable")
            deletedVariantsIds.push(variantId);

            // Visually transform the row to look "Greyed Out" immediately
            const row = document.getElementById('var-row-' + variantId);
            if (row) {
                // Apply the "Unavailable" look
                row.style.background = '#f1f5f9';
                row.style.color = '#94a3b8';
                row.style.transition = 'all 0.3s ease';

                // Disable inputs
                const inputs = row.querySelectorAll('input');
                inputs.forEach(input => input.disabled = true);

                // Strikethrough text
                const textCells = row.querySelectorAll('td');
                textCells.forEach(td => td.style.opacity = '0.6');
                if (row.cells[1]) row.cells[1].style.textDecoration = 'line-through';

                // Disable the delete button
                const btn = row.querySelector('.btn-icon-danger');
                if (btn) {
                    btn.innerHTML = '🚫';
                    btn.disabled = true;
                    btn.style.filter = 'grayscale(1)';
                    btn.style.opacity = '0.5';
                    btn.style.cursor = 'not-allowed';
                    btn.title = "Marked for unavailability";
                }
            }
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