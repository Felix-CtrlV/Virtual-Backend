<?php
// Include the navigation/config file
include("partials/nav.php");

// 1. Logic for Payment History
// Fetch all payment history for this supplier
$historyStmt = $conn->prepare("SELECT * FROM rent_payments WHERE supplier_id = ? ORDER BY paid_date DESC");
$historyStmt->bind_param("i", $supplierid);
$historyStmt->execute();
$paymentHistory = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$historyStmt->close();

// 2. Logic for Financial Stats
// Calculate Total Rent Paid Lifetime
$totalRentPaidStmt = $conn->prepare("SELECT SUM(paid_amount) as total_paid FROM rent_payments WHERE supplier_id = ?");
$totalRentPaidStmt->bind_param("i", $supplierid);
$totalRentPaidStmt->execute();
$rentRow = $totalRentPaidStmt->get_result()->fetch_assoc();
$totalRentPaid = $rentRow['total_paid'] ?? 0;
$totalRentPaidStmt->close();

// Determine Monthly Rent Amount (Assuming based on latest payment or default)
// If no history, default to 1000. If history exists, take the last paid_amount / calculated months, 
// OR just assume a fixed rate. For this demo, let's assume a fixed base rent of $1000 or derive from last payment.
$baseMonthlyRent = 1000.00;
if (!empty($paymentHistory)) {
    // simplistic logic: take the most recent payment amount. 
    // In a real app, you'd store 'monthly_rent_price' in the 'suppliers' table.
    $lastPayment = $paymentHistory[0]['paid_amount'];
    // If they paid multiple months, this might be high, but let's set a fallback
    $baseMonthlyRent = 1000.00;
}

// 3. Logic for Rent Timer (Data from nav.php)
// $diff is calculated in nav.php (days remaining). 
// $contractRow['contract_end'] is the due date.

