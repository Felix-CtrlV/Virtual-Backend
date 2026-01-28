<?php
// =========================================================
// 1. CONFIGURATION & MOCK DATA (Replace this with DB calls)
// =========================================================

// Set the timezone to match your server/user region
date_default_timezone_set('UTC');

// In a real scenario, you would parse your XML/CSV file here.
// For this preview, we simulate the data structure you provided:
// ban_id, entity_type, entity_id, reason, banned_at, banned_until, banned_by

$banned_users = [
    [
        'ban_id'       => 'BAN-8842-XJ',
        'entity_type'  => 'user',
        'entity_id'    => '1001',
        'reason'       => 'Violation of Community Standards (Section 4.2): Harassment.',
        'banned_at'    => '2023-10-25 14:30:00',
        'banned_until' => '2025-12-31 23:59:59', // Future date for demo
        'banned_by'    => 'Trust & Safety Team'
    ],
    // Add more rows here...
];

// SIMULATION: Let's assume the current user is ID 1001
$current_user_id = '1001'; 
$my_ban = null;

// Find the user in the "Database"
foreach ($banned_users as $ban) {
    if ($ban['entity_id'] === $current_user_id) {
        $my_ban = $ban;
        break;
    }
}

// If no ban found, redirect or show success (Optional)
if (!$my_ban) {
    die("Access Granted: User is not banned.");
}

// =========================================================
// 2. HELPER LOGIC (Date Formatting)
// =========================================================

$start_date = new DateTime($my_ban['banned_at']);
$end_date   = new DateTime($my_ban['banned_until']);
$now        = new DateTime();
$interval   = $now->diff($end_date);

// Format remaining time nicely
$remaining_text = "";
if ($end_date < $now) {
    $remaining_text = "Ban Expired";
    $is_active = false;
} else {
    $remaining_text = $interval->format('%a days, %h hours remaining');
    $is_active = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied | Account Suspended</title>
    <style>
        /* MODERN CSS RESET & VARIABLES */
        :root {
            --bg-color: #0f1117;       /* Dark Discord/GitHub-like bg */
            --card-bg: #1e2128;        /* Slightly lighter card */
            --accent-red: #ff4d4d;     /* Alert Red */
            --accent-dim: #3a1c1c;     /* Dim Red Background */
            --text-main: #ffffff;
            --text-muted: #a0a3b1;
            --border-color: #2f3342;
            --font-stack: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
            padding: 20px;
        }

        /* CARD CONTAINER */
        .ban-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 550px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* HEADER SECTION */
        .ban-header {
            background: var(--accent-dim);
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 77, 77, 0.2);
        }

        .icon-lock {
            width: 60px;
            height: 60px;
            background: rgba(255, 77, 77, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .icon-lock svg {
            fill: var(--accent-red);
            width: 32px;
            height: 32px;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: var(--text-muted);
            margin-top: 10px;
            font-size: 14px;
        }

        /* DETAILS BODY */
        .ban-body {
            padding: 30px;
        }

        /* REASON BOX */
        .reason-box {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--accent-red);
            margin-bottom: 25px;
        }

        .reason-label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .reason-text {
            font-size: 15px;
            line-height: 1.5;
        }

        /* DATA GRID */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-value {
            font-size: 14px;
            font-weight: 500;
        }

        /* TIMESTAMP BADGE */
        .timer-badge {
            background: #2b2f3a;
            color: var(--text-muted);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .timer-highlight {
            color: var(--accent-red);
            font-weight: bold;
        }

        /* FOOTER */
        .ban-footer {
            padding: 20px 30px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--text-muted);
        }

        .btn-contact {
            background: transparent;
            border: 1px solid var(--text-muted);
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-contact:hover {
            border-color: var(--text-main);
            color: var(--text-main);
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
                <span class="reason-label">Reason for suspension</span>
                <div class="reason-text">
                    <?php echo htmlspecialchars($my_ban['reason']); ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="reason-label">Ban ID</span>
                    <span class="info-value"><?php echo htmlspecialchars($my_ban['ban_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="reason-label">Authorized By</span>
                    <span class="info-value"><?php echo htmlspecialchars($my_ban['banned_by']); ?></span>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="reason-label">Date Issued</span>
                    <span class="info-value"><?php echo $start_date->format('M d, Y'); ?></span>
                </div>
                <div class="info-item">
                    <span class="reason-label">Expiry Date</span>
                    <span class="info-value"><?php echo $end_date->format('M d, Y'); ?></span>
                </div>
            </div>

            <div class="timer-badge">
                <span>Duration Remaining:</span>
                <span class="timer-highlight">
                    <?php echo $is_active ? $remaining_text : "Permanently Banned"; ?>
                </span>
            </div>

        </div>

        <div class="ban-footer">
            <span>&copy; <?php echo date('Y'); ?> Security System</span>
            <a href="mailto:" class="btn-contact">Contact Support</a> //admin email
        </div>
    </div>

</body>
</html>