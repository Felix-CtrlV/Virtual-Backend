<?php
$pageTitle = 'Renting Payment Management';
$pageSubtitle = 'Overview of tenant rental payments and balances.';
include("partials/nav.php");
?>

<?php
$activesuppliersquery = "SELECT COUNT(*) AS total_company FROM companies where status = 'active';";
$suppliersresult = mysqli_query($conn, $activesuppliersquery);
$suppliersrow = mysqli_fetch_assoc($suppliersresult);

$totalsuppliersquery = "SELECT COUNT(*) AS total_company FROM companies;";
$totalsuppliersresult = mysqli_query($conn, $totalsuppliersquery);
$totalsuppliersrow = mysqli_fetch_assoc($totalsuppliersresult);

$activesupplierspercent = $suppliersrow['total_company'] / max($totalsuppliersrow['total_company'], 1) * 100;
// ...............................................................................................................................

$rentquery = " SELECT
    COUNT(DISTINCT c.company_id) AS total_shops,
    
    COUNT(DISTINCT CASE 
        WHEN rp.paid_date <= LAST_DAY(CURRENT_DATE) 
             AND rp.due_date >= CURRENT_DATE 
        THEN c.company_id 
    END) AS paid_shops,
    
    ROUND(
        COUNT(DISTINCT CASE 
            WHEN rp.paid_date <= LAST_DAY(CURRENT_DATE) 
                 AND rp.due_date >= CURRENT_DATE 
            THEN c.company_id 
        END) * 100.0 / NULLIF(COUNT(DISTINCT c.company_id), 0),
        2
    ) AS payment_percentage,
    
    COUNT(DISTINCT CASE 
        WHEN rp.due_date < CURRENT_DATE 
        THEN c.company_id 
    END) AS overdue_shops,
    
    COALESCE(SUM(CASE 
        WHEN rp.paid_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') 
             AND rp.paid_date <= CURRENT_DATE
        THEN rp.paid_amount 
    END), 0) AS total_collected_amount

FROM suppliers s
INNER JOIN companies c 
    ON c.supplier_id = s.supplier_id AND c.status = 'active'
LEFT JOIN rent_payments rp 
    ON rp.supplier_id = s.supplier_id
WHERE s.status = 'active';
";
$rentresult = mysqli_query($conn, $rentquery);
$rentrow = mysqli_fetch_assoc($rentresult);

$paidShops = (int) ($rentrow['paid_shops'] ?? 0);
$totalShops = (int) ($rentrow['total_shops'] ?? 0);
$unpaidShops = max($totalShops - $paidShops, 0);

$unpaidsuppliers = [];
$unpaidsql = "SELECT 
    s.supplier_id,
    c.*,
    s.name AS supplier_name,
    s.email,
    rp.paid_date AS last_paid_date,
    rp.due_date AS due_date
FROM suppliers s
INNER JOIN companies c
    ON c.supplier_id = s.supplier_id
LEFT JOIN (
    SELECT rp1.*
    FROM rent_payments rp1
    INNER JOIN (
        SELECT supplier_id, MAX(paid_date) AS latest_paid
        FROM rent_payments
        GROUP BY supplier_id
    ) rp2
    ON rp1.supplier_id = rp2.supplier_id 
       AND rp1.paid_date = rp2.latest_paid
) rp
    ON rp.supplier_id = s.supplier_id
WHERE s.status = 'active'
  AND c.status = 'active'
  AND (rp.paid_date IS NULL OR rp.due_date < CURRENT_DATE)
ORDER BY (rp.due_date IS NULL) ASC, rp.due_date ASC
LIMIT 8;";
$unpaidresult = mysqli_query($conn, $unpaidsql);
if ($unpaidresult) {
    while ($row = mysqli_fetch_assoc($unpaidresult)) {
        $unpaidsuppliers[] = $row;
    }
}

$recentpayments = [];
$recentpaymentssql = "SELECT 
    rp.supplier_id,
    c.*,
    rp.paid_amount,
    rp.paid_date,
    rp.due_date
FROM rent_payments rp
INNER JOIN suppliers s 
    ON s.supplier_id = rp.supplier_id
INNER JOIN companies c
    ON c.supplier_id = s.supplier_id AND c.status = 'active'
WHERE s.status = 'active'
ORDER BY rp.paid_date DESC
LIMIT 10;
";
$recentpaymentsresult = mysqli_query($conn, $recentpaymentssql);
if ($recentpaymentsresult) {
    while ($row = mysqli_fetch_assoc($recentpaymentsresult)) {
        $recentpayments[] = $row;
    }
}
?>

