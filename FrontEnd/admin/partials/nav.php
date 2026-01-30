<?php
// Ensure this path is correct relative to where nav.php is included
include("../../BackEnd/config/dbconfig.php");

// Session Check
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: index.php");
    exit();
}

// Fetch Admin Info
$adminid = $_SESSION["adminid"];
$admininfosql = "SELECT * FROM admins WHERE adminid='$adminid'";
$adminresult = mysqli_query($conn, $admininfosql);
$admininfo = mysqli_fetch_assoc($adminresult);
$name = $admininfo['name'];

// Page Logic
$currentPage = basename($_SERVER['PHP_SELF']);
if (!isset($active)) {
    $active = '';
    if ($currentPage === 'dashboard.php')
        $active = 'dashboard';
    elseif ($currentPage === 'users.php')
        $active = 'users';
    elseif ($currentPage === 'viewsuppliers.php' or $currentPage === 'suppliersmanagement.php')
        $active = 'view-supplier';
    elseif ($currentPage === 'reviews.php')
        $active = 'reviews';
    elseif ($currentPage === 'rentingpayment.php')
        $active = 'renting';
    elseif ($currentPage === 'setting.php')
        $active = 'profile';
}

if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
    $pageSubtitle = 'High-level overview of your mall performance.';
}

// --- FETCH PENDING COMPANIES ---
$pending_sql = "SELECT c.*, s.name AS supplier_name, s.email AS supplier_email, s.image AS supplier_image, sa.logo AS company_logo
FROM companies c
JOIN suppliers s ON c.supplier_id = s.supplier_id
LEFT JOIN shop_assets sa ON sa.supplier_id = c.supplier_id
WHERE c.status = 'pending' ORDER BY c.created_at DESC";

// Fallback logic for table names
$pending_query = mysqli_query($conn, $pending_sql);
if (!$pending_query) {
    $pending_sql = "SELECT c.*, s.name as supplier_name, s.email as supplier_email, s.image as supplier_image 
                FROM company c 
                JOIN supplier s ON c.supplier_id = s.supplier_id 
                WHERE c.status = 'pending' ORDER BY c.created_at DESC";
    $pending_query = mysqli_query($conn, $pending_sql);
}

$pending_count = 0;
$pending_companies = [];
if ($pending_query) {
    $pending_count = mysqli_num_rows($pending_query);
    while ($row = mysqli_fetch_assoc($pending_query)) {
        $pending_companies[] = $row;
    }
}

// --- FETCH CHAT CONVERSATIONS ---
$chat_sql = "
    SELECT 
        c.conversation_id,
        s.supplier_id,
        s.name,
        s.image,
        (SELECT message_text FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.created_at DESC LIMIT 1) as last_msg,
        (SELECT created_at FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.created_at DESC LIMIT 1) as last_time
    FROM conversations c
    JOIN conversation_participants cp_me ON c.conversation_id = cp_me.conversation_id
    JOIN conversation_participants cp_other ON c.conversation_id = cp_other.conversation_id
    JOIN suppliers s ON cp_other.user_id = s.supplier_id
    WHERE cp_me.user_id = '$adminid' 
      AND cp_me.user_type = 'admin'
      AND cp_other.user_type = 'supplier'
    ORDER BY last_time DESC
