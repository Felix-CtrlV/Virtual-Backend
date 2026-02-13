<?php
session_start();
// Adjust the path to your dbconfig as needed. 
// Assuming banned.php is in the root and config is in BackEnd/config/
require_once '../BackEnd/config/dbconfig.php'; 

// 1. Check if we have a flagged user in the session
if (!isset($_SESSION['banned_user'])) {
    header("Location: index.php");
    exit();
}

$entity_id   = $_SESSION['banned_user']['id'];
$entity_type = $_SESSION['banned_user']['type']; // 'supplier' or 'customer'

// 2. Fetch Ban Details + Admin Username
// We join banned_list with the admin table to get the username instead of ID
$sql = "SELECT b.reason, b.banned_at, b.banned_until, a.username 
        FROM banned_list b
        LEFT JOIN admins a ON b.banned_by = a.adminid
        WHERE b.entity_id = ? AND b.entity_type = ? 
        ORDER BY b.banned_at DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $entity_id, $entity_type); // "i" for int id, "s" for string type
$stmt->execute();
$result = $stmt->get_result();
$ban_data = $result->fetch_assoc();

if (!$ban_data) {
    die("Error: Account is suspended, but no ban record was found. Please contact support.");
}

// 3. Date & Countdown Logic
$start_date = new DateTime($ban_data['banned_at']);
$end_date   = new DateTime($ban_data['banned_until']);
$now        = new DateTime();

$is_active = $end_date > $now;
$interval  = $now->diff($end_date);

$remaining_text = "";
if ($is_active) {
    $parts = [];
    if ($interval->y > 0) $parts[] = $interval->y . ' yr';
    if ($interval->m > 0) $parts[] = $interval->m . ' mo';
    if ($interval->d > 0) $parts[] = $interval->d . ' days';
    if ($interval->h > 0) $parts[] = $interval->h . ' hrs';
    
    // Show top 2 significant units (e.g., "1 mo, 2 days")
    $remaining_text = implode(', ', array_slice($parts, 0, 2));
} else {
    $remaining_text = "Ban Expired";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <style>
        :root {
            --bg-color: #0f1117;
            --card-bg: #1e2128;
            --accent-red: #ff4d4d;
            --text-main: #ffffff;
            --text-muted: #a0a3b1;
            --font-stack: 'Inter', system-ui, sans-serif;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font-stack);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .ban-card {
            background: var(--card-bg);
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            border: 1px solid #2f3342;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            text-align: center;
        }
        .ban-header {
            background: rgba(255, 77, 77, 0.1);
            padding: 30px;
            border-bottom: 1px solid rgba(255, 77, 77, 0.2);
        }
        .icon-lock svg {
            fill: var(--accent-red);
            width: 40px;
            height: 40px;
            margin-bottom: 15px;
        }
        h1 { margin: 0; font-size: 24px; }
        .ban-body { padding: 30px; text-align: left; }
        .reason-box {
            background: rgba(255,255,255,0.05);
            padding: 15px;
            border-left: 3px solid var(--accent-red);
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            display: block;
            margin-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .timer-badge {
            background: #2b2f3a;
            padding: 12px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }
        .timer-val { color: var(--accent-red); font-weight: bold; }
        .ban-footer {
            padding: 15px;
            background: rgba(0,0,0,0.2);
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="ban-card">
        <div class="ban-header">
            <div class="icon-lock">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C9.243 2 7 4.243 7 7V10H6C4.897 10 4 10.897 4 12V20C4 21.103 4.897 22 6 22H18C19.103 22 20 21.103 20 20V12C20 10.897 19.103 10 18 10H17V7C17 4.243 14.757 2 12 2ZM9 7C9 5.346 10.346 4 12 4C13.654 4 15 5.346 15 7V10H9V7ZM12 17C11.448 17 11 16.552 11 16C11 15.448 11.448 15 12 15C12.552 15 13 15.448 13 16C13 16.552 12.552 17 12 17Z" />
                </svg>
            </div>
            <h1>Account Suspended</h1>
            <div class="subtitle">Access to this resource has been disabled.</div>
        </div>
        <div class="ban-body">
            <div class="reason-box">
                <span class="label">Reason</span>
                <div><?php echo htmlspecialchars($ban_data['reason']); ?></div>
            </div>
            <div class="info-grid">
                <div>
                    <span class="label">Banned By</span>
                    <div><?php echo htmlspecialchars($ban_data['username']); ?></div>
                </div>
                <div>
                    <span class="label">Date Issued</span>
                    <div><?php echo $start_date->format('M d, Y'); ?></div>
                </div>
            </div>
            <div class="timer-badge">
                <span>Time Remaining:</span>
                <span class="timer-val"><?php echo $remaining_text; ?></span>
            </div>
        </div>
        <div class="ban-footer">
            &copy; <?php echo date('Y'); ?> Malltiverse Security System
        </div>
    </div>
</body>
</html>