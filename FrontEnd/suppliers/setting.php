<?php
// session_start(); // Ensure session is started somewhere in your app flow (e.g., nav.php)
include("partials/nav.php");

// =========================================
// 1. HELPER FUNCTIONS & INITIALIZATION
// =========================================
$supplierid = $_SESSION["supplierid"];
$msg = "";
$msg_type = "";

// Helper to determine if a file is video based on extension
function isVideoFile($filename)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
}

// Fetch Supplier & Shop Assets Data
$stmt = $conn->prepare("
    SELECT 
    s.*,
    c.*,
    c.description AS company_description,
    sa.logo,
    sa.banner,
    sa.primary_color,
    sa.secondary_color,
    sa.about AS shop_about,
    sa.description AS shop_description,
    sa.template_type
FROM suppliers s
LEFT JOIN companies c 
    ON c.supplier_id = s.supplier_id AND c.status = 'active'
LEFT JOIN shop_assets sa 
    ON s.supplier_id = sa.supplier_id
WHERE s.supplier_id = ?;

");
$stmt->bind_param("i", $supplierid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$company_id = $user['company_id']; // Needed for fetching messages

$banner_string = $user["banner"];
$banners = explode(",", $banner_string);
$banner_count = count($banners);

for ($i = 0; $i < $banner_count; $i++) {
    ${"banner" . ($i + 1)} = $banners[$i];
}

// Fetch Available Templates
$templates = [];
$templatesResult = $conn->query("SELECT * FROM templates");
if ($templatesResult->num_rows > 0) {
    while ($t = $templatesResult->fetch_assoc()) {
        $templates[] = $t;
    }
}

// Define paths for banner display logic
$bannerPathRel = '../uploads/shops/' . $supplierid . '/' . ($banner1 ?? '');
// Check if file actually exists on server to avoid broken links
$bannerExists = !empty($user['banner']) && file_exists($_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/' . $bannerPathRel);


// =========================================
// 2. HANDLE FORM SUBMISSIONS
// =========================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- A. UPDATE PROFILE ---
    if (isset($_POST['save_profile'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        // 1️⃣ Update suppliers table (name, email)
        $upd1 = $conn->prepare("UPDATE suppliers SET name=?, email=? WHERE supplier_id=?");
        $upd1->bind_param("ssi", $name, $email, $supplierid);

        // 2️⃣ Update companies table (phone, address)
        $upd2 = $conn->prepare("UPDATE companies SET phone=?, address=? WHERE supplier_id=? AND status='active'");
        $upd2->bind_param("ssi", $phone, $address, $supplierid);

        if ($upd1->execute() && $upd2->execute()) {
            $msg = "Profile updated successfully.";
            $msg_type = "success";

            // Update local user array
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;
        } else {
            $msg = "Error updating profile.";
            $msg_type = "error";
        }
    }


    // --- B. UPDATE COMPANY ---
    if (isset($_POST['save_company'])) {
        $c_name = $_POST['company_name'];
        $tags = $_POST['tags'];
        $desc = $_POST['description'];
        $upd = $conn->prepare("UPDATE suppliers SET company_name=?, tags=?, description=? WHERE supplier_id=?");
        $upd->bind_param("sssi", $c_name, $tags, $desc, $supplierid);
        if ($upd->execute()) {
            $msg = "Company details updated.";
            $msg_type = "success";
            $user['company_name'] = $c_name;
            $user['tags'] = $tags;
            $user['description'] = $desc;
        }
    }

    // --- C. SECURITY (Password Change with Validation) ---
    if (isset($_POST['save_security'])) {
        $curr_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $conf_pass = $_POST['confirm_password'];

        // 1. Fetch current hashed password from DB
        $stmt_pw = $conn->prepare("SELECT password FROM suppliers WHERE supplier_id = ?");
        $stmt_pw->bind_param("i", $supplierid);
        $stmt_pw->execute();
        $res_pw = $stmt_pw->get_result()->fetch_assoc();
        $db_hash = $res_pw['password'];
        $stmt_pw->close();

        // 2. Verify current password input against DB hash
        if (password_verify($curr_pass, $db_hash)) {
            // 3. Check if new passwords match and aren't empty
            if (!empty($new_pass) && ($new_pass === $conf_pass)) {
                // 4. Hash new password and update
                $new_hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                $upd = $conn->prepare("UPDATE suppliers SET password=? WHERE supplier_id=?");
                $upd->bind_param("si", $new_hashed, $supplierid);
                if ($upd->execute()) {
                    $msg = "Password changed successfully.";
                    $msg_type = "success";
                } else {
                    $msg = "Database error during update.";
                    $msg_type = "error";
                }
            } else {
                $msg = "New passwords do not match or cannot be empty.";
                $msg_type = "error";
            }
        } else {
            $msg = "Current password is incorrect.";
            $msg_type = "error";
        }
    }

    // --- D. CUSTOMIZE (Visuals & Assets) ---
    if (isset($_POST['save_customize'])) {
        // Basic inputs
        $p_color = $_POST['primary_color'];
        $s_color = $_POST['secondary_color'];
        $shop_about = $_POST['shop_about'];
        $shop_desc = $_POST['shop_description'];
        $media_mode = $_POST['media_mode']; // 'image' or 'video'
        $selected_template = $_POST['template_id'] ?? $user['template_id'];

        $target_dir = "../uploads/shops/" . $supplierid . "/";
        if (!is_dir($target_dir))
            mkdir($target_dir, 0777, true);

        // 1. Handle Logo Upload
        $logo_sql = "";
        if (!empty($_FILES['logo']['name'])) {
            if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logo_name = time() . "_logo_" . basename($_FILES['logo']['name']);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_dir . $logo_name)) {
                    $logo_sql = ", logo = '$logo_name'";
                    $user['logo'] = $logo_name; // Update local var
                } else {
                    $msg = "Error moving logo file. Check folder permissions.";
                    $msg_type = "error";
                }
            } else {
                $msg = "Logo upload error code: " . $_FILES['logo']['error'];
                $msg_type = "error";
            }
        }

        // 2. Handle Banner Upload (Image OR Video)
        $banner_sql = "";
        if (!empty($_FILES['banner']['name'])) {
            // Check specifically for file size errors
            if ($_FILES['banner']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['banner']['error'] === UPLOAD_ERR_FORM_SIZE) {
                $msg = "File is too large! Check 'upload_max_filesize' in php.ini.";
                $msg_type = "error";
            } elseif ($_FILES['banner']['error'] === UPLOAD_ERR_OK) {
                $banner_name = time() . "_bn_" . basename($_FILES['banner']['name']);
                // Only update SQL if file move SUCCEEDS
                if (move_uploaded_file($_FILES['banner']['tmp_name'], $target_dir . $banner_name)) {
                    $banner_sql = ", banner = '$banner_name'";
                    $user['banner'] = $banner_name;
                    // Update display logic for this request
                    $bannerPathRel = '../uploads/shops/' . $supplierid . '/' . $banner_name;
                    $bannerExists = true;
                } else {
                    $msg = "Failed to save file. Check folder permissions.";
                    $msg_type = "error";
                }
            } else {
                $msg = "Banner upload error code: " . $_FILES['banner']['error'];
                $msg_type = "error";
            }
        }

        // 3. Update Database (Only if no critical upload error occurred)
        if ($msg_type !== "error") {
            $sql_final = "UPDATE shop_assets SET primary_color=?, secondary_color=?, about=?, description=?, template_type=? $logo_sql $banner_sql WHERE supplier_id=?";
            $stmt2 = $conn->prepare($sql_final);
            $stmt2->bind_param("sssssi", $p_color, $s_color, $shop_about, $shop_desc, $media_mode, $supplierid);

            if ($stmt2->execute()) {
                $msg = "Shop customization saved!";
                $msg_type = "success";

                // Refresh local variables for immediate UI update
                $user['primary_color'] = $p_color;
                $user['secondary_color'] = $s_color;
                $user['shop_about'] = $shop_about;
                $user['shop_description'] = $shop_desc;
                $user['template_type'] = $media_mode;
                $user['template_id'] = $selected_template;
            } else {
                $msg = "Database Error: " . $stmt2->error;
                $msg_type = "error";
            }
            $stmt2->close();

            // Update template_id in suppliers table
            $stmt3 = $conn->prepare("UPDATE companies SET template_id=? WHERE supplier_id=?");
            $stmt3->bind_param("ii", $selected_template, $supplierid);
            $stmt3->execute();
            $stmt3->close();
        }
    }

    // --- E. HANDLE MESSAGE REPLY ---
    if (isset($_POST['send_reply'])) {
        $msg_id = $_POST['reply_message_id'];
        $reply_text = trim($_POST['reply_text']);
        
        if (!empty($reply_text)) {
            // 1. Insert into contact_replies
            $stmt_reply = $conn->prepare("INSERT INTO contact_replies (message_id, supplier_id, reply_text, created_at) VALUES (?, ?, ?, NOW())");
            $stmt_reply->bind_param("iis", $msg_id, $supplierid, $reply_text);
            
            if ($stmt_reply->execute()) {
                // 2. Update status in contact_messages
                $stmt_status = $conn->prepare("UPDATE contact_messages SET status='replied' WHERE message_id=?");
                $stmt_status->bind_param("i", $msg_id);
                $stmt_status->execute();
                
                $msg = "Reply sent successfully.";
                $msg_type = "success";
            } else {
                $msg = "Error sending reply.";
                $msg_type = "error";
            }
        } else {
            $msg = "Reply text cannot be empty.";
            $msg_type = "error";
        }
    }
}

// =========================================
// 3. FETCH NOTIFICATIONS (MESSAGES)
// =========================================
$notifications = [];
if (!empty($company_id)) {
    // Assuming you have a 'customers' table. Adjust column names (c.name, c.email) as per your DB
    $sql_msgs = "
        SELECT 
            cm.message_id, 
            cm.customer_id, 
            cm.message, 
            cm.status, 
            cm.created_at,
            c.name AS customer_name,
            c.email AS customer_email
        FROM contact_messages cm
        LEFT JOIN customers c ON cm.customer_id = c.customer_id
        WHERE cm.company_id = ?
        ORDER BY cm.created_at DESC
    ";
    $stmt_msgs = $conn->prepare($sql_msgs);
    $stmt_msgs->bind_param("i", $company_id);
    $stmt_msgs->execute();
    $res_msgs = $stmt_msgs->get_result();
    while ($row = $res_msgs->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt_msgs->close();
}
?>

<!DOCTYPE html>
<html lang="en">

    <style>
        /* --- SCOPED CSS FOR SETTINGS PAGE --- */
        :root {
            --glass-bg: rgba(255, 255, 255, 0.95);
            --accent: <?= $user['primary_color'] ?? '#333' ?>;
            --accent-hover: color-mix(in srgb, var(--accent) 80%, black);
            --pill-bg: #e0e0e0;
        }

        body {
            background-color: #f4f7f6;
        }

        /* 1. Main Container */
        .settings-wrapper {
            width: 100%;
            margin: 0px;
            background-image: radial-gradient(circle at 5% 10%, color-mix(in srgb, var(--primary) 10%, transparent) 0%, transparent 35%), radial-gradient(circle at 90% 90%, color-mix(in srgb, var(--primary) 30%, transparent) 0%, transparent 40%);
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(20px);
            min-height: 85vh;
        }

        /* 2. CHANNEL HEADER */
        .channel-header {
            position: relative;
            background: #f0f0f0;
        }

        .banner-area {
            width: 100%;
            height: 280px;
            background-color: #ddd;
            position: relative;
            overflow: hidden;
        }

        .banner-media {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 0;
        }

        .profile-section {
            display: flex;
            align-items: flex-end;
            padding: 0 50px;
            margin-top: -75px;
            position: relative;
            z-index: 2;
            margin-bottom: 25px;
        }

        .profile-img-container {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 6px solid #fff;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        .profile-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .channel-info {
            margin-left: 30px;
            margin-bottom: 20px;
            color: #222;
            text-shadow: 2px 2px 4px rgba(255, 255, 255, 0.8);
        }

        .channel-info h1 {
            font-size: 2.2rem;
            margin: 0;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .channel-info span {
            color: #555;
            font-size: 1rem;
            font-weight: 500;
        }

        /* 3. NAVIGATION TABS */
        .tabs-nav {
            display: flex;
            padding: 0 50px;
            border-bottom: 2px solid #f0f0f0;
            gap: 35px;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 15px 5px;
            font-size: 1.05rem;
            font-weight: 600;
            color: #777;
            cursor: pointer;
            position: relative;
            transition: 0.3s;
        }

        .tab-btn:hover {
            color: #000;
        }

        .tab-btn.active {
            color: var(--accent);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--accent);
            border-radius: 4px 4px 0 0;
        }

        /* 4. CONTENT AREA & FORMS */
        .tab-content {
            padding: 50px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .input-field {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #eee;
            border-radius: 12px;
            font-size: 1rem;
            transition: 0.3s;
            background: #fcfcfc;
            color: #333;
        }

        .input-field:focus {
            border-color: var(--accent);
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.03);
        }

        textarea.input-field {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        /* Customization Specifics */
        .color-picker-row {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .color-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        input[type="color"] {
            -webkit-appearance: none;
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 18px;
            cursor: pointer;
            padding: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
        input[type="color"]::-webkit-color-swatch { border: none; border-radius: 18px; }

        .upload-row {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .upload-box {
            flex: 1;
            min-width: 300px;
            border: 3px dashed #ddd;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            background: #fdfdfd;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .upload-box:hover {
            border-color: var(--accent);
            background: #fff;
            transform: translateY(-3px);
        }

        .upload-icon {
            font-size: 2rem;
            color: #ccc;
            margin-bottom: 10px;
            display: block;
        }

        /* Media Preview Styles */
        .preview-media {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Segmented Control */
        .segmented-control {
            display: inline-grid;
            grid-template-columns: 1fr 1fr;
            background: var(--pill-bg);
            padding: 5px;
            border-radius: 50px;
            position: relative;
            user-select: none;
            width: 300px;
        }

        .segmented-control input[type="radio"] { display: none; }

        .seg-label {
            text-align: center;
            padding: 10px 20px;
            z-index: 2;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
            border-radius: 50px;
        }

        .seg-pill {
            position: absolute;
            top: 5px;
            left: 5px;
            width: calc(50% - 5px);
            height: calc(100% - 10px);
            background: #fff;
            border-radius: 50px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
            z-index: 1;
        }

        #mode-video:checked~.seg-pill { transform: translateX(100%); }
        #mode-image:checked+.seg-label, #mode-video:checked+.seg-label { color: var(--accent); }

        /* Template Grid */
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-top: 15px;
        }

        .template-card {
            border: 3px solid #eee;
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
            background: #fff;
        }

        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .template-card.selected {
            border-color: var(--accent);
            box-shadow: 0 0 0 5px color-mix(in srgb, var(--accent) 20%, transparent);
        }

        .template-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
            border-bottom: 1px solid #eee;
        }

        .template-info {
            padding: 12px;
            text-align: center;
            font-weight: 700;
            color: #444;
        }

        .template-radio { display: none; }

        /* Action Buttons & Alerts */
        .action-bar {
            margin-top: 40px;
            text-align: right;
            border-top: 2px solid #f0f0f0;
            padding-top: 25px;
        }

        .btn-save {
            background: var(--accent);
            color: #fff;
            padding: 14px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            cursor: pointer;
            font-weight: 700;
            transition: 0.2s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-save:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        .alert {
            padding: 15px 25px;
            margin: 30px 50px 0 50px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        /* --- NOTIFICATION TABLE STYLES --- */
        .msg-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .msg-item {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: box-shadow 0.2s;
        }
        .msg-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .msg-content h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .msg-meta {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 10px;
            display: block;
        }
        .msg-body {
            color: #555;
            line-height: 1.5;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-left: 10px;
        }
        .status-pending { background: #ffeeba; color: #856404; }
        .status-replied { background: #d1e7dd; color: #0f5132; }

        .btn-reply {
            background: #fff;
            border: 2px solid var(--accent);
            color: var(--accent);
            padding: 8px 16px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
            flex-shrink: 0;
            margin-left: 20px;
        }
        .btn-reply:hover {
            background: var(--accent);
            color: #fff;
        }

        /* --- MODAL STYLES --- */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }
        .modal-box {
            background: #fff;
            width: 90%;
            max-width: 500px;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            position: relative;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header h3 { margin: 0; }
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        .msg-preview {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #ddd;
            font-size: 0.95rem;
            color: #666;
            max-height: 100px;
            overflow-y: auto;
        }

    </style>
    
    <div class="settings-wrapper">

        <div class="channel-header">
            <div class="banner-area">
                <?php if ($bannerExists): ?>
                    <?php if (isVideoFile($user['banner'])): ?>
                        <video src="<?= $bannerPathRel ?>" autoplay muted loop playsinline class="banner-media"></video>
                    <?php else: ?>
                        <img src="<?= $bannerPathRel ?>" alt="Shop Banner" class="banner-media">
                    <?php endif; ?>
                <?php else: ?>
                    <img src="https://via.placeholder.com/1400x400?text=Upload+Your+Banner" alt="Placeholder Banner"
                        class="banner-media" style="opacity:0.3">
                <?php endif; ?>
            </div>

            <div class="profile-section">
                <div class="profile-img-container">
                    <img src="<?= !empty($user['logo']) ? '../uploads/shops/' . $supplierid . '/' . $user['logo'] : 'https://via.placeholder.com/160?text=Logo' ?>"
                        alt="Profile">
                </div>

                <div class="channel-info">
                    <h1><?= htmlspecialchars($user['company_name'] ?? 'My Shop') ?></h1>
                    <span>@<?= htmlspecialchars($user['name']) ?> • #<?= $supplierid ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert <?= $msg_type ?>">
                <span style="margin-right:10px; font-size:1.2rem;"><?= $msg_type == 'success' ? '✅' : '⚠️' ?></span>
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="tabs-nav">
            <button class="tab-btn active" onclick="openTab(event, 'profile')">Profile</button>
            <button class="tab-btn" onclick="openTab(event, 'company')">Company</button>
            <button class="tab-btn" onclick="openTab(event, 'notifications')">Notifications</button>
            <button class="tab-btn" onclick="openTab(event, 'security')">Security</button>
            <button class="tab-btn" onclick="openTab(event, 'customize')">Customize</button>
        </div>

        <div id="profile" class="tab-content active">
            <h3>Personal Information</h3>
            <p style="color:#777; margin-bottom:30px;">Manage your personal contact details used for account recovery
                and notifications.</p>

            <form method="POST">
                <div class="form-grid">
                    <div class="input-group">
                        <label>Username (Name)</label>
                        <input type="text" name="name" class="input-field"
                            value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="input-field"
                            value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="input-field"
                            value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>
                    <div class="input-group">
                        <label>Address</label>
                        <input type="text" name="address" class="input-field"
                            value="<?= htmlspecialchars($user['address']) ?>">
                    </div>
                </div>
                <div class="action-bar">
                    <button type="submit" name="save_profile" class="btn-save">Save Profile</button>
                </div>
            </form>
        </div>

        <div id="company" class="tab-content">
            <h3>Company Details</h3>
            <p style="color:#777; margin-bottom:30px;">Information displayed to customers and on official documents.</p>

            <form method="POST">
                <div class="form-grid">
                    <div class="input-group full-width">
                        <label>Company Name</label>
                        <input type="text" name="company_name" class="input-field"
                            value="<?= htmlspecialchars($user['company_name']) ?>" required
                            style="font-size: 1.2rem; font-weight: bold;">
                    </div>
                    <div class="input-group full-width">
                        <label>Tags (Categories)</label>
                        <input type="text" name="tags" class="input-field"
                            placeholder="e.g., Clothing, Electronics, Handmade"
                            value="<?= htmlspecialchars($user['tags']) ?>">
                    </div>
                    <div class="input-group full-width">
                        <label>Internal Description / Notes</label>
                        <textarea name="description" class="input-field"
                            placeholder="Brief overview of your business model..."><?= htmlspecialchars($user['description']) ?></textarea>
                    </div>
                </div>
                <div class="action-bar">
                    <button type="submit" name="save_company" class="btn-save">Save Company Info</button>
                </div>
            </form>
        </div>

        <div id="notifications" class="tab-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                <div>
                    <h3>Customer Messages</h3>
                    <p style="color:#777; margin:0;">View and reply to inquiries from your shop page.</p>
                </div>
            </div>

            <?php if (empty($notifications)): ?>
                <div style="text-align:center; padding:50px; color:#999; border:2px dashed #eee; border-radius:12px;">
                    <span style="font-size:2rem;">📭</span>
                    <p>No messages found.</p>
                </div>
            <?php else: ?>
                <ul class="msg-list">
                    <?php foreach ($notifications as $note): ?>
                        <li class="msg-item">
                            <div class="msg-content">
                                <h4>
                                    <?= htmlspecialchars($note['customer_name'] ?? 'Guest') ?>
                                    <span class="status-badge status-<?= $note['status'] ?>"><?= $note['status'] ?></span>
                                </h4>
                                <span class="msg-meta">
                                    <?= date('M d, Y h:i A', strtotime($note['created_at'])) ?> • 
                                    <?= htmlspecialchars($note['customer_email']) ?>
                                </span>
                                <div class="msg-body">
                                    <?= nl2br(htmlspecialchars($note['message'])) ?>
                                </div>
                            </div>
                            <?php if ($note['status'] !== 'replied'): ?>
                                <button class="btn-reply" 
                                    onclick="openReplyModal(
                                        '<?= $note['message_id'] ?>', 
                                        '<?= htmlspecialchars($note['customer_name'] ?? 'Customer', ENT_QUOTES) ?>', 
                                        '<?= htmlspecialchars(substr($note['message'], 0, 150) . '...', ENT_QUOTES) ?>'
                                    )">
                                    Reply ↩
                                </button>
                            <?php else: ?>
                                <button class="btn-reply" disabled style="opacity:0.5; cursor:default; border-color:#ccc; color:#999;">Replied</button>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div id="security" class="tab-content">
            <h3>Security & Login</h3>
            <p style="color:#777; margin-bottom:30px;">Protect your account by updating your password regularly.</p>

            <form method="POST" autocomplete="off">
                <div class="form-grid" style="max-width: 500px;">
                    <div class="input-group full-width"
                        style="background: #fff3cd; padding: 20px; border-radius: 12px; border: 1px solid #ffeeba;">
                        <label style="color: #856404;">Current Password (Required)</label>
                        <input type="password" name="current_password" class="input-field" required
                            placeholder="Enter current password to authorize changes">
                    </div>

                    <div class="input-group" style="display: flex; justify-content: space-between;">
                        <div>
                            <label>New Password</label>
                            <input type="password" name="new_password" class="input-field" placeholder="Min 8 characters">
                        </div>
                        <div>
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="input-field"
                            placeholder="Re-enter new password">
                        </div>
                    </div>
                </div>
                <div class="action-bar">
                    <button type="submit" name="save_security" class="btn-save"
                        style="background-color: #d9534f;">Update Password</button>
                </div>
            </form>
        </div>

        <div id="customize" class="tab-content">
            <h3>Store Front Customization</h3>
            <p style="color:#777; margin-bottom:30px;">Design the look and feel of your public shop page.</p>

            <form method="POST" enctype="multipart/form-data">

                <div class="input-group">
                    <label>Brand Theme Colors</label>
                    <div class="color-picker-row">
                        <div class="color-wrapper">
                            <input type="color" name="primary_color"
                                value="<?= htmlspecialchars($user['primary_color'] ?? '#333333') ?>">
                            <small>Primary</small>
                        </div>
                        <div class="color-wrapper">
                            <input type="color" name="secondary_color"
                                value="<?= htmlspecialchars($user['secondary_color'] ?? '#FFD55A') ?>">
                            <small>Secondary</small>
                        </div>
                    </div>
                </div>

                <hr style="border:0; border-top:2px solid #f0f0f0; margin:30px 0;">

                <div class="upload-row">
                    <div class="upload-box" id="logo_box" onclick="document.getElementById('upl_logo').click()">
                        <input type="file" name="logo" id="upl_logo" hidden accept="image/png, image/jpeg" 
                               onchange="handleFileSelect(this, 'logo_preview', 'image')">
                        
                        <div id="logo_preview">
                            <span class="upload-icon">📸</span>
                            <h4>Upload New Logo</h4>
                            <small class="text-muted">Square, PNG/JPG</small>
                        </div>
                    </div>

                    <div class="upload-box" id="banner_box" onclick="document.getElementById('upl_banner').click()">
                        <input type="file" name="banner" id="upl_banner" hidden accept="image/*,video/mp4,video/webm" 
                               onchange="handleFileSelect(this, 'banner_preview', 'mixed')">
                        
                        <div id="banner_preview">
                            <span class="upload-icon">🖼️🎥</span>
                            <h4>Upload New Banner</h4>
                            <small class="text-muted">Wide Image or Video</small>
                        </div>
                    </div>
                </div>

                <hr style="border:0; border-top:2px solid #f0f0f0; margin:30px 0;">

                <div class="form-grid">
                    <div class="input-group full-width">
                        <label style="margin-bottom:15px; display:block;">Default Banner Display Mode</label>
                        <div class="segmented-control">
                            <input type="radio" name="media_mode" value="image" id="mode-image"
                                <?= ($user['template_type'] ?? 'image') == 'image' ? 'checked' : '' ?>>
                            <label for="mode-image" class="seg-label" style="margin-bottom: 0px;">Image Mode</label>

                            <input type="radio" name="media_mode" value="video" id="mode-video"
                                <?= ($user['template_type'] ?? '') == 'video' ? 'checked' : '' ?>>
                            <label for="mode-video" class="seg-label" style="margin-bottom: 0px;">Video Mode</label>

                            <div class="seg-pill"></div>
                        </div>
                        <small style="display:block; margin-top:10px; color:#888;">This determines how your banner loads
                            initially on your storefront.</small>
                    </div>

                    <div class="input-group">
                        <label>Public 'About' Headline</label>
                        <textarea name="shop_about" class="input-field" style="height:100px;"
                            placeholder="e.g., Wear The Confidence"><?= htmlspecialchars($user['shop_about'] ?? '') ?></textarea>
                    </div>
                    <div class="input-group">
                        <label>Public Shop Description</label>
                        <textarea name="shop_description" class="input-field" style="height:100px;"
                            placeholder="Detailed description visible to customers..."><?= htmlspecialchars($user['shop_description'] ?? '') ?></textarea>
                    </div>
                </div>

                <hr style="border:0; border-top:2px solid #f0f0f0; margin:30px 0;">

                <div class="input-group full-width">
                    <label>Select Store Template Layout</label>
                    <div class="template-grid">
                        <?php foreach ($templates as $tpl): ?>
                            <label
                                class="template-card <?= ($user['template_id'] == $tpl['template_id']) ? 'selected' : '' ?>"
                                onclick="selectTemplate(this)">
                                <input type="radio" name="template_id" value="<?= $tpl['template_id'] ?>"
                                    class="template-radio" <?= ($user['template_id'] == $tpl['template_id']) ? 'checked' : '' ?>>
                                <img src="../assets/template_preview/<?= htmlspecialchars($tpl['preview_image']) ?>"
                                    alt="<?= $tpl['template_name'] ?>">
                                <div class="template-info">
                                    <?= htmlspecialchars($tpl['template_name']) ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="action-bar">
                    <button type="submit" name="save_customize" class="btn-save">Publish Changes</button>
                </div>
            </form>
        </div>

    </div>

    <div id="replyModal" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeReplyModal()">&times;</button>
            <div class="modal-header">
                <h3>Reply to <span id="modal_customer_name" style="color:var(--accent)">Customer</span></h3>
            </div>
            
            <form method="POST">
                <input type="hidden" name="reply_message_id" id="modal_message_id">
                
                <div class="msg-preview">
                    <strong>Message:</strong><br>
                    <span id="modal_message_text">...</span>
                </div>

                <div class="input-group">
                    <label>Your Reply</label>
                    <textarea name="reply_text" class="input-field" style="height:120px;" required placeholder="Type your response here..."></textarea>
                </div>

                <div style="text-align:right; margin-top:20px;">
                    <button type="button" onclick="closeReplyModal()" style="background:none; border:none; padding:10px 20px; cursor:pointer;">Cancel</button>
                    <button type="submit" name="send_reply" class="btn-save" style="padding:10px 30px; font-size:1rem;">Send Reply</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            // Hide all tabs
            const tabcontent = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }

            // Remove active class from buttons
            const tablinks = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }

            // Show current tab (with slight delay for smooth animation trigger)
            setTimeout(() => {
                document.getElementById(tabName).classList.add("active");
            }, 50);

            evt.currentTarget.classList.add("active");
        }

        // Visual feedback for Template Selection
        function selectTemplate(card) {
            document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
        }

        // Auto-hide alert messages after a few seconds
        const alertBox = document.querySelector('.alert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.opacity = '0';
                alertBox.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alertBox.remove(), 500);
            }, 4000);
        }

        // --- NEW: Preview Logic for File Uploads ---
        function handleFileSelect(input, previewId, type) {
            const file = input.files[0];
            if (!file) return;

            const container = document.getElementById(previewId);
            const fileUrl = URL.createObjectURL(file);
            
            // Clear existing content in the upload box
            container.innerHTML = ''; 

            // Determine if file is video or image
            const isVideo = file.type.startsWith('video/');
            let mediaElem;

            if (isVideo) {
                mediaElem = document.createElement('video');
                mediaElem.src = fileUrl;
                mediaElem.autoplay = true;
                mediaElem.muted = true;
                mediaElem.loop = true;
                mediaElem.className = 'preview-media'; // Used new CSS class
            } else {
                mediaElem = document.createElement('img');
                mediaElem.src = fileUrl;
                mediaElem.className = 'preview-media'; // Used new CSS class
            }

            // Add filename text below preview
            const nameInfo = document.createElement('div');
            nameInfo.style.fontWeight = 'bold';
            nameInfo.style.color = 'var(--accent)';
            nameInfo.style.fontSize = '0.9rem';
            nameInfo.textContent = "Selected: " + file.name;

            container.appendChild(mediaElem);
            container.appendChild(nameInfo);
        }

        // --- NEW: Modal Logic ---
        function openReplyModal(id, name, message) {
            document.getElementById('modal_message_id').value = id;
            document.getElementById('modal_customer_name').textContent = name;
            document.getElementById('modal_message_text').textContent = message;
            
            const modal = document.getElementById('replyModal');
            modal.style.display = 'flex';
        }

        function closeReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
        }

        // Close modal if clicking outside box
        window.onclick = function(event) {
            const modal = document.getElementById('replyModal');
            if (event.target == modal) {
                closeReplyModal();
            }
        }
    </script>
</body>
</html>