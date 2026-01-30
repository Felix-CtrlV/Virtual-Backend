<?php
session_start();
require_once '../BackEnd/config/dbconfig.php'; // Update path as needed

// Check Auth
if (!isset($_SESSION['customer_id'])) {
    header("Location: customerLogin.php");
    exit();
}

$id = $_SESSION['customer_id'];
$msg = "";
$msgType = "";

// Determine active tab (default to 'profile')
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. UPDATE PROFILE
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        $img_sql_part = "";
        $filename = "";
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "assets/customer_profiles/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            $filename = time() . "_" . basename($_FILES["profile_image"]["name"]);
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $img_sql_part = ", image = ?";
                $_SESSION['user_image'] = $filename; 
            }
        }

        $query = "UPDATE customers SET name=?, email=?, phone=?, address=?";
        if ($img_sql_part) $query .= $img_sql_part;
        $query .= " WHERE customer_id=?";

        $stmt = $conn->prepare($query);

        if ($img_sql_part) {
            $stmt->bind_param("sssssi", $name, $email, $phone, $address, $filename, $id);
        } else {
            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $id);
        }

        if ($stmt->execute()) {
            $msg = "Profile updated successfully.";
            $msgType = "success";
        } else {
            $msg = "Error updating profile.";
            $msgType = "error";
        }
        $activeTab = 'profile';
    }

    // 2. CHANGE PASSWORD
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $dbHash = $row['password'];

        if (password_verify($currentPass, $dbHash)) {
            if ($newPass === $confirmPass) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE customers SET password=? WHERE customer_id=?");
                $updateStmt->bind_param("si", $newHash, $id);
                
                if ($updateStmt->execute()) {
                    $msg = "Password changed successfully.";
                    $msgType = "success";
                } else {
                    $msg = "Database error occurred.";
                    $msgType = "error";
                }
            } else {
                $msg = "New passwords do not match.";
                $msgType = "error";
            }
        } else {
            $msg = "Current password is incorrect.";
            $msgType = "error";
        }
        $activeTab = 'security';
    }
}

// --- FETCH USER DATA ---
$stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$currentImg = $user['image'] ? "assets/customer_profiles/" . $user['image'] : "assets/default-user.png";