$statusColor = ($diff >= 0) ? 'var(--primary)' : '#cb4444'; // Green/Primary if ok, Red if overdue
$statusText = ($diff >= 0) ? $diff . ' Days Left' : abs($diff) . ' Days OVERDUE';
$progressPercent = ($diff >= 0) ? $percent : 100; // Full circle if overdue

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Payment - <?= $row['company_name'] ?></title>
    <link rel="stylesheet" href="css/supplierCss.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        /* Page Specific Overrides */
        .rent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 45px 30px 45px;
        }

        .rent-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 0 45px 30px 45px;
        }

        .rent-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: var(--radius-md);
            border: 1px solid rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s;
        }

        .rent-card:hover {
            transform: translateY(-5px);
        }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--primary);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            color: white;
        }

        .rent-details h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .rent-details h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
        }

        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin: 0 45px 45px 45px;
        }

        .history-panel {
            background: white;
            border-radius: var(--radius-xl);
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            min-height: 400px;
        }

        .pay-panel {
            background: var(--dark-card);
            color: white;
            border-radius: var(--radius-xl);
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .pay-panel::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.2;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .qty-selector {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: transparent;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .qty-input {
            width: 60px;
            height: 40px;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            text-align: center;
            font-weight: bold;
        }

        .total-display {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed rgba(255, 255, 255, 0.2);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 10px;
        }

        .big-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .btn-pay {
            background: var(--primary);
            color: #333;
            width: 100%;
            padding: 18px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 20px;
        }

        .btn-pay:hover {
            transform: scale(1.02);
            box-shadow: 0 0 20px var(--primary);
        }

        /* Receipt Hidden Template */
        #receipt-template {
            position: fixed;
            top: -9999px;
            left: -9999px;
            width: 320px;
            background: #fff;
            padding: 20px;
            font-family: 'Courier New', Courier, monospace;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            color: black;
            border: 1px solid #ddd;
        }

        .rec-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .rec-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .rec-total {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 14px;
        }

        .rec-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="rent-header">
        <div>
            <h1>Rent & Billing</h1>
            <p style="color:#666;">Manage your shop lease and payment history</p>
        </div>
        <div class="glass-panel" style="padding: 10px 20px;">
            <small>Current Status</small>
            <div style="font-weight:bold; color: <?= $statusColor ?>; display: flex; align-items: center; gap: 8px;">
                <div style="width: 10px; height: 10px; background: <?= $statusColor ?>; border-radius: 50%;"></div>
                <?= $diff >= 0 ? 'Active' : 'Overdue' ?>
            </div>
        </div>
    </div>

    <div class="rent-stats-grid">
        <div class="rent-card">
            <div class="circle-progress" style="background: conic-gradient(<?= $statusColor ?> <?= $progressPercent ?>%, #ddd 0); width: 80px; height: 80px;">
                <div class="inner-circle" style="width: 65px; height: 65px; font-size: 0.9rem;">
                    <?= $diff ?>d
                </div>
            </div>
            <div class="rent-details">
                <h3 id="due-label">Next Due Date</h3>
                <h2 id="due-date-display"><?= date('M d, Y', strtotime($contractRow['contract_end'])) ?></h2>
                <small style="color: <?= $statusColor ?>; font-weight:bold;"><?= $statusText ?></small>
            </div>
        </div>

        <div class="rent-card">
            <div class="icon-box">$</div>
            <div class="rent-details">
                <h3>Total Revenue</h3>
                <h2>$<?= number_format($totalRevenue, 2) ?></h2>
                <small>Current Balance available</small>
            </div>
        </div>

        <div class="rent-card">
            <div class="icon-box" style="background: #333;">#</div>
            <div class="rent-details">
                <h3>Lifetime Rent Paid</h3>
                <h2>$<?= number_format($totalRentPaid, 2) ?></h2>
                <small>Since <?= date('M Y', strtotime($contractRow['contract_start'])) ?></small>
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
                            // Calculate approximate months covered based on amount
                            $monthsCovered = round($pay['paid_amount'] / $baseMonthlyRent);
                            $monthsCovered = max(1, $monthsCovered); // Minimum 1 month
                        ?>
                            <tr class="product-row">
                                <td><?= date('M d, Y', strtotime($pay['paid_date'])) ?></td>
                                <td><?= $monthsCovered ?> Month<?= $monthsCovered > 1 ? 's' : '' ?></td>
                                <td><?= date('M d, Y', strtotime($pay['due_date'])) ?></td>
                                <td style="font-weight:bold;">$<?= number_format($pay['paid_amount'], 2) ?></td>
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
                <button class="btn-pay" onclick="processPayment()">Confirm & Pay</button>
            </div>
        </div>
    </div>

    <div id="receipt-template">
        <div class="rec-header">
            <h2 style="margin:0;"><?= strtoupper($row['company_name']) ?></h2>
            <div>Rent Payment Receipt</div>
            <div>Supplier ID: <?= $supplierid ?></div>
        </div>
        <div class="rec-row">
            <span>Date:</span>
            <span id="rec-date">2025-01-01</span>
        </div>
        <div class="rec-row">
            <span>Receipt #:</span>
            <span id="rec-id">RC-9999</span>
        </div>
        <div style="border-bottom: 1px dashed #000; margin: 10px 0;"></div>
        <div class="rec-row">
            <span>Item</span>
            <span>Amt</span>
        </div>
        <div class="rec-row">
            <span id="rec-desc">Rent (1 Month)</span>
            <span id="rec-amt">$1000.00</span>
        </div>
        <div class="rec-total rec-row">
            <span>TOTAL PAID</span>
            <span id="rec-total">$1000.00</span>
        </div>
        <div class="rec-footer">
            <p>Thank you for your payment.</p>
            <p>Auth: AUTH-<?= rand(10000, 99999) ?></p>
        </div>
    </div>

    <script>
        let currentMonths = 1;
        const baseRent = <?= $baseMonthlyRent ?>;

        // Timer Logic Variables (for frontend update)
        let currentDueDate = new Date("<?= $contractRow['contract_end'] ?>");

        function updateMonths(change) {
            currentMonths += change;
            if (currentMonths < 1) currentMonths = 1;
            if (currentMonths > 12) currentMonths = 12; // Cap at 1 year for safety

            document.getElementById('pay-months').value = currentMonths;
            const total = currentMonths * baseRent;
            document.getElementById('total-display').innerText = '$' + total.toLocaleString('en-US', {
                minimumFractionDigits: 2
            });
        }

        function processPayment() {
            if (!confirm("Confirm payment of $" + (currentMonths * baseRent) + " for " + currentMonths + " months?")) return;

            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
            const receiptId = 'PAY-' + Math.floor(Math.random() * 1000000);
            const totalAmount = currentMonths * baseRent;

            // 1. Update Receipt Data
            document.getElementById('rec-date').innerText = now.toISOString().split('T')[0];
            document.getElementById('rec-id').innerText = receiptId;
            document.getElementById('rec-desc').innerText = `Rent Extension (${currentMonths} Mo)`;
            const formattedTotal = '$' + totalAmount.toLocaleString('en-US', {
                minimumFractionDigits: 2
            });
            document.getElementById('rec-amt').innerText = formattedTotal;
            document.getElementById('rec-total').innerText = formattedTotal;

            // 2. Generate Receipt Image
            const receiptNode = document.getElementById('receipt-template');

            html2canvas(receiptNode).then(canvas => {
                // Trigger Download
                const link = document.createElement('a');
                link.download = `${receiptId}_Receipt.png`;
                link.href = canvas.toDataURL();
                link.click();

                // 3. Update UI (Frontend simulation of "Resetting Timer")
                alert("Payment Successful! Receipt downloaded.");

                // Add new row to table
                const tableBody = document.getElementById('history-body');
                const newRow = document.createElement('tr');
                newRow.className = 'product-row';

                // Calculate new due date
                currentDueDate.setMonth(currentDueDate.getMonth() + currentMonths);
                const newDueDateStr = currentDueDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });

                newRow.innerHTML = `
                    <td>${dateStr}</td>
                    <td>${currentMonths} Month${currentMonths > 1 ? 's' : ''}</td>
                    <td>${newDueDateStr}</td>
                    <td style="font-weight:bold;">${formattedTotal}</td>
                    <td><span class="badge ok">Paid</span></td>
                `;

                // Insert at top
                if (tableBody.querySelector('td[colspan="5"]')) {
                    tableBody.innerHTML = ''; // Remove "No history" text
                }
                tableBody.insertBefore(newRow, tableBody.firstChild);

                // Reset Timer UI
                document.getElementById('due-date-display').innerText = newDueDateStr;
                // Calculate new diff roughly
                const timeDiff = currentDueDate - now;
                const daysLeft = Math.ceil(timeDiff / (1000 * 3600 * 24));

                // Update Status text
                const statusLabel = document.querySelector('.rent-details small');
                statusLabel.innerText = daysLeft + " Days Left";
                statusLabel.style.color = "var(--primary)";

                document.getElementById('due-label').innerText = "New Due Date";

                // Update Circle (Reset to full visual for simplicity or calc percentage)
                document.querySelector('.inner-circle').innerText = daysLeft + "d";
                document.querySelector('.circle-progress').style.background = `conic-gradient(var(--primary) 100%, #ddd 0)`;
            });
        }
    </script>

</body>

</html>