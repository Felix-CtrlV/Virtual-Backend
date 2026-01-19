<?php
// orders.php
session_start();

// --- 1. AJAX HANDLER (API LOGIC) ---
if (isset($_GET['ajax_action']) || isset($_POST['ajax_action'])) {
    include("../../BackEnd/config/dbconfig.php");
    $supplier_id = $_SESSION['supplierid'];

    // A. FETCH ORDERS LIST
    if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'fetch_orders') {
        $month = (int)$_GET['month'];
        $year = (int)$_GET['year'];
        $search = trim($_GET['search']);
        
        $sql = "SELECT o.order_id, o.order_code, o.order_date, o.order_status, o.payment_method, o.price AS total_amount, c.name AS customer_name 
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.customer_id 
                WHERE o.supplier_id = ? AND MONTH(o.order_date) = ? AND YEAR(o.order_date) = ?";
        
        $params = [$supplier_id, $month, $year];
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
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        
        if (count($orders) > 0) {
            foreach ($orders as $o) {
                // Status Class Logic
                $statusClass = 'badge';
                $s = strtolower($o['order_status']);
                if($s == 'confirm' || $s == 'shipped') $statusClass .= ' ok';
                elseif($s == 'cancelled') $statusClass .= ' low';
                else $statusClass .= ' pending'; 
                
                echo '<tr class="product-row">';
                echo '<td>
                        <div style="font-weight:600; color:var(--text-main);">#'.htmlspecialchars($o['order_code']).'</div>
                        <small style="color:#888;">'.date('M d, Y', strtotime($o['order_date'])).'</small>
                      </td>';
                echo '<td>'.htmlspecialchars($o['customer_name'] ?: 'Guest').'</td>';
                echo '<td style="font-weight:700;">$'.number_format($o['total_amount'], 2).'</td>';
                echo '<td style="color:#666;">'.ucfirst($o['payment_method']).'</td>';
                echo '<td><span class="'.$statusClass.'">'.ucfirst($o['order_status']).'</span></td>';
                echo '<td>
                        <button class="btn-sm" style="background:white; border:1px solid #ddd; color:#333;" onclick="openManageModal('.$o['order_id'].')">Manage</button>
                      </td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6" style="text-align:center; padding:40px; color:#888;">No orders found for this period.</td></tr>';
        }
        exit;
    }

    // B. FETCH SINGLE ORDER DETAILS
    if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_details') {
        $order_id = (int)$_GET['order_id'];
        
        $stmt = $conn->prepare("SELECT o.*, c.name, c.email, c.address, c.phone FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = ? AND o.supplier_id = ?");
        $stmt->bind_param("ii", $order_id, $supplier_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        $pStmt = $conn->prepare("SELECT od.quantity, od.price, p.product_name, p.image FROM order_detail od JOIN products p ON od.product_id = p.product_id WHERE od.order_id = ?");
        $pStmt->bind_param("i", $order_id);
        $pStmt->execute();
        $products = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['order' => $order, 'products' => $products]);
        exit;
    }

    // C. UPDATE STATUS
    if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_status') {
        $order_id = (int)$_POST['order_id'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ? AND supplier_id = ?");
        $stmt->bind_param("sii", $status, $order_id, $supplier_id);
        echo $stmt->execute() ? 'success' : 'error';
        exit;
    }
}

// --- NORMAL PAGE LOAD ---
include("partials/nav.php"); 