// --- FETCH NOTIFICATIONS (MESSAGES) IF TAB IS ACTIVE ---
$notifications = [];
if ($activeTab === 'notifications') {
    // Join with companies to get company name
    $sql = "SELECT cm.message_id, cm.message, cm.status, cm.created_at, c.company_name 
            FROM contact_messages cm 
            JOIN companies c ON cm.company_id = c.company_id 
            WHERE cm.customer_id = ? 
            ORDER BY cm.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* GitHub-Style Settings CSS */
        :root {
            --bg-color: #0d1117;
            --border-color: #30363d;
            --text-primary: #c9d1d9;
            --text-secondary: #8b949e;
            --btn-primary: #238636;
            --btn-hover: #2ea043;
            --btn-danger: #da3633;
            --input-bg: #010409;
            --card-bg: #161b22;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            padding-top: 40px;
        }

        .container {
            display: flex;
            width: 1200px;
            max-width: 95%;
            gap: 30px;
        }

        /* Sidebar */
        .sidebar { width: 240px; flex-shrink: 0; }
        .menu-item {
            display: block; padding: 10px 15px; color: var(--text-primary);
            text-decoration: none; border-radius: 6px; font-size: 14px; margin-bottom: 4px;
        }
        .menu-item.active { background: #161b22; font-weight: 600; border-left: 2px solid #f78166; }
        .menu-item:hover:not(.active) { background: #161b22; }

        /* Main Content */
        .content { flex: 1; }
        .header { border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 24px; }
        h1 { margin: 0; font-size: 24px; font-weight: 400; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 8px 12px; background: var(--input-bg);
            border: 1px solid var(--border-color); border-radius: 6px;
            color: var(--text-primary); font-size: 14px; box-sizing: border-box;
            transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: #58a6ff; box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.3); }
        .helper-text { font-size: 12px; color: var(--text-secondary); margin-top: 6px; }
        
        /* Buttons & Alerts */
        .btn-submit {
            background-color: var(--btn-primary); color: #ffffff; border: 1px solid rgba(240, 246, 252, 0.1);
            border-radius: 6px; padding: 8px 16px; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s;
        }
        .btn-submit:hover { background-color: var(--btn-hover); }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert.success { background: rgba(46, 160, 67, 0.15); border: 1px solid rgba(46, 160, 67, 0.4); color: #3fb950; }
        .alert.error { background: rgba(218, 54, 51, 0.15); border: 1px solid rgba(248, 81, 73, 0.4); color: #f85149; }
        .back-link { margin-bottom: 20px; display: inline-block; color: #58a6ff; text-decoration: none; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
        .profile-pic-section { display: flex; gap: 20px; align-items: center; margin-bottom: 25px; }
        .avatar-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color); }
        .file-upload-btn { background: #21262d; border: 1px solid rgba(240, 246, 252, 0.1); color: #c9d1d9; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 12px; }

        /* --- NOTIFICATION STYLES --- */
        .notif-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .notif-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .company-name { color: #58a6ff; font-weight: 600; }
        
        .status-pill {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            border: 1px solid transparent;
        }
        .status-pending { color: #d29922; border-color: rgba(210,153,34,0.4); background: rgba(210,153,34,0.1); }
        .status-replied { color: #3fb950; border-color: rgba(63,185,80,0.4); background: rgba(63,185,80,0.1); }

        .message-box {
            background: #0d1117;
            border: 1px solid var(--border-color);
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .reply-container {
            margin-top: 15px;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }
        .reply-header { font-size: 12px; color: var(--text-secondary); margin-bottom: 5px; }
        .reply-box {
            background: rgba(88, 166, 255, 0.1); /* Subtle blue tint */
            border: 1px solid rgba(88, 166, 255, 0.3);
            color: var(--text-primary);
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <a href="index.html" class="back-link">← Back to Mall</a>
        <nav>
            <a href="?tab=profile" class="menu-item <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">Public Profile</a>
            <a href="?tab=security" class="menu-item <?php echo $activeTab === 'security' ? 'active' : ''; ?>">Account Security</a>
            <a href="?tab=notifications" class="menu-item <?php echo $activeTab === 'notifications' ? 'active' : ''; ?>">Notifications</a>
        </nav>
    </div>

    <div class="content">
        <?php if ($msg): ?>
            <div class="alert <?php echo $msgType; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($activeTab === 'profile'): ?>
            <div class="header"><h1>Public Profile</h1></div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="profile-pic-section">
                    <img src="<?php echo $currentImg; ?>" alt="Profile" class="avatar-preview">
                    <div>
                        <label style="margin-bottom: 5px;">Profile Picture</label>
                        <input type="file" name="profile_image" id="fileInput" hidden accept="image/*">
                        <button type="button" class="file-upload-btn" onclick="document.getElementById('fileInput').click()">Change Picture</button>
                        <div class="helper-text">JPG, GIF or PNG. Max size 2MB.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Public Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                </div>
                <button type="submit" class="btn-submit">Update Profile</button>
            </form>
        <?php endif; ?>

        <?php if ($activeTab === 'security'): ?>
            <div class="header"><h1>Account Security</h1></div>
            <div class="section-desc" style="color:var(--text-secondary); margin-bottom:20px;">Manage your password and security preferences.</div>

            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-submit">Change Password</button>
            </form>
        <?php endif; ?>

        <?php if ($activeTab === 'notifications'): ?>
            <div class="header"><h1>Notifications</h1></div>
            
            <?php if (empty($notifications)): ?>
                <div style="color: var(--text-secondary);">No messages sent yet.</div>
            <?php else: ?>
                
                <?php foreach ($notifications as $note): ?>
                    <?php 
                        // Status Logic
                        $statusClass = (strtolower($note['status']) == 'replied') ? 'status-replied' : 'status-pending'; 
                        $statusLabel = ucfirst($note['status']);
                    ?>
                    
                    <div class="notif-card">
                        <div class="notif-header">
                            <span>Sent to <span class="company-name"><?php echo htmlspecialchars($note['company_name']); ?></span> on <?php echo date('M d, Y', strtotime($note['created_at'])); ?></span>
                            <span class="status-pill <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                        </div>
                        
                        <div class="message-box">
                            <strong>You:</strong> <?php echo htmlspecialchars($note['message']); ?>
                        </div>

                        <?php 
                        $msgId = $note['message_id'];
                        $replySql = "SELECT * FROM contact_replies WHERE message_id = ?";
                        $rStmt = $conn->prepare($replySql);
                        $rStmt->bind_param("i", $msgId);
                        $rStmt->execute();
                        $replies = $rStmt->get_result();

                        if ($replies->num_rows > 0): 
                            while($reply = $replies->fetch_assoc()):
                        ?>
                            <div class="reply-container">
                                <div class="reply-header">
                                    <i class="fas fa-reply"></i> Reply from Supplier
                                </div>
                                <div class="reply-box">
                                    <?php echo htmlspecialchars($reply['reply_text']); ?>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        <?php endif; ?>
        
    </div>
</div>

<script>
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function(event) {
            if(event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.avatar-preview').src = e.target.result;
                }
                reader.readAsDataURL(event.target.files[0]);
            }
        });
    }
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>