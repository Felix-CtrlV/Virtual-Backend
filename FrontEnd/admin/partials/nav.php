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
    // ... your existing active page logic ...
    if ($currentPage === 'dashboard.php') $active = 'dashboard';
    elseif ($currentPage === 'users.php') $active = 'users';
    elseif ($currentPage === 'viewsuppliers.php' or $currentPage === 'suppliersmanagement.php') $active = 'view-supplier';
    elseif ($currentPage === 'reviews.php') $active = 'reviews';
    elseif ($currentPage === 'rentingpayment.php') $active = 'renting';
    elseif ($currentPage === 'setting.php') $active = 'profile';
}

if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
    $pageSubtitle = 'High-level overview of your mall performance.';
}

// --- FETCH PENDING COMPANIES ---
$pending_sql = "SELECT 
    c.*,
    s.name   AS supplier_name,
    s.email  AS supplier_email,
    s.image  AS supplier_image,
    sa.logo  AS company_logo
FROM companies c
JOIN suppliers s 
    ON c.supplier_id = s.supplier_id
LEFT JOIN shop_assets sa 
    ON sa.supplier_id = c.supplier_id
WHERE c.status = 'pending'
ORDER BY c.created_at DESC";

// Fallback for singular table name 'company' if 'companies' fails
$pending_query = mysqli_query($conn, $pending_sql);
if (!$pending_query) {
    $pending_sql = "SELECT c.*, s.name as supplier_name, s.email as supplier_email, s.image as supplier_image 
                FROM company c 
                JOIN supplier s ON c.supplier_id = s.supplier_id 
                WHERE c.status = 'pending' 
                ORDER BY c.created_at DESC";
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Malltiverse Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adminstyle.css">

    <style>
        .notif-container {
            position: relative;
            margin-right: 20px;
            cursor: pointer;
        }

        .notif-icon {
            font-size: 1.2rem;
            color: #333;
            transition: color 0.3s;
        }

        .notif-icon:hover {
            color: #6C63FF;
        }

        .notif-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ff4757;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .notif-dropdown {
            display: none;
            position: absolute;
            top: 40px;
            right: 0;
            width: 320px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
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
            border-bottom: 1px solid #eee;
            color: #333;
        }

        .notif-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notif-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.2s;
            cursor: pointer;
        }

        .notif-item:hover {
            background-color: #f9f9ff;
        }

        .notif-item-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            background: #eee;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .notif-item-info h4 {
            font-size: 0.9rem;
            margin: 0;
            color: #333;
        }

        .notif-item-info p {
            font-size: 0.8rem;
            margin: 2px 0 0;
            color: #888;
        }

        .empty-state {
            padding: 20px;
            text-align: center;
            color: #999;
            font-size: 0.9rem;
        }

        /* Modal Styles */
        .side-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
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
            background: white;
            z-index: 2001;
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.1);
            transition: right 0.3s cubic-bezier(0.77, 0, 0.175, 1);
            display: flex;
            flex-direction: column;
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
            border-bottom: 1px solid #eee;
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
            border-top: 1px solid #eee;
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
            color: #888;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .tag-pill {
            display: inline-block;
            background: #eef2ff;
            color: #6366f1;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .supplier-preview {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
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
            background: #10b981;
            color: white;
        }

        .btn-reject {
            background: #ef4444;
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
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <script src="https://kit.fontawesome.com/7867607d9e.js" crossorigin="anonymous"></script>
</head>

<body>
    <aside class="sidebar">
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

    <main class="main">
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
                                    <div class="notif-item" onclick='openCompanyModal(<?php echo json_encode($company, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <div class="notif-item-logo">
                                            <?php if (!empty($company['company_logo'])): ?>
                                                <img style="width: 100%; height: 100%; object-fit: cover;" src="../uploads/shops/<?php echo $company['supplier_id']; ?>/<?php echo $company['company_logo']; ?>" alt="Logo">
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
            <div class="modal-body" id="modalContent">
            </div>
            <div class="modal-footer">
                <button class="btn btn-reject" onclick="processCompany('reject')">Reject</button>
                <button class="btn btn-accept" onclick="processCompany('accept')">Accept</button>
            </div>
        </div>

        <script>
            // --- 1. Dropdown Logic ---
            const notifBtn = document.getElementById('notifBtn');
            const notifDropdown = document.getElementById('notifDropdown');

            notifBtn.addEventListener('click', (e) => {
                if (e.target.closest('.notif-dropdown')) return;
                notifDropdown.classList.toggle('show');
            });

            document.addEventListener('click', (e) => {
                if (!notifBtn.contains(e.target)) {
                    notifDropdown.classList.remove('show');
                }
            });

            // --- 2. Modal Logic ---
            let currentCompanyId = null;

            function openCompanyModal(companyData) {
                currentCompanyId = companyData.company_id;
                const modal = document.getElementById('companyModal');
                const overlay = document.getElementById('modalOverlay');
                const content = document.getElementById('modalContent');

                notifDropdown.classList.remove('show');

                // Process Tags
                let tagsHtml = '';
                if (companyData.tags) {
                    const tags = companyData.tags.split(',');
                    tags.forEach(tag => {
                        tagsHtml += `<span class="tag-pill">${tag.trim()}</span>`;
                    });
                }

                // Handle Image Paths in JS safely
                const logoPath = companyData.company_logo ?
                    `../uploads/shops/${companyData.supplier_id}/${companyData.company_logo}` :
                    `https://ui-avatars.com/api/?name=${encodeURIComponent(companyData.company_name)}&background=random`;

                const supplierPath = companyData.supplier_image ?
                    `../assets/customer_profiles/${companyData.supplier_image}` :
                    `https://ui-avatars.com/api/?name=${encodeURIComponent(companyData.supplier_name)}&background=random`;

                content.innerHTML = `
                    <div class="info-group" style="text-align:center;">
                        <div style="width:80px; height:80px; background:#eee; border-radius:12px; margin:0 auto 10px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                            <img style="width: 100%; height: 100%; object-fit: cover;" src="${logoPath}" alt="Company Logo">
                        </div>
                        <h3 style="margin:0;">${companyData.company_name}</h3>
                        <small style="color:#888;">Created: ${companyData.created_at}</small>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Description</div>
                        <p style="font-size:0.9rem; color:#444;">${companyData.description}</p>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Tags</div>
                        <div>${tagsHtml}</div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Contact Info</div>
                        <p style="font-size:0.9rem; margin-bottom:5px;"><i class="fas fa-map-marker-alt" style="width:20px"></i> ${companyData.address}</p>
                        <p style="font-size:0.9rem;"><i class="fas fa-phone" style="width:20px"></i> ${companyData.phone}</p>
                    </div>

                    <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">

                    <div class="info-group">
                        <div class="info-label">Supplier Information</div>
                        <div class="supplier-preview">
                            <img src="${supplierPath}" alt="Supplier">
                            <div>
                                <div style="font-weight:600; font-size:0.9rem;">${companyData.supplier_name}</div>
                                <div style="font-size:0.8rem; color:#666;">${companyData.supplier_email}</div>
                            </div>
                        </div>
                    </div>
                `;

                modal.classList.add('active');
                overlay.classList.add('active');
            }

            function closeModal() {
                document.getElementById('companyModal').classList.remove('active');
                document.getElementById('modalOverlay').classList.remove('active');
            }

            // --- 3. AJAX Process Logic ---
            function processCompany(action) {
                if (!currentCompanyId) return;
                if (!confirm(`Are you sure you want to ${action} this company?`)) return;

                const formData = new FormData();
                formData.append('company_id', currentCompanyId);
                formData.append('action', action);

                // Note: Ensure the path to process_company.php is correct relative to the current page
                fetch('utils/process_company.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            closeModal();
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Request failed. Check console for details.');
                    });
            }
        </script>
</body>

</html>