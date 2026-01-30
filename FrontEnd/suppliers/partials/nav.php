<?php
include("../../BackEnd/config/dbconfig.php");

if (!isset($_SESSION["supplier_logged_in"])) {
    header("Location: ../supplierLogin.php");
    exit();
}

$supplierid = $_SESSION["supplierid"];

// 1. Fetch Supplier & Shop Details
$stmt = $conn->prepare("SELECT  s.*, c.company_name, c.description AS company_description, sa.*, p.product_id, p.product_name, p.price, pv.variant_id, pv.size, pv.color, pv.quantity
FROM suppliers s LEFT JOIN companies c ON c.supplier_id = s.supplier_id LEFT JOIN shop_assets sa ON sa.supplier_id = s.supplier_id LEFT JOIN products p 
ON p.supplier_id = s.supplier_id LEFT JOIN product_variant pv ON pv.product_id = p.product_id WHERE s.supplier_id = ? AND c.status = 'active';");
$stmt->bind_param("i", $supplierid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
$supplierName = $row['name'];

// 2. Product Count
$countproduct = $conn->prepare("SELECT COUNT(*) AS product_count FROM products WHERE supplier_id = ?");
$countproduct->bind_param("i", $supplierid);
$countproduct->execute();
$rowCount = $countproduct->get_result()->fetch_assoc();
$countproduct->close();
$productCount = $rowCount['product_count'];

// 3. Order Stats (Pending/Cancelled)
$orderCountStmt = $conn->prepare("SELECT SUM(order_status = 'pending') AS pending_count, SUM(order_status = 'cancelled') AS cancelled_count FROM orders WHERE supplier_id = ?");
$orderCountStmt->bind_param("i", $supplierid);
$orderCountStmt->execute();
$orderCounts = $orderCountStmt->get_result()->fetch_assoc();
$orderCountStmt->close();
$pendingOrders = $orderCounts['pending_count'] ?? 0;
$cancelledOrders = $orderCounts['cancelled_count'] ?? 0;

// 4. Contract / Rent Logic
$contractStmt = $conn->prepare('SELECT rp.paid_date AS contract_start, rp.due_date AS contract_end FROM rent_payments rp INNER JOIN (
SELECT supplier_id, MAX(paid_date) AS latest_paid FROM rent_payments WHERE supplier_id = ? GROUP BY supplier_id) latest ON rp.supplier_id = latest.supplier_id
AND rp.paid_date = latest.latest_paid;');
$contractStmt->bind_param('i', $supplierid);
$contractStmt->execute();
$contractRow = $contractStmt->get_result()->fetch_assoc();
$contractStmt->close();

$start = new DateTime($contractRow['contract_start'] ?? 'now');
$end = new DateTime($contractRow['contract_end'] ?? 'now');
$today = new DateTime();
$totalDays = $start->diff($end)->days;
$daysPassed = $start->diff($today)->days;
$diffObj = $end->diff($today);
$diff = $diffObj->days;
if (!$diffObj->invert) {
    $diff = -$diff;
}
$percent = ($totalDays > 0) ? ($daysPassed / $totalDays) * 100 : 0;
$percent = max(0, min(100, round($percent)));

// 5. Total Revenue (Current Month)
$totalrevenuestmt = $conn->prepare("SELECT SUM(daily_revenue) OVER () AS total_revenue_month, SUM(daily_orders) OVER () AS total_orders_month
FROM (SELECT DAY(order_date) AS day, SUM(price) AS daily_revenue, COUNT(*) AS daily_orders FROM orders WHERE supplier_id = ?
AND order_status = 'confirm' AND order_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND order_date < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
GROUP BY DAY(order_date)) t LIMIT 1;");
$totalrevenuestmt->bind_param("i", $supplierid);
$totalrevenuestmt->execute();
$totalrevenueRow = $totalrevenuestmt->get_result()->fetch_assoc();
$totalrevenuestmt->close();
$totalRevenue = $totalrevenueRow['total_revenue_month'] ?? 0;
$totalOrder = $totalrevenueRow['total_orders_month'] ?? 0;

// 6. Best Sellers (Top 5)
$bestsellerstmt = $conn->prepare("SELECT p.product_id, p.product_name, p.image, MAX(variant_sales.total_sold) AS best_variant_sold 
FROM (SELECT pv.product_id, oi.variant_id, SUM(oi.quantity) AS total_sold FROM orders o 
JOIN order_detail oi ON o.order_id = oi.order_id 
JOIN product_variant pv ON oi.variant_id = pv.variant_id 
WHERE o.supplier_id = ? AND o.order_status = 'confirm' 
GROUP BY pv.product_id, oi.variant_id) AS variant_sales 
JOIN products p ON p.product_id = variant_sales.product_id 
GROUP BY p.product_id, p.product_name 
ORDER BY best_variant_sold DESC LIMIT 5;");

$bestsellers = [];
if ($bestsellerstmt) {
    $bestsellerstmt->bind_param("i", $supplierid);
    $bestsellerstmt->execute();
    $bestsellers = $bestsellerstmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $bestsellerstmt->close();
}

// 7. Monthly Revenue Chart with YEAR FILTER
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$yearStmt = $conn->prepare("SELECT DISTINCT YEAR(order_date) as yr FROM orders WHERE supplier_id = ? ORDER BY yr DESC");
$yearStmt->bind_param("i", $supplierid);
$yearStmt->execute();
$availableYearsResult = $yearStmt->get_result();
$availableYears = [];
while ($y = $availableYearsResult->fetch_assoc()) {
    $availableYears[] = $y['yr'];
}
if(empty($availableYears)) $availableYears[] = date('Y');
$yearStmt->close();

$revenue = $conn->prepare("SELECT MONTH(order_date) AS month, SUM(price) AS total_revenue FROM orders
WHERE supplier_id = ? AND YEAR(order_date) = ? AND order_status = 'confirm' GROUP BY MONTH(order_date) ORDER BY month;");
$revenue->bind_param("ii", $supplierid, $selectedYear);
$revenue->execute();
$revenue_result = $revenue->get_result();
$monthlyRevenue = array_fill(1, 12, 0);
while ($revenuerow = $revenue_result->fetch_assoc()) {
    $monthlyRevenue[(int) $revenuerow['month']] = (float) $revenuerow['total_revenue'];
}
$revenue->close();

// 8. Inventory / Best Selling Categories
$categoryStmt = $conn->prepare("SELECT 
    c.category_name, 
    SUM(od.quantity) AS total_sold
FROM order_detail od
JOIN product_variant pv ON od.variant_id = pv.variant_id
JOIN products p ON pv.product_id = p.product_id
JOIN category c ON p.category_id = c.category_id
WHERE p.supplier_id = ?
GROUP BY c.category_name
ORDER BY total_sold DESC;
");
$categoryData = [];
$categoryLabels = [];
if ($categoryStmt) {
    $categoryStmt->bind_param("i", $supplierid);
    $categoryStmt->execute();
    $catResult = $categoryStmt->get_result();
    while ($c = $catResult->fetch_assoc()) {
        $categoryLabels[] = $c['category_name'];
        $categoryData[] = $c['total_sold'];
    }
    $categoryStmt->close();
}
if (empty($categoryData)) {
    $categoryLabels = ['NoData'];
    $categoryData = [0];
}

// 9. Recent Reviews
$reviewStmt = $conn->prepare("SELECT 
    r.review_id,
    r.rating,
    r.review,
    r.created_at,
    u.name AS customer_name,
    u.image
FROM reviews r
LEFT JOIN customers u ON r.customer_id = u.customer_id
WHERE r.supplier_id = ?
  AND r.created_at >= DATE_SUB(NOW(), INTERVAL 100 DAY)
ORDER BY r.created_at DESC;
");

$recentReviews = [];
if ($reviewStmt) {
    $reviewStmt->bind_param("i", $supplierid);
    $reviewStmt->execute();
    $recentReviews = $reviewStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $reviewStmt->close();
}

// --- FETCH CHAT CONVERSATIONS (SUPPLIER SIDE) ---
// We fetch conversations where the current user is a 'supplier'
// and we want to see the 'admin' or other participants.
$chat_sql = "
    SELECT 
        c.conversation_id,
        a.adminid as other_user_id,
        a.name,
        a.image, 
        (SELECT message_text FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.created_at DESC LIMIT 1) as last_msg,
        (SELECT created_at FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.created_at DESC LIMIT 1) as last_time
    FROM conversations c
    JOIN conversation_participants cp_me ON c.conversation_id = cp_me.conversation_id
    JOIN conversation_participants cp_other ON c.conversation_id = cp_other.conversation_id
    JOIN admins a ON cp_other.user_id = a.adminid
    WHERE cp_me.user_id = '$supplierid' 
      AND cp_me.user_type = 'supplier'
      AND cp_other.user_type = 'admin'
    ORDER BY last_time DESC
";
$chat_query = mysqli_query($conn, $chat_sql);
$conversations = [];
if ($chat_query) {
    while ($row_chat = mysqli_fetch_assoc($chat_query)) {
        $conversations[] = $row_chat;
    }
}

// Active Page Logic
$current_page = basename($_SERVER["PHP_SELF"]);
if (!isset($active)) {
    $active = '';
    if ($current_page === "dashboard.php") $active = "dashboard";
    elseif ($current_page === "rentpayment.php") $active = "rentpayment";
    elseif ($current_page === "setting.php" || $current_page === "customize.php") $active = "setting";
    elseif ($current_page === "inventory.php") $active = "inventory";
    elseif ($current_page === "orders.php") $active = "orders";
}

$pendingOrderList = [
    ['id' => '#ORD-991', 'item' => 'Wireless Headset', 'time' => '10 min ago'],
    ['id' => '#ORD-992', 'item' => 'HDMI Cable 2m', 'time' => '1 hour ago'],
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard - <?= htmlspecialchars($row['company_name']) ?> Style</title>
    <link rel="stylesheet" href="css/supplierCss.css">
    
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <script src="https://kit.fontawesome.com/7867607d9e.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: <?= htmlspecialchars($row['primary_color']) ?>;
            --secondary: <?= htmlspecialchars($row['secondary_color']) ?>;
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none; position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;
        }
        .modal-content {
            background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; animation: popIn 0.3s ease;
        }
        @keyframes popIn { from {transform: scale(0.8); opacity: 0;} to {transform: scale(1); opacity: 1;} }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #888; }
        .star-gold { color: #f5c518; }

        /* ============================
           FLOATING EXPANDABLE CHAT (Telegram Style)
           ============================ */

        /* 1. The Floating Button (FAB) */
        .chat-fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 50%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 9000;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            color: white;
        }

        .chat-fab:hover {
            transform: scale(1.1) rotate(5deg);
        }

        /* 2. The Fullscreen Overlay Container */
        .chat-overlay-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #ffffff; /* Supplier theme usually light */
            border-radius: 50%;
            z-index: 9001;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.77, 0, 0.175, 1);
            box-shadow: 0 0 0 0 rgba(0,0,0,0);
            opacity: 0;
            pointer-events: none;
        }

        /* Active State: Full Screen */
        .chat-overlay-container.active {
            bottom: 0;
            right: 0;
            width: 100%;
            height: 100vh;
            border-radius: 0;
            opacity: 1;
            pointer-events: all;
        }

        /* 3. The Inner Layout */
        .chat-interface-inner {
            display: flex;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.3s ease 0.2s; /* Delay fade-in until expanded */
            background: #f1f5f9;
        }

        .chat-overlay-container.active .chat-interface-inner {
            opacity: 1;
        }

        /* --- SIDEBAR (Contact List) --- */
        .chat-sidebar-panel {
            width: 350px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }

        .chat-sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e2e8f0;
            background: var(--primary);
            color: white;
        }

        .btn-close-chat {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.4);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .btn-close-chat:hover {
            background: rgba(255,255,255,0.2);
        }

        .chat-search-box {
            padding: 15px;
            background: #f8fafc;
        }

        .chat-search-input {
            width: 100%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 10px 15px;
            border-radius: 20px;
            color: #334155;
            outline: none;
        }

        .chat-contacts-list {
            flex: 1;
            overflow-y: auto;
        }

        .contact-item {
            display: flex;
            padding: 15px 20px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }

        .contact-item:hover, .contact-item.active {
            background: #f1f5f9;
        }

        .contact-item.active {
            border-left: 3px solid var(--primary);
            background: #eef2ff;
        }

        .contact-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #cbd5e1;
            margin-right: 15px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: bold;
        }

        .contact-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .contact-info { flex: 1; overflow: hidden; }
        .contact-top { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .contact-name { font-weight: 600; color: #334155; font-size: 0.95rem; }
        .contact-time { font-size: 0.75rem; color: #94a3b8; }
        .contact-msg { font-size: 0.85rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* --- MAIN CHAT AREA --- */
        .chat-main-view {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f1f5f9;
            /* Simple pattern for chat background */
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .chat-header {
            height: 70px;
            padding: 0 30px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .header-user { display: flex; align-items: center; gap: 15px; color: #334155; }
        .header-user img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; }
        .header-info h4 { margin: 0; font-size: 1rem; }
        .header-info span { font-size: 0.8rem; color: var(--primary); font-weight: bold; }

        .chat-messages-area {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        /* Custom Scrollbar */
        .chat-messages-area::-webkit-scrollbar, .chat-contacts-list::-webkit-scrollbar { width: 6px; }
        .chat-messages-area::-webkit-scrollbar-thumb, .chat-contacts-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

        .message-bubble {
            max-width: 65%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .msg-incoming {
            align-self: flex-start;
            background: white;
            color: #334155;
            border-bottom-left-radius: 4px;
        }

        .msg-outgoing {
            align-self: flex-end;
            background: var(--primary);
            color: white; /* Assuming primary is dark enough, else need logic */
            border-bottom-right-radius: 4px;
        }

        .msg-time {
            display: block;
            text-align: right;
            font-size: 0.7rem;
            margin-top: 5px;
            opacity: 0.7;
        }

        .chat-input-area {
            padding: 20px 30px;
            background: white;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chat-input-box {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 12px 20px;
            border-radius: 30px;
            color: #334155;
            outline: none;
            font-size: 0.95rem;
        }

        .chat-input-box:focus { border-color: var(--primary); }

        .send-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .send-btn:hover { transform: scale(1.1); }

        /* Mobile Logic */
        @media (max-width: 768px) {
            .chat-sidebar-panel { width: 80px; }
            .contact-info, .chat-search-box { display: none; }
            .chat-sidebar-header { justify-content: center; }
            .contact-avatar { margin-right: 0; }
        }
    </style>
</head>

<body style="background-image:
  radial-gradient(circle at 5% 10%, color-mix(in srgb, var(--primary) 10%, transparent) 0%, transparent 35%),
  radial-gradient(circle at 90% 90%, color-mix(in srgb, var(--primary) 30%, transparent) 0%, transparent 40%);
">

    <header>
        <div class="logo"><?= htmlspecialchars($row['company_name']) ?></div>
        <div class="navline">
            <nav class="nav-links">
                <a href="dashboard.php" <?= $active === "dashboard" ? 'class="active"' : '' ?>>Dashboard</a>
                <a href="inventory.php" <?= $active === "inventory" ? 'class="active"' : '' ?>>Inventory</a>
                <a href="orders.php" <?= $active === "orders" ? 'class="active"' : '' ?>>Orders</a>
                <a href="rentpayment.php" <?= $active === "rentpayment" ? 'class="active"' : '' ?>>Rent Payment</a>
                <a href="setting.php" <?= $active === "setting" ? 'class="active"' : '' ?>>Settings</a>
            </nav>
            <button class="btn-logout" onclick="window.location.href='../utils/signout.php'">Logout</button>
        </div>
    </header>
    
    <div class="container">

        <div class="chat-fab" onclick="toggleChatInterface()">
            <lord-icon src="https://cdn.lordicon.com/fdxqrdfe.json" trigger="loop" delay="2000" colors="primary:#ffffff" style="width:30px;height:30px"></lord-icon>
        </div>

        <div class="chat-overlay-container" id="chatOverlay">
            <div class="chat-interface-inner">
                
                <div class="chat-sidebar-panel">
                    <div class="chat-sidebar-header">
                        <h3 style="margin:0; color:white;">Messages</h3>
                        <button class="btn-close-chat" onclick="toggleChatInterface()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="chat-search-box">
                        <input type="text" class="chat-search-input" placeholder="Search...">
                    </div>

                    <div class="chat-contacts-list">
                        <?php if (!empty($conversations)): ?>
                            <?php foreach ($conversations as $conv): ?>
                                <div class="contact-item" onclick="loadChat(<?php echo $conv['conversation_id']; ?>, '<?php echo htmlspecialchars($conv['name']); ?>', '<?php echo $conv['image']; ?>', this)">
                                    <div class="contact-avatar">
                                        <?php if($conv['image'] && $conv['image'] !== 'default_admin.png'): ?>
                                            <img src="../assets/customer_profiles/<?php echo $conv['image']; ?>" alt="Adm">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr($conv['name'], 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="contact-info">
                                        <div class="contact-top">
                                            <span class="contact-name"><?php echo htmlspecialchars($conv['name']); ?> (Admin)</span>
                                            <span class="contact-time"><?php echo date('H:i', strtotime($conv['last_time'])); ?></span>
                                        </div>
                                        <div class="contact-msg"><?php echo htmlspecialchars($conv['last_msg']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding:20px; text-align:center; color:#64748b;">
                                <p>No active conversations.</p>
                                <small>Contact support if you need help.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="chat-main-view">
                    <div id="chat-empty-view" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#64748b;">
                        <lord-icon src="https://cdn.lordicon.com/zpxybbhl.json" trigger="hover" colors="primary:#64748b,secondary:<?= htmlspecialchars($row['primary_color']) ?>" style="width:100px;height:100px"></lord-icon>
                        <h3>Select a conversation</h3>
                        <p>Choose the Admin from the list to start messaging.</p>
                    </div>

                    <div id="chat-active-view" style="display:none; height:100%; flex-direction:column;">
                        <div class="chat-header">
                            <div class="header-user">
                                <img id="active-chat-img" src="" alt="">
                                <div class="header-info">
                                    <h4 id="active-chat-name">Admin Name</h4>
                                    <span>Mall Administrator</span>
                                </div>
                            </div>
                        </div>

                        <div class="chat-messages-area" id="messages-container">
                            </div>

                        <div class="chat-input-area">
                            <input type="hidden" id="current-conv-id">
                            <input type="text" class="chat-input-box" id="message-input" placeholder="Write a message...">
                            <button class="send-btn" onclick="sendMessage()">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <script>
            // --- CHAT LOGIC ---
            const overlay = document.getElementById('chatOverlay');
            const msgContainer = document.getElementById('messages-container');
            const chatInput = document.getElementById('message-input');
            const currentConvIdInput = document.getElementById('current-conv-id');
            let chatInterval = null;

            // 1. Toggle Chat (Open/Close)
            function toggleChatInterface() {
                overlay.classList.toggle('active');
                
                // Stop polling if closed
                if (!overlay.classList.contains('active')) {
                    if (chatInterval) clearInterval(chatInterval);
                } else {
                    // Resume polling if a chat was previously open
                    const activeId = currentConvIdInput.value;
                    if(activeId) {
                         fetchMessages(activeId, true);
                         chatInterval = setInterval(() => fetchMessages(activeId, true), 3000);
                    }
                }
            }

            // 2. Load Specific Chat
            function loadChat(conversationId, name, image, element) {
                // Highlight sidebar item
                document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active'));
                if(element) element.classList.add('active');

                // UI Updates
                document.getElementById('chat-empty-view').style.display = 'none';
                document.getElementById('chat-active-view').style.display = 'flex';
                document.getElementById('active-chat-name').innerText = name;
                currentConvIdInput.value = conversationId;
                
                // Determine image source
                let imgPath = '';
                // if(image && image !== 'default_admin.png') {
                console.log(image);
                    imgPath = `../assets/customer_profiles/${image}`;
                // } else {
                    // imgPath = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random`;
                // }
                
                // Error handling for image loading
                const imgEl = document.getElementById('active-chat-img');
                imgEl.src = imgPath;
                // imgEl.onerror = function() {
                //      this.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}`;
                // };

                // Load Messages
                msgContainer.innerHTML = '<div style="text-align:center; padding:20px; color:#64748b;">Loading...</div>';
                fetchMessages(conversationId);

                // Start Polling
                if (chatInterval) clearInterval(chatInterval);
                chatInterval = setInterval(() => fetchMessages(conversationId, true), 3000);
            }

            // 3. Fetch Messages from DB
            function fetchMessages(conversationId, silent = false) {
                // Adjust path based on file structure. Assuming nav.php is in 'supplier/inc/' and utils in 'supplier/utils/'
                // 'adminnav.php' used 'utils/get_messages.php'. 
                // Since this file is likely included in pages like 'supplier/dashboard.php', the path relative to the RUNNING script is 'utils/...'.
                // If this doesn't work, try '../utils/' depending on where dashboard.php is located vs utils folder.
                
                fetch(`../admin/utils/get_messages.php?conversation_id=${conversationId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderMessages(data.messages, silent);
                        }
                    })
                    .catch(err => console.error("Error fetching messages:", err));
            }

            // 4. Render Messages to DOM
            function renderMessages(messages, silent) {
                if (!silent) msgContainer.innerHTML = '';

                let html = '';
                if (messages.length === 0) {
                    html = '<div style="text-align:center; margin-top:50px; color:#64748b; font-size:0.9rem;">No messages yet.<br>Start the conversation!</div>';
                } else {
                    messages.forEach(msg => {
                        // 'supplier' matches sender_type in your message table for THIS user
                        const isMe = (msg.sender_type === 'supplier');
                        const bubbleClass = isMe ? 'msg-outgoing' : 'msg-incoming';
                        
                        const dateObj = new Date(msg.created_at);
                        const timeStr = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                        html += `
                            <div class="message-bubble ${bubbleClass}">
                                ${msg.message_text}
                                <span class="msg-time">${timeStr}</span>
                            </div>
                        `;
                    });
                }

                // Update only if content differs to prevent flicker
                if (msgContainer.innerHTML !== html) {
                    msgContainer.innerHTML = html;
                    msgContainer.scrollTop = msgContainer.scrollHeight;
                }
            }

            // 5. Send Message
            function sendMessage() {
                const text = chatInput.value.trim();
                const convId = currentConvIdInput.value;
                if (!text || !convId) return;

                // Optimistic UI Append
                const tempMsg = `
                    <div class="message-bubble msg-outgoing" style="opacity:0.7">
                        ${text}
                        <span class="msg-time">Sending...</span>
                    </div>`;
                msgContainer.insertAdjacentHTML('beforeend', tempMsg);
                msgContainer.scrollTop = msgContainer.scrollHeight;
                chatInput.value = '';

                const formData = new FormData();
                formData.append('conversation_id', convId);
                formData.append('message', text);

                fetch('utils/send_message.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) fetchMessages(convId, true);
                    });
            }

            // Enter key to send
            chatInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') sendMessage();
            });
        </script>