<?php
include("partials/nav.php");

// --- Handle Crediverse return: payment_status=success ---
if (
    isset($_GET['payment_status']) &&
    $_GET['payment_status'] === 'success' &&
    isset($_SESSION['pending_rent'])
) {
    $pending = $_SESSION['pending_rent'];

    $ins = $conn->prepare("
        INSERT INTO rent_payments (company_id, paid_date, due_date, amount, status)
        VALUES (?, ?, ?, ?, 'paid')
    ");

    if ($ins) {
        $ins->bind_param(
            "issd",
            $pending['company_id'],
            $pending['paid_date'],
            $pending['due_date'],
            $pending['amount']
        );

        if ($ins->execute()) {
            unset($_SESSION['pending_rent']);
            $ins->close();
            // header("Location: rentpayment.php");
            exit;
        }
        $ins->close();
    }

    unset($_SESSION['pending_rent']);
    header("Location: rentpayment.php?error=insert_failed");
    exit;
}

// 0. FETCH COMPANY ID (Crucial step since rent_payments now links to company_id)
$compStmt = $conn->prepare("SELECT company_id, renting_price FROM companies WHERE supplier_id = ?");
$compStmt->bind_param("i", $supplierid);
$compStmt->execute();
$compRes = $compStmt->get_result()->fetch_assoc();
$compStmt->close();

$companyid = $compRes['company_id'];
// Base rent per month from companies.renting_price
$baseMonthlyRent = (float) ($compRes['renting_price'] ?? 1000);

// 1. Logic for Payment History (Using company_id)
$historyStmt = $conn->prepare("SELECT * FROM rent_payments WHERE company_id = ? ORDER BY paid_date DESC");
$historyStmt->bind_param("i", $companyid);
$historyStmt->execute();
$paymentHistory = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$historyStmt->close();

// 2. Logic for Financial Stats (Using company_id)
$totalRentPaidStmt = $conn->prepare("SELECT SUM(amount) as total_paid FROM rent_payments WHERE company_id = ?");
$totalRentPaidStmt->bind_param("i", $companyid);
$totalRentPaidStmt->execute();
$rentRow = $totalRentPaidStmt->get_result()->fetch_assoc();
$totalRentPaid = $rentRow['total_paid'] ?? 0;
$totalRentPaidStmt->close();

// 3. RE-CALCULATE CONTRACT STATUS (Override nav.php logic to ensure company_id usage)
$contractStmt = $conn->prepare('SELECT rp.paid_date AS contract_start, rp.due_date AS contract_end 
FROM rent_payments rp 
INNER JOIN (
    SELECT company_id, MAX(paid_date) AS latest_paid 
    FROM rent_payments 
    WHERE company_id = ? 
    GROUP BY company_id
) latest ON rp.company_id = latest.company_id AND rp.paid_date = latest.latest_paid;');
$contractStmt->bind_param('i', $companyid);
$contractStmt->execute();
$contractRow = $contractStmt->get_result()->fetch_assoc();
$contractStmt->close();

// Logic for Progress Bar & Due Date
$start = new DateTime($contractRow['contract_start'] ?? 'now');
$end = new DateTime($contractRow['contract_end'] ?? 'now');
$today = new DateTime();

$totalDays = $start->diff($end)->days;
$daysPassed = $start->diff($today)->days;

// Calculate diff correctly: Positive = Days Left, Negative = Overdue
$diffObj = $end->diff($today);
$diff = $diffObj->days;
if (!$diffObj->invert) {
    // If today is AFTER end date, invert is false (0), so we make diff negative
    $diff = -$diff;
}

$percent = ($totalDays > 0) ? (($totalDays - $diff) / $totalDays) * 100 : 0; // Approximate visual progress
// Or strictly based on time passed:
if($totalDays > 0) {
    // If diff is positive (days left), percent is days passed / total
    // If diff is negative (overdue), percent is 100
    if ($diff >= 0) {
        $percent = ($daysPassed / $totalDays) * 100;
    } else {
        $percent = 100;
    }
} else {
    $percent = 0;
}
$percent = max(0, min(100, round($percent)));

$statusColor = ($diff >= 0) ? 'var(--primary)' : '#cb4444';
$statusText = ($diff >= 0) ? $diff . ' Days Left' : abs($diff) . ' Days OVERDUE';
$progressPercent = ($diff >= 0) ? $percent : 100;
?>

<?php if (!empty($_GET['error'])): ?>
    <div class="rent-error" style="background:#fee;color:#c00;padding:12px;margin-bottom:20px;border-radius:8px;">
        <?php
        echo $_GET['error'] === 'no_company' ? 'Company not found.' : ($_GET['error'] === 'insert_failed' ? 'Payment recorded but something went wrong. Please check your history.' : 'An error occurred.');
        ?>
    </div>
<?php endif; ?>

<div class="rent-header">
    <div>
        <h1>Rent & Billing</h1>
        <p style="color:#666;">Manage your shop lease and payment history</p>
    </div>
    <div class="glass-panel" style="padding: 10px 20px;">
        <small>Current Status</small>
        <div style="font-weight:bold; color: <?= $statusColor ?>; display: flex; align-items: center; gap: 8px;">
            <div style="width: 10px; height: 10px; background: <?= $statusColor ?>; border-radius: 50%;"></div>
            <?= $diff >= 0 ? 'Settled' : 'Overdue' ?>
        </div>
    </div>
</div>

<div class="rent-stats-grid">
    <div class="rent-card">
        <div class="circle-progress"
            style="background: conic-gradient(<?= $statusColor ?> <?= $progressPercent ?>%, #ddd 0); width: 80px; height: 80px;">
            <div class="inner-circle" style="width: 65px; height: 65px; font-size: 0.9rem;">
                <?= $diff >= 0 ? $diff . 'd' : 'Overdue' ?>
            </div>
        </div>

        <div class="rent-details">
            <h3 id="due-label">Next Due Date</h3>
            <h2 id="due-date-display"><?= date('M d, Y', strtotime($contractRow['contract_end'] ?? 'now')) ?></h2>
            <small style="color: <?= $statusColor ?>; font-weight:bold;"><?= $statusText ?></small>
        </div>
    </div>

    <div class="rent-card">
        <div class="icon-box">$</div>
        <div class="rent-details">
            <h3>Total Revenue</h3>
            <h2>$<?= number_format($totalRevenue ?? 0, 2) ?></h2>
            <small>Current Balance available</small>
        </div>
    </div>

    <div class="rent-card">
        <div class="icon-box" style="background: #333;">#</div>
        <div class="rent-details">
            <h3>Lifetime Rent Paid</h3>
            <h2>$<?= number_format($totalRentPaid, 2) ?></h2>
            <small>Since <?= date('M Y', strtotime($contractRow['contract_start'] ?? 'now')) ?></small>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="history-panel">
        <h3 style="margin-bottom: 20px;">Payment History</h3>
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Date Paid</th>
                    <th>Period Covered</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="history-body">
                <?php if (count($paymentHistory) > 0): ?>
                    <?php foreach ($paymentHistory as $pay):
                        $payAmount = $pay['amount'] ?? $pay['paid_amount'] ?? 0;
                        $monthsCovered = isset($pay['month']) ? (int) $pay['month'] : (($baseMonthlyRent > 0 && $payAmount) ? max(1, (int) round($payAmount / $baseMonthlyRent)) : 1);
                        ?>
                        <tr class="product-row">
                            <td><?= date('M d, Y', strtotime($pay['paid_date'])) ?></td>
                            <td><?= $monthsCovered ?> Month<?= $monthsCovered > 1 ? 's' : '' ?></td>
                            <td><?= date('M d, Y', strtotime($pay['due_date'])) ?></td>
                            <td style="font-weight:bold;">$<?= number_format($payAmount, 2) ?></td>
                            <td><span class="badge ok">Paid</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No payment history found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pay-panel">
        <div>
            <h2>Make a Payment</h2>
            <p style="opacity: 0.7; margin-bottom: 30px;">Extend your lease securely.</p>

            <div class="input-group">
                <label>Duration (Months)</label>
                <div class="qty-selector">
                    <button class="qty-btn" onclick="updateMonths(-1)">-</button>
                    <input type="number" id="pay-months" class="qty-input" value="1" readonly>
                    <button class="qty-btn" onclick="updateMonths(1)">+</button>
                </div>
            </div>

            <div class="input-group">
                <label>Base Rent</label>
                <div style="font-size: 1.2rem;">$<?= number_format($baseMonthlyRent, 2) ?> <small>/mo</small></div>
            </div>
        </div>

        <div class="total-display">
            <div class="total-row">
                <span>Total Payment</span>
                <span class="big-price" id="total-display">$<?= number_format($baseMonthlyRent, 2) ?></span>
            </div>
            <form id="pay-form" action="utils/init_rent_payment.php" method="get" style="margin:0;">
                <input type="hidden" name="months" id="pay-months-hidden" value="1">
                <button type="submit" class="btn-pay">Confirm & Pay</button>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var currentMonths = 1;
        var baseRent = <?= json_encode($baseMonthlyRent) ?>;

        function updateMonths(change) {
            currentMonths += change;
            if (currentMonths < 1) currentMonths = 1;
            if (currentMonths > 12) currentMonths = 12;

            document.getElementById('pay-months').value = currentMonths;
            document.getElementById('pay-months-hidden').value = currentMonths;
            var total = currentMonths * baseRent;
            document.getElementById('total-display').innerText = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
        }

        window.updateMonths = updateMonths;
    })();
</script>

</body>
</html>