";
$chat_query = mysqli_query($conn, $chat_sql);
$conversations = [];
if ($chat_query) {
    while ($row = mysqli_fetch_assoc($chat_query)) {
        $conversations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Malltiverse Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adminstyle.css">

    <style>
        /* Existing Notification Styles */
        .notif-container {
            position: relative;
            margin-right: 20px;
            cursor: pointer;
        }

        .notif-icon {
            font-size: 1.2rem;
            color: var(--text);
            transition: color 0.3s;
        }

        .notif-icon:hover {
            color: var(--accent);
        }

        .notif-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger);
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            border: 2px solid var(--bg);
        }

        .notif-dropdown {
            display: none;
            position: absolute;
            top: 40px;
            right: 0;
            width: 320px;
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow: hidden;
            animation: fadeIn 0.2s ease-in-out;
        }

        .notif-dropdown.show {
            display: block;
        }

        .notif-header {
            padding: 15px;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }

        .notif-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notif-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
            cursor: pointer;
        }

        .notif-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .notif-item-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            background: #334155;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--text);
        }

        .notif-item-info h4 {
            font-size: 0.9rem;
            margin: 0;
            color: var(--text);
        }

        .notif-item-info p {
            font-size: 0.8rem;
            margin: 2px 0 0;
            color: var(--muted);
        }

        .empty-state {
            padding: 20px;
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
        }

        /* Modal Styles */
        .side-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(2px);
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .side-modal {
            position: fixed;
            top: 0;
            right: -450px;
            width: 400px;
            height: 100%;
            background: var(--bg-light);
            border-left: 1px solid var(--border);
            z-index: 2001;
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.5);
            transition: right 0.3s cubic-bezier(0.77, 0, 0.175, 1);
            display: flex;
            flex-direction: column;
            color: var(--text);
        }

        .side-modal.active {
            right: 0;
        }

        .side-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 10px;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .tag-pill {
            display: inline-block;
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .supplier-preview {
            display: flex;
            align-items: center;
            background: rgba(15, 23, 42, 0.6);
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .supplier-preview img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-accept {
            background: var(--success);
            color: white;
        }

        .btn-reject {
            background: var(--danger);
            color: white;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 50%;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 9000;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
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
            background: #0f172a;
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
        }

        .chat-overlay-container.active .chat-interface-inner {
            opacity: 1;
        }

        /* --- SIDEBAR (Contact List) --- */
        .chat-sidebar-panel {
            width: 350px;
            background: #1e293b;
            border-right: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
        }

        .chat-sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .btn-close-chat {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
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
            background: rgba(255,255,255,0.1);
            border-color: white;
        }

        .chat-search-box {
            padding: 15px;
        }

        .chat-search-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.1);
            padding: 10px 15px;
            border-radius: 20px;
            color: white;
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
            border-bottom: 1px solid rgba(255,255,255,0.02);
            transition: background 0.2s;
        }

        .contact-item:hover, .contact-item.active {
            background: #334155;
        }

        .contact-item.active {
            border-left: 3px solid #6366f1;
        }

        .contact-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #475569;
            margin-right: 15px;
            overflow: hidden;
        }

        .contact-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .contact-info { flex: 1; overflow: hidden; }
        .contact-top { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .contact-name { font-weight: 600; color: #f1f5f9; font-size: 0.95rem; }
        .contact-time { font-size: 0.75rem; color: #94a3b8; }
        .contact-msg { font-size: 0.85rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* --- MAIN CHAT AREA --- */
        .chat-main-view {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #0f172a;
            background-image: radial-gradient(#1e293b 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .chat-header {
            height: 70px;
            padding: 0 30px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-user { display: flex; align-items: center; gap: 15px; color: white; }
        .header-user img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #6366f1; }
        .header-info h4 { margin: 0; font-size: 1rem; }
        .header-info span { font-size: 0.8rem; color: #6366f1; }

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
        .chat-messages-area::-webkit-scrollbar-thumb, .chat-contacts-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }

        .message-bubble {
            max-width: 65%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .msg-incoming {
            align-self: flex-start;
            background: #334155;
            color: #e2e8f0;
            border-bottom-left-radius: 4px;
        }

        .msg-outgoing {
            align-self: flex-end;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: white;
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
            background: rgba(30, 41, 59, 0.9);
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chat-input-box {
            flex: 1;
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.1);
            padding: 12px 20px;
            border-radius: 30px;
            color: white;
            outline: none;
            font-size: 0.95rem;
        }

        .chat-input-box:focus { border-color: #6366f1; }

        .send-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <script src="https://kit.fontawesome.com/7867607d9e.js" crossorigin="anonymous"></script>
</head>

<body>

    <aside class="sidebar" id="mainSidebar">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-vr-cardboard"></i>
            </div>
            <div>
                <div class="logo-text">Malltiverse</div>
                <div class="logo-sub">Admin Console</div>
            </div>
        </div>

        <div class="nav-section-title">Overview</div>
        <ul class="nav">
            <a class="nav-button <?php echo $active === 'dashboard' ? 'active' : ''; ?>"
                href="dashboard.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/kwnsnjyg.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">Dashboard</span>
                <span class="nav-badge">Today</span>
            </a>
        </ul>

        <div class="nav-section-title">Management</div>
        <ul class="nav">
            <a class="nav-button <?php echo $active === 'users' ? 'active' : ''; ?>"
                href="users.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/spzqjmbt.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">User Management</span>
            </a>
            <a class="nav-button <?php echo $active === 'view-supplier' ? 'active' : ''; ?>"
                href="viewsuppliers.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/ntfnmkcn.json" trigger="loop" delay="2000"
                    state="hover-look-around"
                    colors="primary:#ffffff,secondary:#ffffff,tertiary:#000000,quaternary:#ffffff,quinary:#ffffff"
                    style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">View Companies</span>
            </a>
            <a class="nav-button <?php echo $active === 'reviews' ? 'active' : ''; ?>"
                href="reviews.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/xuoapdes.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">Reviews</span>
            </a>
            <a class="nav-button <?php echo $active === 'renting' ? 'active' : ''; ?>"
                href="rentingpayment.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/jeuxydnh.json" trigger="loop" delay="2000" stroke="bold"
                    state="hover-partial-roll" colors="primary:#ffffff,secondary:#ffffff"
                    style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">Rent</span>
            </a>
        </ul>

        <div class="nav-section-title">Account</div>
        <ul class="nav">
            <a class="nav-button <?php echo $active === 'profile' ? 'active' : ''; ?>"
                href="setting.php?adminid=<?php echo urlencode($adminid); ?>">
                <lord-icon src="https://cdn.lordicon.com/umuwriak.json" trigger="loop" delay="2000"
                    colors="primary:#ffffff" style="width:25px;height:25px">
                </lord-icon>
                <span class="nav-label-main">Profile Settings</span>
            </a>
        </ul>

        <div class="nav-foot">
            <p>Signed in as <strong><?php echo htmlspecialchars($name); ?></strong></p>
            <small>Malltiverse • v1.0.0</small><br>
            <small><a href="../utils/signout.php">Sign out</a></small>
        </div>
    </aside>

    <main class="main" id="mainContent">
        <div class="topbar">
            <div class="topbar-left">
                <h1 id="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p id="page-subtitle"><?php echo htmlspecialchars($pageSubtitle); ?></p>
            </div>

            <div class="topbar-actions">
                <div class="notif-container" id="notifBtn">
                    <i class="fas fa-bell notif-icon"></i>
                    <?php if ($pending_count > 0): ?>
                        <span class="notif-badge" id="notifBadge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <span>Pending Registrations</span>
                        </div>
                        <div class="notif-list">
                            <?php if ($pending_count > 0): ?>
                                <?php foreach ($pending_companies as $company): ?>
                                    <div class="notif-item"
                                        onclick='openCompanyModal(<?php echo json_encode($company, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <div class="notif-item-logo">
                                            <?php if (!empty($company['company_logo'])): ?>
                                                <img style="width: 100%; height: 100%; object-fit: cover;"
                                                    src="../uploads/shops/<?php echo $company['supplier_id']; ?>/<?php echo $company['company_logo']; ?>"
                                                    alt="Logo">
                                            <?php else: ?>
                                                <span><?php echo strtoupper(substr($company['company_name'], 0, 1)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notif-item-info">
                                            <h4><?php echo htmlspecialchars($company['company_name']); ?></h4>
                                            <p>By <?php echo htmlspecialchars($company['supplier_name']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">No pending registrations.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
            </div>
        </div>

        <div class="side-modal-overlay" id="modalOverlay" onclick="closeModal()"></div>
        <div class="side-modal" id="companyModal">
            <div class="modal-header">
                <h2 style="font-size:1.2rem; margin:0;">Registration Details</h2>
                <i class="fas fa-times" style="cursor:pointer;" onclick="closeModal()"></i>
            </div>
            <div class="modal-body" id="modalContent"></div>
            <div class="modal-footer">
                <button class="btn btn-reject" onclick="processCompany('reject')">Reject</button>
                <button class="btn btn-accept" onclick="processCompany('accept')">Accept</button>
            </div>
        </div>

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
                                        <img src="../assets/customer_profiles/<?php echo $conv['image']; ?>" 
                                             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($conv['name']); ?>'">
                                    </div>
                                    <div class="contact-info">
                                        <div class="contact-top">
                                            <span class="contact-name"><?php echo htmlspecialchars($conv['name']); ?></span>
                                            <span class="contact-time"><?php echo date('H:i', strtotime($conv['last_time'])); ?></span>
                                        </div>
                                        <div class="contact-msg"><?php echo htmlspecialchars($conv['last_msg']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding:20px; text-align:center; color:#64748b;">No active conversations.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="chat-main-view">
                    <div id="chat-empty-view" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#64748b;">
                        <lord-icon src="https://cdn.lordicon.com/zpxybbhl.json" trigger="hover" colors="primary:#64748b,secondary:#6366f1" style="width:100px;height:100px"></lord-icon>
                        <h3>Select a conversation</h3>
                        <p>Choose a contact from the left to start messaging.</p>
                    </div>

                    <div id="chat-active-view" style="display:none; height:100%; flex-direction:column;">
                        <div class="chat-header">
                            <div class="header-user">
                                <img id="active-chat-img" src="" alt="">
                                <div class="header-info">
                                    <h4 id="active-chat-name">User Name</h4>
                                    <span>Supplier</span>
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
                
                const imgPath = image ? `../assets/customer_profiles/${image}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}`;
                document.getElementById('active-chat-img').src = imgPath;

                // Load Messages
                msgContainer.innerHTML = '<div style="text-align:center; padding:20px; color:#64748b;">Loading...</div>';
                fetchMessages(conversationId);

                // Start Polling
                if (chatInterval) clearInterval(chatInterval);
                chatInterval = setInterval(() => fetchMessages(conversationId, true), 3000);
            }

            // 3. Fetch Messages from DB
            function fetchMessages(conversationId, silent = false) {
                fetch(`utils/get_messages.php?conversation_id=${conversationId}`)
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
                        // 'admin' matches sender_type in your message table for you
                        const isMe = (msg.sender_type === 'admin');
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

            // --- EXISTING DROPDOWN/MODAL LOGIC ---
            const notifBtn = document.getElementById('notifBtn');
            const notifDropdown = document.getElementById('notifDropdown');

            if (notifBtn) {
                notifBtn.addEventListener('click', (e) => {
                    if (e.target.closest('.notif-dropdown')) return;
                    notifDropdown.classList.toggle('show');
                });

                document.addEventListener('click', (e) => {
                    if (!notifBtn.contains(e.target)) {
                        notifDropdown.classList.remove('show');
                    }
                });
            }

            // Modal Logic
            let currentCompanyId = null;

            function openCompanyModal(companyData) {
                currentCompanyId = companyData.company_id;
                const modal = document.getElementById('companyModal');
                const overlay = document.getElementById('modalOverlay');
                const content = document.getElementById('modalContent');
                if (notifDropdown) notifDropdown.classList.remove('show');

                let tagsHtml = '';
                if (companyData.tags) {
                    const tags = companyData.tags.split(',');
                    tags.forEach(tag => { tagsHtml += `<span class="tag-pill">${tag.trim()}</span>`; });
                }

                const logoPath = companyData.company_logo ? `../uploads/shops/${companyData.supplier_id}/${companyData.company_logo}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(companyData.company_name)}`;
                const supplierPath = companyData.supplier_image ? `../assets/customer_profiles/${companyData.supplier_image}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(companyData.supplier_name)}`;

                content.innerHTML = `
                    <div class="info-group" style="text-align:center;">
                        <div style="width:80px; height:80px; background:#eee; border-radius:12px; margin:0 auto 10px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                            <img style="width: 100%; height: 100%; object-fit: cover;" src="${logoPath}" alt="Logo">
                        </div>
                        <h3 style="margin:0;">${companyData.company_name}</h3>
                        <small style="color: var(--muted);">Created: ${companyData.created_at}</small>
                    </div>
                    <div class="info-group"><div class="info-label">Description</div><p style="font-size:0.9rem; color: var(--text);">${companyData.description}</p></div>
                    <div class="info-group"><div class="info-label">Tags</div><div>${tagsHtml}</div></div>
                    <div class="info-group"><div class="info-label">Contact Info</div>
                        <p style="font-size:0.9rem; margin-bottom:5px;"><i class="fas fa-map-marker-alt"></i> ${companyData.address}</p>
                        <p style="font-size:0.9rem;"><i class="fas fa-phone"></i> ${companyData.phone}</p>
                    </div>
                    <hr style="border:0; border-top:1px solid var(--border); margin:20px 0;">
                    <div class="info-group"><div class="info-label">Supplier Information</div>
                        <div class="supplier-preview"><img src="${supplierPath}"><div><div style="font-weight:600; font-size:0.9rem;">${companyData.supplier_name}</div><div style="font-size:0.8rem; color: var(--muted);">${companyData.supplier_email}</div></div></div>
                    </div>
                `;
                modal.classList.add('active');
                overlay.classList.add('active');
            }

            function closeModal() {
                document.getElementById('companyModal').classList.remove('active');
                document.getElementById('modalOverlay').classList.remove('active');
            }

            function processCompany(action) {
                if (!currentCompanyId) return;
                if (!confirm(`Are you sure you want to ${action} this company?`)) return;
                const formData = new FormData();
                formData.append('company_id', currentCompanyId);
                formData.append('action', action);
                fetch('utils/process_company.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) { closeModal(); location.reload(); } else { alert('Error: ' + (data.message || 'Unknown error')); }
                    })
                    .catch(error => { console.error('Error:', error); alert('Request failed.'); });
            }
        </script>
</body>
</html>