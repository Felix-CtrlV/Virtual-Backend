<?php
function _admin_is_mobile() {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return (bool) preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|tablet/i', $ua);
}
if (!_admin_is_mobile()) return;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desktop Required — Malltiverse Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            color: #e2e8f0;
            padding: 24px;
            text-align: center;
        }
        .card {
            max-width: 420px;
            width: 100%;
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.15);
            border-radius: 20px;
            padding: 48px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        }
        .icon-wrap {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.2));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            font-size: 36px;
        }
        h1 {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #f1f5f9;
        }
        p {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        .hint {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 20px;
        }
        .brand {
            margin-top: 28px;
            font-size: 0.8rem;
            color: #475569;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap"><i class="fas fa-desktop"></i></div>
        <h1>Desktop or laptop required</h1>
        <p>The Malltiverse admin panel is designed for a larger screen and full keyboard for security and the best experience.</p>
        <p class="hint">You can bookmark this page and sign in from your computer.</p>
        <div class="brand">Malltiverse - Admin</div>
    </div>
</body>
</html>
<?php exit; ?>
