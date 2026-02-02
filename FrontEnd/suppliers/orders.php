<?php
session_start();

// --- 1. AJAX HANDLER (API LOGIC) ---
if (isset($_GET['ajax_action']) || isset($_POST['ajax_action'])) {
    include("../../BackEnd/config/dbconfig.php");
    $supplier_id = $_SESSION['supplierid'];
    $company_id = 0;
    $cr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_id FROM companies WHERE supplier_id = " . (int)$supplier_id . " AND status = 'active' LIMIT 1"));
    if ($cr) $company_id = (int)$cr['company_id'];

    // A. FETCH ORDERS LIST (Populates the Table)
    if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'fetch_orders') {
        $month = (int) $_GET['month'];
        $year = (int) $_GET['year'];
        $search = trim($_GET['search']);

        $sql = "SELECT DISTINCT o.order_id, o.order_code, o.order_date, o.order_status, o.payment_method, o.price AS total_amount, c.name AS customer_name, p.product_id FROM orders o
LEFT JOIN customers c ON o.customer_id = c.customer_id JOIN order_detail od ON o.order_id = od.order_id JOIN product_variant pv ON od.variant_id = pv.variant_id JOIN products p ON pv.product_id = p.product_id
WHERE o.company_id = ? AND MONTH(o.order_date) = ? AND YEAR(o.order_date) = ?";

        $params = [$company_id, $month, $year];
        $types = "iii";

        if (!empty($search)) {
            $sql .= " AND (c.name LIKE ? OR o.order_code LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        $sql .= " ORDER BY o.order_date DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo "<tr><td colspan='6' style='text-align:center; padding:20px; color:red;'>Database Error: " . $conn->error . "</td></tr>";
            exit;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);

        if (count($orders) > 0) {
            foreach ($orders as $o) {
                // Status Class Logic for the Table Badge
                $statusClass = 'badge';
                $s = strtolower($o['order_status']);
                if ($s == 'confirm' || $s == 'shipped')
                    $statusClass .= ' ok';
                elseif ($s == 'cancelled')
                    $statusClass .= ' low';
                else
                    $statusClass .= ' pending';

                echo '<tr class="product-row">';
                echo '<td>
                        <div style="font-weight:600; color:var(--text-main);">#' . htmlspecialchars($o['order_code']) . '</div>
                        <small style="color:#888;">' . date('M d, Y', strtotime($o['order_date'])) . '</small>
                      </td>';
                echo '<td>' . htmlspecialchars($o['customer_name'] ?: 'Guest') . '</td>';
                echo '<td style="font-weight:700;">$' . number_format($o['total_amount'], 2) . '</td>';
                echo '<td style="color:#666;">' . ucfirst($o['payment_method']) . '</td>';
                echo '<td><span class="' . $statusClass . '">' . ucfirst($o['order_status']) . '</span></td>';

                // --- THE VIEW BUTTON ---
                echo '<td>
                        <button class="btn-sm" style="background:#fff; border:1px solid #ddd; color:#333; font-weight:600;" onclick="openViewModal(' . $o['order_id'] . ')">
                            View Details
                        </button>
                      </td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6" style="text-align:center; padding:40px; color:#888;">No orders found for this period.</td></tr>';
        }
        exit;
    }

    // B. FETCH SINGLE ORDER DETAILS (The Backend Logic for the Modal)
    // --- THIS SECTION IS FIXED ---
    if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_details') {
        // Ensure we output JSON even if errors occur
        header('Content-Type: application/json');

        $order_id = (int) $_GET['order_id'];

        // 1. Fetch Order + Customer Info
        $stmt = $conn->prepare("
                SELECT o.*, c.name, c.email, c.address, c.phone 
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.customer_id 
                WHERE o.order_id = ? AND o.company_id = ?");

        if (!$stmt) {
            echo json_encode(['error' => 'Order Query SQL Error: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("ii", $order_id, $company_id);
        $stmt->execute();
        $orderResult = $stmt->get_result();

        if ($orderResult->num_rows === 0) {
            echo json_encode(['error' => 'Order not found or access denied']);
            exit;
        }

        $order = $orderResult->fetch_assoc();

        // 2. Fetch Items for that Order
        // Note: If you still get an error, check if your table is named 'order_details' (plural) instead of 'order_detail'
        $pStmt = $conn->prepare("SELECT  od.quantity, p.price, p.product_name, p.product_id, p.image 
                                        FROM order_detail od
                                        JOIN product_variant pv ON od.variant_id = pv.variant_id 
                                        JOIN products p ON pv.product_id = p.product_id 
                                        WHERE od.order_id = ?");

        if (!$pStmt) {
            echo json_encode(['error' => 'Product Query SQL Error: ' . $conn->error]);
            exit;
        }

        $pStmt->bind_param("i", $order_id);
        $pStmt->execute();
        $products = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['order' => $order, 'products' => $products]);
        exit;
    }
}

// --- NORMAL PAGE LOAD ---
include("partials/nav.php");
include("../../BackEnd/config/dbconfig.php"); // Ensure DB is connected for stats

// Stats Calculation (orders table uses company_id)
$company_id = $row['company_id'] ?? 0;
$statsStmt = $conn->prepare("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN order_status = 'confirm' THEN price ELSE 0 END) as revenue
    FROM orders WHERE company_id = ? AND MONTH(order_date) = MONTH(CURRENT_DATE())");

if ($statsStmt) {
    $statsStmt->bind_param("i", $company_id);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();
} else {
    $stats = ['total' => 0, 'pending' => 0, 'revenue' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
    <link rel="stylesheet" href="css/supplierCss.css">
    <style>
        /* --- ICONS (SVG) --- */
        .icon-svg {
            width: 24px;
            height: 24px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
        }

        .icon-sm {
            width: 18px;
            height: 18px;
        }

        /* --- HEADER & STATS --- */
        .rent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 45px 30px 45px;
        }

        .rent-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 0 45px 30px 45px;
        }

        /* --- FILTER BAR --- */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
            padding: 15px 25px;
        }

        .search-wrapper {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: #fff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            color: #333;
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border-color: var(--primary);
        }

        .search-icon-pos {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        /* --- DATE NAVIGATOR --- */
        .date-nav {
            display: flex;
            align-items: center;
            background: #fff;
            padding: 5px;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            gap: 10px;
            user-select: none;
        }

        .nav-btn {
            background: transparent;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #555;
            transition: 0.2s;
        }

        .nav-btn:hover {
            background: #f3f3f3;
            color: #000;
        }

        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .date-display {
            font-weight: 600;
            color: #333;
            min-width: 140px;
            text-align: center;
            font-size: 0.95rem;
        }

        /* --- TABLE & LOADING --- */
        .table-wrapper {
            position: relative;
            min-height: 300px;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 5;
            border-radius: 20px;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e5e5;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .badge.pending {
            background: #fff8e1;
            color: #b7791f;
            border: 1px solid #fef3c7;
        }

        /* --- VIEW MODAL STYLES (NEW) --- */
        .split-modal {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            height: 100%;
        }

        .modal-info-side {
            width: 440px;
            background: #fafafa;
            padding: 35px;
            border-right: 1px solid #eee;
            overflow-y: auto;
        }

        .modal-product-side {
            padding: 35px;
            background: white;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .info-group {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
            margin-bottom: 25px;
            border: 1px solid #f0f0f0;
        }

        .info-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #999;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Transaction Details Grid */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }

        .detail-box {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #eee;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
        }

        /* Large Status Pill */
        .status-pill-lg {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: capitalize;
            margin-top: 5px;
        }

        .status-pill-lg.ok {
            background: #dcfce7;
            color: #166534;
        }

        .status-pill-lg.pending {
            background: #fef9c3;
            color: #854d0e;
        }

        .status-pill-lg.low {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Products List */
        .modal-prod-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .modal-prod-img {
            width: 56px;
            height: 56px;
            border-radius: 10px;
            background: #f0f0f0;
            object-fit: cover;
        }

        .modal-prod-list {
            overflow-y: auto;
            flex-grow: 1;
            padding-right: 5px;
        }
    </style>
</head>

<body>
    <div class="rent-header">
        <div>
            <h1>Orders</h1>
            <p style="color:#666; margin-top:5px;">Track sales and view receipts</p>
        </div>
        <div class="glass-panel" style="padding: 10px 20px; display:flex; align-items:center; gap:10px;">
            <div style="text-align:right;">
                <small style="display:block; color:#666; font-size:0.75rem;">REVENUE (THIS MONTH)</small>
                <div style="font-weight:800; color: var(--primary); font-size:1.1rem;">
                    $<?= number_format($stats['revenue'] ?? 0, 2) ?>
                </div>
            </div>
            <svg class="icon-svg" style="color:var(--primary);" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </div>

    <div class="rent-stats-grid">
        <div class="rent-card">
            <div class="icon-box">
                <svg class="icon-svg" style="color:white;" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div class="rent-details">
                <h3>Total Orders</h3>
                <h2><?= $stats['total'] ?></h2>
                <small>This Month</small>
            </div>
        </div>
        <div class="rent-card">
            <div class="icon-box" style="background:#ea982a;">
                <svg class="icon-svg" style="color:white;" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="rent-details">
                <h3>Pending</h3>
                <h2><?= $stats['pending'] ?></h2>
                <small>Needs Action</small>
            </div>
        </div>
        <div class="rent-card">
            <div class="icon-box" style="background:#333;">
                <svg class="icon-svg" style="color:white;" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
            </div>
            <div class="rent-details">
                <h3>Avg. Value</h3>
                <h2>$<?= $stats['total'] > 0 ? number_format($stats['revenue'] / $stats['total'], 2) : '0.00' ?></h2>
                <small>Per Order</small>
            </div>
        </div>
    </div>

    <div class="page-container">

        <div class="glass-panel filter-bar">
            <div class="search-wrapper">
                <div class="search-icon-pos">
                    <svg class="icon-sm" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="searchInput" class="search-input" placeholder="Search customer or code...">
            </div>

            <div class="date-nav">
                <button class="nav-btn" id="prevBtn" onclick="changeDate(-1)">
                    <svg class="icon-sm" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <div class="date-display" id="dateDisplay">Loading...</div>
                <button class="nav-btn" id="nextBtn" onclick="changeDate(1)">
                    <svg class="icon-sm" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="inventory-panel table-wrapper">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner"></div>
            </div>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th width="15%">Order</th>
                        <th width="20%">Customer</th>
                        <th width="15%">Total</th>
                        <th width="15%">Payment</th>
                        <th width="15%">Status</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                </tbody>
            </table>
        </div>
    </div>

    <div id="viewModal" class="modal-overlay">
        <div class="modal-box" style="width: 850px; height: 600px;">
            <div class="split-modal">

                <div class="modal-info-side">
                    <div style="margin-bottom:25px;">
                        <small style="color:#888;">ORDER ID</small>
                        <h2 style="font-weight:800; font-size:1.8rem; margin:0; line-height:1;">
                            <span id="displayOrderCode" style="color:var(--primary);">#...</span>
                        </h2>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Customer Information</div>
                        <div style="display:flex; align-items:flex-start; gap:12px; margin-top:10px;">
                            <div
                                style="background:#eee; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                                👤</div>
                            <div>
                                <div class="info-val" id="displayCustomer" style="font-size:1rem; font-weight:600;">...
                                </div>
                                <div style="font-size:0.85rem; color:#666; margin-top:2px;" id="displayEmail">...</div>
                                <div style="font-size:0.85rem; color:#666; margin-top:2px;" id="displayPhone">...</div>
                            </div>
                        </div>
                        <div style="margin-top:15px; padding-top:10px; border-top:1px dashed #eee;">
                            <small style="color:#999; display:block; margin-bottom:4px;">Shipping Address</small>
                            <div id="displayAddress" style="font-size:0.9rem; line-height:1.4; color:#333;">...</div>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Transaction Details</div>

                        <div class="detail-grid">
                            <div class="detail-box">
                                <small class="info-label" style="display:block; margin-bottom:0;">Payment</small>
                                <div class="detail-value" id="displayPayment">...</div>
                            </div>
                            <div class="detail-box">
                                <small class="info-label" style="display:block; margin-bottom:0;">Date</small>
                                <div class="detail-value" id="displayDate">...</div>
                            </div>
                        </div>

                        <div style="margin-top:20px;">
                            <small class="info-label">Order Status</small>
                            <div>
                                <span id="displayStatusPill" class="status-pill-lg pending">...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-product-side">
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:20px;">
                        <h3 style="margin:0;">Purchased Items</h3>
                        <div style="font-weight:800; font-size:1.4rem; color:var(--primary);" id="displayTotal">$0.00
                        </div>
                    </div>

                    <div class="modal-prod-list" id="modalProductList">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- STATE MANAGEMENT ---
        let viewDate = new Date();
        const today = new Date();

        document.addEventListener('DOMContentLoaded', () => {
            updateDateUI();
            fetchOrders();
        });

        // --- LIVE SEARCH ---
        let debounceTimer;
        document.getElementById('searchInput').addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchOrders, 300);
        });

        // --- DATE NAVIGATION ---
        function changeDate(direction) {
            viewDate.setMonth(viewDate.getMonth() + direction);
            updateDateUI();
            fetchOrders();
        }

        function updateDateUI() {
            const options = { year: 'numeric', month: 'long' };
            document.getElementById('dateDisplay').innerText = viewDate.toLocaleDateString('en-US', options);

            const nextBtn = document.getElementById('nextBtn');
            const isCurrentOrFuture = (viewDate.getFullYear() > today.getFullYear()) ||
                (viewDate.getFullYear() === today.getFullYear() && viewDate.getMonth() >= today.getMonth());
            nextBtn.disabled = isCurrentOrFuture;
        }

        // --- FETCH ORDERS LIST ---
        function fetchOrders() {
            const month = viewDate.getMonth() + 1;
            const year = viewDate.getFullYear();
            const search = document.getElementById('searchInput').value;
            const loader = document.getElementById('loadingOverlay');

            loader.classList.add('active');

            fetch(`orders.php?ajax_action=fetch_orders&month=${month}&year=${year}&search=${search}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('ordersTableBody').innerHTML = html;
                    loader.classList.remove('active');
                })
                .catch(err => {
                    console.error(err);
                    loader.classList.remove('active');
                });
        }

        // --- VIEW MODAL LOGIC (READ ONLY) ---
        function openViewModal(orderId) {
            const modal = document.getElementById('viewModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('open'), 10);

            // Clear previous data
            document.getElementById('modalProductList').innerHTML = '<div style="padding:20px; text-align:center;">Loading details...</div>';

            fetch(`orders.php?ajax_action=get_details&order_id=${orderId}`)
                .then(res => res.text())
                .then(text => {
                    let data;

                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error("Invalid JSON returned:", text);
                        alert("Server error. Check console.");
                        closeModal();
                        return;
                    }

                    if (data.error) {
                        alert("Error: " + data.error);
                        closeModal();
                        return;
                    }

                    if (!data.order) {
                        console.error("Missing order object:", data);
                        alert("Order data missing.");
                        closeModal();
                        return;
                    }

                    const o = data.order;


                    // 1. Header & Total
                    document.getElementById('displayOrderCode').innerText = '#' + o.order_code;
                    document.getElementById('displayTotal').innerText = '$' + parseFloat(o.price).toFixed(2);

                    // 2. Customer Info
                    document.getElementById('displayCustomer').innerText = o.name || 'Guest Customer';
                    document.getElementById('displayEmail').innerText = o.email || 'No email provided';
                    document.getElementById('displayPhone').innerText = o.phone || 'No phone provided';
                    document.getElementById('displayAddress').innerText = o.address || 'No shipping address';

                    // 3. Transaction Details
                    // Payment
                    document.getElementById('displayPayment').innerHTML = `
                        <svg class="icon-sm" viewBox="0 0 24 24" style="color:#666;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg> 
                        ${o.payment_method ? o.payment_method.toUpperCase() : 'N/A'}
                    `;

                    // Date & Time
                    const dateObj = new Date(o.order_date);
                    const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const timeStr = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    document.getElementById('displayDate').innerHTML = `
                        <svg class="icon-sm" viewBox="0 0 24 24" style="color:#666;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 
                        ${dateStr} <span style="font-weight:400; color:#888; font-size:0.8em; margin-left:4px;">${timeStr}</span>
                    `;

                    // Status Pill
                    const pill = document.getElementById('displayStatusPill');
                    const s = o.order_status.toLowerCase();
                    pill.className = 'status-pill-lg'; // reset
                    pill.innerText = o.order_status;

                    if (s === 'confirm' || s === 'shipped') pill.classList.add('ok');
                    else if (s === 'cancelled') pill.classList.add('low');
                    else pill.classList.add('pending');

                    // 4. Products List
                    const list = document.getElementById('modalProductList');
                    list.innerHTML = '';

                    if (data.products.length > 0) {
                        data.products.forEach(p => {
                            const imgPath = p.image ? '../uploads/products/' + p.product_id + '_' + p.image : '../assets/placeholder.png';

                            const html = `
                                <div class="modal-prod-item">
                                    <img src="${imgPath}" class="modal-prod-img">
                                    <div style="flex-grow:1;">
                                        <div style="font-weight:600; color:#333; margin-bottom:4px;">${p.product_name}</div>
                                        <div style="font-size:0.85rem; color:#888;">
                                            Qty: <strong style="color:#333;">${p.quantity}</strong> × $${parseFloat(p.price).toFixed(2)}
                                        </div>
                                    </div>
                                    <div style="font-weight:700; color:#333;">$${(p.quantity * p.price).toFixed(2)}</div>
                                </div>
                            `;
                            list.insertAdjacentHTML('beforeend', html);
                        });
                    } else {
                        list.innerHTML = '<div style="padding:40px; text-align:center; color:#999;">No items found.</div>';
                    }
                })
                .catch(err => {
                    console.error("Fetch Error:", err);
                    document.getElementById('modalProductList').innerText = "Error loading details.";
                });
        }

        function closeModal() {
            const modal = document.getElementById('viewModal');
            modal.classList.remove('open');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        window.onclick = function (e) {
            if (e.target.classList.contains('modal-overlay')) closeModal();
        }
    </script>
</body>

</html>