// Stats Calculation
$supplier_id = $_SESSION['supplierid'];
$statsStmt = $conn->prepare("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN order_status = 'confirm' THEN price ELSE 0 END) as revenue
    FROM orders WHERE supplier_id = ? AND MONTH(order_date) = MONTH(CURRENT_DATE())");
$statsStmt->bind_param("i", $supplier_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
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
        .icon-svg { width: 24px; height: 24px; fill: none; stroke: currentColor; stroke-width: 2; }
        .icon-sm { width: 18px; height: 18px; }
        
        /* --- HEADER & STATS --- */
        .rent-header { display: flex; justify-content: space-between; align-items: center; margin: 20px 45px 30px 45px; }
        .rent-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 0 45px 30px 45px; }

        /* --- FILTER BAR --- */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
            padding: 15px 25px; /* Bigger padding for glass effect */
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
            border: 1px solid rgba(0,0,0,0.08);
            background: #fff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            color: #333;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
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
            border: 1px solid rgba(0,0,0,0.08);
            gap: 10px;
            user-select: none;
        }

        .nav-btn {
            background: transparent;
            border: none;
            width: 36px; height: 36px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            color: #555;
            transition: 0.2s;
        }
        .nav-btn:hover { background: #f3f3f3; color: #000; }
        .nav-btn:disabled { opacity: 0.3; cursor: not-allowed; }

        .date-display {
            font-weight: 600;
            color: #333;
            min-width: 140px;
            text-align: center;
            font-size: 0.95rem;
        }

        /* --- TABLE & LOADING --- */
        .table-wrapper { position: relative; min-height: 300px; }
        .loading-overlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(4px);
            display: none; justify-content: center; align-items: center;
            z-index: 5; border-radius: 20px;
        }
        .loading-overlay.active { display: flex; }
        
        .spinner {
            width: 40px; height: 40px;
            border: 3px solid #e5e5e5;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .badge.pending { background: #fff8e1; color: #b7791f; border: 1px solid #fef3c7; }

        /* --- MODAL --- */
        .split-modal { display: grid; grid-template-columns: 1fr 1.5fr; height: 100%; }
        .modal-info-side { background: #fafafa; padding: 35px; border-right: 1px solid #eee; overflow-y: auto; }
        .modal-product-side { padding: 35px; background: white; display: flex; flex-direction: column; overflow: hidden; }
        
        .info-group {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            margin-bottom: 25px;
            border: 1px solid #f0f0f0;
        }
        
        .modal-prod-item {
            display: flex; align-items: center; gap: 20px; padding: 15px 0; border-bottom: 1px solid #f5f5f5;
        }
        .modal-prod-img {
            width: 56px; height: 56px; border-radius: 10px; background: #f0f0f0; object-fit: cover;
        }

        /* Radio Status */
        .status-opt {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 15px; border: 1px solid #e5e5e5; border-radius: 10px;
            cursor: pointer; transition: 0.2s; background: #fff;
        }
        .status-opt:hover { border-color: #ccc; }
        input[type="radio"]:checked + span { font-weight: 700; color: var(--primary); }
        input[type="radio"] { accent-color: var(--primary); transform: scale(1.1); }
    </style>
</head>

<body>
    <div class="rent-header">
        <div>
            <h1>Manage Orders</h1>
            <p style="color:#666; margin-top:5px;">Track sales and update status</p>
        </div>
        <div class="glass-panel" style="padding: 10px 20px; display:flex; align-items:center; gap:10px;">
            <div style="text-align:right;">
                <small style="display:block; color:#666; font-size:0.75rem;">REVENUE (THIS MONTH)</small>
                <div style="font-weight:800; color: var(--primary); font-size:1.1rem;">
                    $<?= number_format($stats['revenue'], 2) ?>
                </div>
            </div>
            <svg class="icon-svg" style="color:var(--primary);" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
        </div>
    </div>

    <div class="rent-stats-grid">
        <div class="rent-card">
            <div class="icon-box">
                <svg class="icon-svg" style="color:white;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div class="rent-details">
                <h3>Total Orders</h3>
                <h2><?= $stats['total'] ?></h2>
                <small>This Month</small>
            </div>
        </div>
        <div class="rent-card">
            <div class="icon-box" style="background:#ea982a;">
                <svg class="icon-svg" style="color:white;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="rent-details">
                <h3>Pending</h3>
                <h2><?= $stats['pending'] ?></h2>
                <small>Needs Action</small>
            </div>
        </div>
        <div class="rent-card">
            <div class="icon-box" style="background:#333;">
                <svg class="icon-svg" style="color:white;" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
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
                    <svg class="icon-sm" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" id="searchInput" class="search-input" placeholder="Search customer or code...">
            </div>
            
            <div class="date-nav">
                <button class="nav-btn" id="prevBtn" onclick="changeDate(-1)">
                    <svg class="icon-sm" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <div class="date-display" id="dateDisplay">Loading...</div>
                <button class="nav-btn" id="nextBtn" onclick="changeDate(1)">
                    <svg class="icon-sm" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
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

    <div id="manageModal" class="modal-overlay">
        <div class="modal-box" style="width: 900px; height: 650px;">
            <form id="updateOrderForm" class="split-modal" onsubmit="return saveStatus(event)">
                <input type="hidden" id="modalOrderId" name="order_id">
                
                <div class="modal-info-side">
                    <h2 style="margin-bottom:20px; font-weight:800; font-size:1.4rem;">
                        Order <span id="displayOrderCode" style="color:var(--primary);">#...</span>
                    </h2>
                    
                    <div class="info-group">
                        <div class="info-label">Customer Details</div>
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:5px;">
                            <div style="background:#eee; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center;">👤</div>
                            <div class="info-val" id="displayCustomer">...</div>
                        </div>
                        <div style="font-size:0.85rem; color:#666; margin-left:40px;" id="displayEmail">...</div>
                        <div style="font-size:0.85rem; color:#666; margin-left:40px; margin-top:2px;" id="displayAddress">...</div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Update Order Status</div>
                        <div class="status-options">
                            <label class="status-opt">
                                <input type="radio" name="status" value="pending">
                                <span>Pending</span>
                            </label>
                            <label class="status-opt">
                                <input type="radio" name="status" value="confirm">
                                <span>Confirmed</span>
                            </label>
                            <label class="status-opt">
                                <input type="radio" name="status" value="shipped">
                                <span>Shipped</span>
                            </label>
                            <label class="status-opt">
                                <input type="radio" name="status" value="cancelled">
                                <span>Cancelled</span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-top:auto;">
                        <button type="submit" class="btn-main" style="width:100%; justify-content:center;">Save Changes</button>
                        <button type="button" class="btn-sm" style="width:100%; margin-top:10px; background:transparent; color:#888; border:none;" onclick="closeModal()">Dismiss</button>
                    </div>
                </div>

                <div class="modal-product-side">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:20px;">
                        <h3 style="margin:0;">Items</h3>
                        <div style="font-weight:800; font-size:1.3rem; color:var(--primary);" id="displayTotal">$0.00</div>
                    </div>
                    
                    <div class="modal-prod-list" id="modalProductList">
                        </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- STATE MANAGEMENT ---
        let viewDate = new Date(); // Tracks the currently viewed month
        const today = new Date();  // Fixed reference for capping

        document.addEventListener('DOMContentLoaded', () => {
            updateDateUI();
            fetchOrders();
        });

        // --- LIVE SEARCH (Debounce) ---
        let debounceTimer;
        document.getElementById('searchInput').addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchOrders, 300);
        });

        // --- DATE NAVIGATION LOGIC ---
        function changeDate(direction) {
            // Update viewDate
            viewDate.setMonth(viewDate.getMonth() + direction);
            updateDateUI();
            fetchOrders();
        }

        function updateDateUI() {
            // Format: "January 2026"
            const options = { year: 'numeric', month: 'long' };
            document.getElementById('dateDisplay').innerText = viewDate.toLocaleDateString('en-US', options);

            // Cap Logic: Disable "Next" if viewDate >= current real month/year
            const nextBtn = document.getElementById('nextBtn');
            // Compare year and month
            const isCurrentOrFuture = (viewDate.getFullYear() > today.getFullYear()) || 
                                      (viewDate.getFullYear() === today.getFullYear() && viewDate.getMonth() >= today.getMonth());
            
            nextBtn.disabled = isCurrentOrFuture;
        }

        // --- FETCH ORDERS ---
        function fetchOrders() {
            // Send 1-based month to PHP (JS is 0-based)
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

        // --- MODAL LOGIC ---
        function openManageModal(orderId) {
            const modal = document.getElementById('manageModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('open'), 10);
            
            fetch(`orders.php?ajax_action=get_details&order_id=${orderId}`)
                .then(res => res.json())
                .then(data => {
                    // Populate
                    document.getElementById('modalOrderId').value = data.order.order_id;
                    document.getElementById('displayOrderCode').innerText = '#' + data.order.order_code;
                    document.getElementById('displayCustomer').innerText = data.order.customer_name || 'Guest';
                    document.getElementById('displayEmail').innerText = data.order.email;
                    document.getElementById('displayAddress').innerText = data.order.address;
                    document.getElementById('displayTotal').innerText = '$' + parseFloat(data.order.price).toFixed(2);
                    
                    // Status
                    const radios = document.getElementsByName('status');
                    for(let r of radios) {
                        r.checked = (r.value == data.order.order_status);
                    }

                    // Products
                    const list = document.getElementById('modalProductList');
                    list.innerHTML = '';
                    
                    if(data.products.length > 0) {
                        data.products.forEach(p => {
                            const imgPath = p.image ? '../uploads/products/' + data.order.supplier_id + '_' + p.image : '../assets/placeholder.png';
                            
                            const html = `
                                <div class="modal-prod-item">
                                    <img src="${imgPath}" class="modal-prod-img" onerror="this.src='../assets/placeholder.png'">
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
                        list.innerHTML = '<div style="padding:40px; text-align:center; color:#999;">No items found in this order.</div>';
                    }
                });
        }

        function closeModal() {
            const modal = document.getElementById('manageModal');
            modal.classList.remove('open');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        function saveStatus(e) {
            e.preventDefault();
            const form = document.getElementById('updateOrderForm');
            const formData = new FormData(form);
            formData.append('ajax_action', 'update_status');

            const btn = form.querySelector('.btn-main');
            const originalText = btn.innerText;
            btn.innerText = 'Saving...';

            fetch('orders.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(res => {
                if(res.trim() === 'success') {
                    // Slight delay for better UX feeling
                    setTimeout(() => {
                        closeModal();
                        fetchOrders();
                        btn.innerText = originalText;
                    }, 300);
                } else {
                    alert("Error updating order.");
                    btn.innerText = originalText;
                }
            });
            return false;
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) closeModal();
        }
    </script>
</body>
</html>