<section class="section active">
    <div class="grid-3">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Active Companies</div>
                    <div class="card-value"><?= $suppliersrow['total_company'] ?></div>
                </div>
                <span class="card-chip status-pill status-active">Active</span>
            </div>
            <div class="card-trend trend-up"><?= $activesupplierspercent ?>% of all companies are Active</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Paid</div>
                    <div class="card-value"><?= $rentrow['paid_shops'] ?></div>
                </div>
                <span class="card-chip">This Month</span>
            </div>
            <div class="card-trend trend-down"><?= $rentrow['overdue_shops'] ?> overdue company(s)</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Monthly-Rent Collected</div>
                    <div class="card-value">$<?= $rentrow['total_collected_amount'] ?></div>
                </div>
                <span class="card-chip"><?= $rentrow['payment_percentage'] ?>% collected</span>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <div>
                    <div class="card-title">Rent Payment Status</div>
                </div>
                <span class="card-chip">Paid vs Unpaid</span>
            </div>
            <div style="display:flex;align-items:center;gap:18px;">
                <div style="flex:0 0 180px;">
                    <canvas id="rentStatusChart" height="180"></canvas>
                </div>
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span
                            style="width:10px;height:10px;border-radius:3px;background:rgba(34, 197, 94, 0.9);display:inline-block;"></span>
                        <span style="font-size:12px;color:var(--muted); width: 70px;">Paid shops</span>
                        <span class="badge-soft"><?= $paidShops ?></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span
                            style="width:10px;height:10px;border-radius:3px;background:rgba(239, 68, 68, 0.9);display:inline-block;"></span>
                        <span style="font-size:12px;color:var(--muted);width:70px;">Unpaid shops</span>
                        <span class="badge-soft"><?= $unpaidShops ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <div>
                    <div class="card-title">Unpaid Suppliers/Shops</div>
                    <div class="card-value"><?= $unpaidShops ?></div>
                </div>
                <a class="btn btn-ghost" href="viewsuppliers.php?adminid=<?= urlencode($adminid) ?>"
                    style="padding:5px 12px;font-size:11px;">View Companies</a>
            </div>

            <div style="overflow:auto; height: 170px;">
                <table>
                    <thead>
                        <tr>
                            <th>Shop</th>
                            <th>Monthly Rent</th>
                            <th>Last Paid</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($unpaidsuppliers)): ?>
                            <?php foreach ($unpaidsuppliers as $s): ?>
                                <?php
                                $dueText = 'N/A';
                                $dueClass = 'status-inactive';
                                if (!empty($s['due_date'])) {
                                    $dueDate = new DateTime($s['due_date']);
                                    $today = new DateTime();
                                    $interval = $today->diff($dueDate);
                                    if ($today > $dueDate) {
                                        $dueText = 'Overdue';
                                        $dueClass = 'status-banned';
                                    } elseif ($interval->days === 0) {
                                        $dueText = 'Due today';
                                        $dueClass = 'status-pending';
                                    } elseif ($interval->days <= 7) {
                                        $dueText = $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' left';
                                        $dueClass = 'status-pending';
                                    } else {
                                        $dueText = $interval->days . ' days left';
                                        $dueClass = 'status-active';
                                    }
                                } elseif (empty($s['last_paid_date'])) {
                                    $dueText = 'No payment';
                                    $dueClass = 'status-inactive';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:500;">
                                            <?= htmlspecialchars($s['company_name'] ?? 'Unknown') ?>
                                        </div>
                                        <div style="font-size:11px;color:var(--muted);">
                                            <?= htmlspecialchars($s['name'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td>$<?= number_format((float) ($s['renting_price'] ?? 0), 2) ?></td>
                                    <td>
                                        <?= !empty($s['last_paid_date']) ? date('M d, Y', strtotime($s['last_paid_date'])) : 'N/A' ?>
                                    </td>
                                    <td>
                                        <?= !empty($s['due_date']) ? date('M d, Y', strtotime($s['due_date'])) : 'N/A' ?>
                                    </td>
                                    <td>
                                        <span
                                            class="status-pill card-chip <?= $dueClass ?>"><?= htmlspecialchars($dueText) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center;padding:24px;color:var(--muted);">
                                    No unpaid suppliers found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="grid-column: span 4;">
            <div class="card-header">
                <div>
                    <div class="card-title">Recent Rent Payments</div>
                    <div class="card-trend" style="color:var(--muted);">Latest recorded transactions</div>
                </div>
                <span class="card-chip">Last 10</span>
            </div>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Shop</th>
                            <th>Paid Amount</th>
                            <th>Paid Date</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentpayments)): ?>
                            <?php foreach ($recentpayments as $p): ?>
                                <tr>
                                    <td style="font-weight:500;">
                                        <?= htmlspecialchars($p['company_name'] ?? 'Unknown') ?>
                                    </td>
                                    <td>$<?= number_format((float) ($p['paid_amount'] ?? 0), 2) ?></td>
                                    <td><?= !empty($p['paid_date']) ? date('M d, Y', strtotime($p['paid_date'])) : 'N/A' ?></td>
                                    <td><?= !empty($p['due_date']) ? date('M d, Y', strtotime($p['due_date'])) : 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;padding:24px;color:var(--muted);">
                                    No payment history found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script src="script.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('rentStatusChart');
        if (!el || typeof Chart === 'undefined') return;

        const paid = <?= (int) $paidShops ?>;
        const unpaid = <?= (int) $unpaidShops ?>;

        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Unpaid'],
                datasets: [{
                    data: [paid, unpaid],
                    backgroundColor: ['rgba(34, 197, 94, 0.85)', 'rgba(239, 68, 68, 0.85)'],
                    borderColor: ['rgba(34, 197, 94, 1)', 'rgba(239, 68, 68, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const total = (paid + unpaid) || 1;
                                const value = ctx.parsed || 0;
                                const pct = Math.round((value / total) * 100);
                                return `${ctx.label}: ${value} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>

</body>

</html>