<?php
$pageTitle = 'Dashboard';
$pageSubtitle = 'High-level overview of your mall performance.';
include("partials/nav.php");
?>

<?php
$totalcustomer = "SELECT COUNT(*) AS total FROM (select customer_id as id FROM customers) AS all_users;";
$result = mysqli_query($conn, $totalcustomer);
$row = mysqli_fetch_assoc($result);
$totalcustomer = $row["total"];

$rowthisweek = "select count(*) as this_week from customers where created_at >= Curdate() - INTERVAL 7 DAY;";
$resultthisweek = mysqli_query($conn, $rowthisweek);
$thisweek = mysqli_fetch_assoc($resultthisweek);

$rowlastweek = "select count(*) as last_week from customers where created_at < Curdate() - INTERVAL 7 DAY AND created_at >= Curdate() - INTERVAL 14 DAY;";
$resultlastweek = mysqli_query($conn, $rowlastweek);
$lastweek = mysqli_fetch_assoc($resultlastweek);

$thisweekcount = $thisweek['this_week'];
$lastweekcount = $lastweek['last_week'];

$thisweekcount = (int) $thisweek['this_week'];
$lastweekcount = (int) $lastweek['last_week'];

$diff = 0;
$percent = 0;

if ($thisweekcount > 0 && $lastweekcount > 0) {
    $diff = $thisweekcount - $lastweekcount;
    $percent = round(($diff / $lastweekcount) * 100, 1);
} elseif ($thisweekcount > 0 && $lastweekcount == 0) {
    $diff = $thisweekcount;
    $percent = 100;
}

// ...................................................................................................................................................

$activesuppliers = "SELECT COUNT(*) AS total FROM (select supplier_id as id FROM suppliers WHERE status = 'active') AS active_suppliers;";
$activesuppliersresult = mysqli_query($conn, $activesuppliers);
$activesuppliersrow = mysqli_fetch_assoc($activesuppliersresult);
$activesupplierscount = $activesuppliersrow["total"];

$thismonth = "select count(*) as this_month from suppliers where status = 'active' and created_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01');";
$thismonthresult = mysqli_query($conn, $thismonth);
$thismonthrow = mysqli_fetch_assoc($thismonthresult);

$lastmonth = "select count(*) as last_month from suppliers where status = 'active' and created_at < DATE_FORMAT(CURDATE() ,'%Y-%m-01') AND created_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH ,'%Y-%m-01');";
$lastmonthresult = mysqli_query($conn, $lastmonth);
$lastmonthrow = mysqli_fetch_assoc($lastmonthresult);

$thismonthcount = $thismonthrow["this_month"];
$lastmonthcount = $lastmonthrow["last_month"];

$supplierdiff = max(0, $thismonthcount - $lastmonthcount);

// ...................................................................................................................................................

$ratingquery = "SELECT Round(AVG(rating),1) AS average_rating FROM reviews;";
$ratingresult = mysqli_query($conn, $ratingquery);
$ratingrow = mysqli_fetch_assoc($ratingresult);
$average_rating = $ratingrow["average_rating"];

$ratingthismonth = "SELECT COUNT(*) AS this_month FROM reviews WHERE created_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01');";
$ratingthismonthresult = mysqli_query($conn, $ratingthismonth);
$ratingthismonthrow = mysqli_fetch_assoc($ratingthismonthresult);

$ratinglastmonth = "SELECT COUNT(*) AS last_month FROM reviews WHERE created_at < DATE_FORMAT(CURDATE() ,'%Y-%m-01') AND created_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH ,'%Y-%m-01');";
$ratinglastmonthresult = mysqli_query($conn, $ratinglastmonth);
$ratinglastmonthrow = mysqli_fetch_assoc($ratinglastmonthresult);

$ratingthismonthcount = $ratingthismonthrow["this_month"];
$ratinglastmonthcount = $ratinglastmonthrow["last_month"];

$ratingdiff = $ratingthismonthcount - $ratinglastmonthcount;

if ($ratinglastmonthcount > 0) {
    $ratingpercent = max(
        0,
        round(($ratingdiff / $ratinglastmonthcount) * 100, 1)
    );
} else {
    $ratingpercent = 100;
}


// ...................................................................................................................................................

$rentquery = "SELECT
    COUNT(DISTINCT c.company_id) AS total_shops,

    COUNT(DISTINCT CASE
        WHEN rp_cov.coverage_end >= CURRENT_DATE
        THEN c.company_id
    END) AS paid_shops,

    ROUND(
        COUNT(DISTINCT CASE
            WHEN rp_cov.coverage_end >= CURRENT_DATE
            THEN c.company_id
        END) * 100.0
        / NULLIF(COUNT(DISTINCT c.company_id), 0),
        2
    ) AS payment_percentage,

    COUNT(DISTINCT CASE
        WHEN rp_cov.coverage_end < CURRENT_DATE
        THEN c.company_id
    END) AS overdue_shops,

    COALESCE(SUM(CASE
        WHEN rp_cov.last_paid_month = DATE_FORMAT(CURRENT_DATE, '%Y-%m')
        THEN rp_cov.total_amount
    END), 0) AS total_collected_amount

FROM suppliers s
INNER JOIN companies c
    ON c.supplier_id = s.supplier_id
   AND c.status = 'active'

LEFT JOIN (
    SELECT
        company_id,
        MIN(paid_date) AS coverage_start,
        MAX(due_date) AS coverage_end,
        SUM(amount) AS total_amount,
        DATE_FORMAT(MAX(paid_date), '%Y-%m') AS last_paid_month
    FROM rent_payments
    GROUP BY company_id
) rp_cov
    ON rp_cov.company_id = c.company_id

WHERE s.status = 'active';

";
$rentresult = mysqli_query($conn, $rentquery);
$rentrow = mysqli_fetch_assoc($rentresult);

// --- STORE OVERVIEW: Total orders ---
$orders_total_sql = "SELECT COUNT(*) AS total FROM orders";
$orders_total_res = mysqli_query($conn, $orders_total_sql);
$orders_total_row = mysqli_fetch_assoc($orders_total_res);
$total_orders = (int) ($orders_total_row['total'] ?? 0);

$orders_this_month_sql = "SELECT COUNT(*) AS total FROM orders WHERE order_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01')";
$orders_month_res = mysqli_query($conn, $orders_this_month_sql);
$orders_month_row = mysqli_fetch_assoc($orders_month_res);
$orders_this_month = (int) ($orders_month_row['total'] ?? 0);

// --- PIE: Store overview (Customers, Active companies, Pending companies) ---
$total_companies_sql = "SELECT COUNT(*) AS total FROM companies";
$total_companies_res = mysqli_query($conn, $total_companies_sql);
$total_companies_row = mysqli_fetch_assoc($total_companies_res);
$total_companies = (int) ($total_companies_row['total'] ?? 0);

$pending_companies_sql = "SELECT COUNT(*) AS total FROM companies WHERE status = 'pending'";
$pending_companies_res = mysqli_query($conn, $pending_companies_sql);
$pending_companies_row = mysqli_fetch_assoc($pending_companies_res);
$pending_companies_count = (int) ($pending_companies_row['total'] ?? 0);
$active_companies_count = $total_companies - $pending_companies_count;

// --- LINE CHART: Last 12 months — new customers & new companies ---
$months_labels = [];
$customers_per_month = [];
$companies_per_month = [];
for ($i = 11; $i >= 0; $i--) {
    $d = new DateTime("first day of -$i months");
    $months_labels[] = $d->format('M Y');
    $month_start = $d->format('Y-m-01');
    $month_end = $d->format('Y-m-t');
    $cust_sql = "SELECT COUNT(*) AS c FROM customers WHERE created_at >= '$month_start' AND created_at <= '$month_end 23:59:59'";
    $cust_res = mysqli_query($conn, $cust_sql);
    $cust_row = mysqli_fetch_assoc($cust_res);
    $customers_per_month[] = (int) ($cust_row['c'] ?? 0);
    $comp_sql = "SELECT COUNT(*) AS c FROM companies WHERE created_at >= '$month_start' AND created_at <= '$month_end 23:59:59'";
    $comp_res = mysqli_query($conn, $comp_sql);
    $comp_row = mysqli_fetch_assoc($comp_res);
    $companies_per_month[] = (int) ($comp_row['c'] ?? 0);
}

// --- YEAR / MONTH FILTER (for revenue chart & best companies) ---
$filter_year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$filter_month = isset($_GET['month']) ? (int) $_GET['month'] : 0; // 0 = all months

// Available years (from orders; always include current year)
$years_sql = "SELECT DISTINCT YEAR(order_date) AS yr FROM orders ORDER BY yr DESC LIMIT 8";
$years_res = mysqli_query($conn, $years_sql);
$available_years = [];
while ($yr = mysqli_fetch_assoc($years_res)) {
    $available_years[] = (int) $yr['yr'];
}
$current_yr = (int) date('Y');
if (empty($available_years) || !in_array($current_yr, $available_years)) {
    $available_years = array_values(array_unique(array_merge([$current_yr], $available_years)));
    rsort($available_years);
}

// --- REVENUE LINE CHART: Mall-wide revenue by month for selected year ---
$month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$revenue_labels = $month_names;
$revenue_by_month = array_fill(0, 12, 0);
$rev_sql = "SELECT MONTH(order_date) AS m, SUM(price) AS revenue FROM orders WHERE YEAR(order_date) = $filter_year GROUP BY m ORDER BY m";
$rev_res = mysqli_query($conn, $rev_sql);
if ($rev_res) {
    while ($r = mysqli_fetch_assoc($rev_res)) {
        $revenue_by_month[(int) $r['m'] - 1] = (float) ($r['revenue'] ?? 0);
    }
}

// --- BEST / WORST COMPANIES: Rank by revenue for selected year (and optional month) ---
$best_companies_sql = "
SELECT c.company_id, c.company_name, SUM(o.price) AS revenue
FROM orders o
JOIN companies c ON o.company_id = c.company_id
WHERE YEAR(o.order_date) = $filter_year
GROUP BY c.company_id, c.company_name
ORDER BY revenue DESC
LIMIT 15";

$best_companies_res = mysqli_query($conn, $best_companies_sql);
$best_companies = [];
$rank = 1;
if ($best_companies_res) {
    while ($row = mysqli_fetch_assoc($best_companies_res)) {
        $row['rank'] = $rank++;
        $best_companies[] = $row;
    }
}

// --- COMPANY REVENUE TREND (multi-line): top 8 companies by revenue, monthly for selected year ---
$company_trend_datasets = [];
$company_trend_colors = [
    'rgba(59, 130, 246, 1)',
    'rgba(34, 197, 94, 1)',
    'rgba(251, 191, 36, 1)',
    'rgba(239, 68, 68, 1)',
    'rgba(139, 92, 246, 1)',
    'rgba(236, 72, 153, 1)',
    'rgba(20, 184, 166, 1)',
    'rgba(249, 115, 22, 1)'
];
$top_company_ids = array_slice(array_map(function ($c) {
    return (int) $c['company_id']; }, $best_companies), 0, 8);
if (!empty($top_company_ids)) {
    $ids_list = implode(',', $top_company_ids);
    $trend_sql = "SELECT o.company_id, c.company_name, MONTH(o.order_date) AS m, SUM(o.price) AS revenue
        FROM orders o
        JOIN companies c ON o.company_id = c.company_id
        WHERE YEAR(o.order_date) = $filter_year AND o.company_id IN ($ids_list)
        GROUP BY o.company_id, c.company_name, m ORDER BY o.company_id, m";
    $trend_res = mysqli_query($conn, $trend_sql);
    $by_company = [];
    $company_names = [];
    if ($trend_res) {
        while ($t = mysqli_fetch_assoc($trend_res)) {
            $cid = (int) $t['company_id'];
            if (!isset($by_company[$cid])) {
                $by_company[$cid] = array_fill(1, 12, 0);
                $company_names[$cid] = $t['company_name'];
            }
            $by_company[$cid][(int) $t['m']] = (float) ($t['revenue'] ?? 0);
        }
    }
    $idx = 0;
    foreach ($top_company_ids as $cid) {
        $name = $company_names[$cid] ?? 'Company ' . $cid;
        $monthly = isset($by_company[$cid]) ? $by_company[$cid] : array_fill(1, 12, 0);
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[] = $monthly[$m] ?? 0;
        }
        $company_trend_datasets[] = [
            'label' => $name,
            'data' => $data,
            'borderColor' => $company_trend_colors[$idx % count($company_trend_colors)],
            'backgroundColor' => str_replace('1)', '0.08)', $company_trend_colors[$idx % count($company_trend_colors)]),
            'fill' => true,
            'tension' => 0.3
        ];
        $idx++;
    }
}
?>

<section class="section active">
    <div class="grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Customers</div>
                    <div class="card-value"><?= $totalcustomer ?></div>
                </div>
                <span class="card-chip"><?= $diff ?>+ new</span>
            </div>
            <div class="card-trend trend-up">+<?= $percent ?>% vs last week</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Suppliers</div>
                    <div class="card-value"><?= $activesupplierscount ?></div>
                </div>
                <span class="card-chip">Active</span>
            </div>
            <div class="card-trend trend-up">+<?= $supplierdiff ?> this month</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Average Rating</div>
                    <div class="card-value"><?= $average_rating ?><span style="color: #eab308;">★</span></div>
                </div>
                <span class="card-chip">Mall-wide</span>
            </div>
            <div class="card-trend trend-up">+<?= $ratingpercent ?>% vs last quarter</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Monthly-Rent Collected</div>
                    <div class="card-value">$<?= $rentrow['total_collected_amount'] ?></div>
                </div>
                <span class="card-chip"><?= $rentrow['payment_percentage'] ?>% collected</span>
            </div>
            <div class="card-trend trend-down"><?= $rentrow['overdue_shops'] ?> overdue company(s)</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Orders</div>
                    <div class="card-value"><?= number_format($total_orders) ?></div>
                </div>
                <span class="card-chip"><?= $orders_this_month ?> this month</span>
            </div>
            <div class="card-trend trend-up">Mall-wide</div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Companies</div>
                    <div class="card-value"><?= number_format($total_companies) ?></div>
                </div>
                <span class="card-chip"><?= $active_companies_count ?> active</span>
            </div>
            <div class="card-trend trend-up"><?= $pending_companies_count ?> pending</div>
        </div>
    </div>

    <!-- Mall revenue line chart -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <div>
                <div class="card-title">Mall revenue (<?= $filter_year ?>)</div>
                <div class="card-value" style="font-size: 1rem;">Monthly sales</div>
            </div>
            <span class="card-chip">Whole mall</span>
        </div>
        <div style="position: relative; height: 280px; padding: 12px;">
            <canvas id="dashboardRevenueChart" style="width:100%; height:100%;"></canvas>
        </div>
    </div>


    <!-- Store overview: line chart + pie chart -->
    <style>
        .dashboard-charts-section {
            margin-top: 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 900px) {
            .dashboard-charts-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div class="dashboard-charts-section">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Monthly signups (last 12 months)</div>
                </div>
                <span class="card-chip">Customers & companies</span>
            </div>
            <div style="position: relative; height: 280px; padding: 12px;">
                <canvas id="dashboardLineChart" style="width:100%; height:100%;"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Store overview</div>
                </div>
                <span class="card-chip">By type</span>
            </div>
            <div style="position: relative; height: 280px; padding: 12px;">
                <canvas id="dashboardPieChart" style="width:100%; height:100%;"></canvas>
            </div>
        </div>
    </div>

    <div class="dashboard-filter-bar"
        style="margin-top: 24px; margin-bottom: 16px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
        <form method="get" action="dashboard.php"
            style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <label style="color: var(--muted); font-size: 0.9rem;">Year</label>
            <select name="year" onchange="this.form.submit()"
                style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text); font-size: 0.9rem;">
                <?php foreach ($available_years as $yr): ?>
                    <option value="<?= $yr ?>" <?= $filter_year === $yr ? 'selected' : '' ?>><?= $yr ?></option>
                <?php endforeach; ?>
                <?php if (!in_array($filter_year, $available_years)): ?>
                    <option value="<?= $filter_year ?>" selected><?= $filter_year ?></option>
                <?php endif; ?>
            </select>
            <label style="color: var(--muted); font-size: 0.9rem;">Month</label>
            <select name="month" onchange="this.form.submit()"
                style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-light); color: var(--text); font-size: 0.9rem;">
                <option value="0" <?= $filter_month === 0 ? 'selected' : '' ?>>All months</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $filter_month === $m ? 'selected' : '' ?>><?= $month_names[$m - 1] ?>
                    </option>
                <?php endfor; ?>
            </select>
        </form>
        <span style="color: var(--muted); font-size: 0.85rem;"></span>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <div>
                <div class="card-title">Company revenue trend (<?= $filter_year ?>)</div>
                <div class="card-value" style="font-size: 0.9rem; font-weight: 400; color: var(--muted);">Top companies
                    by revenue — monthly comparison</div>
            </div>
            <span class="card-chip">Competitive</span>
        </div>
        <div style="position: relative; height: 300px; padding: 12px;">
            <canvas id="dashboardCompanyTrendChart" style="width:100%; height:100%;"></canvas>
        </div>
    </div>

    <!-- Best / worst companies (trend by revenue for selected year/month) -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <div>
                <div class="card-title">Company performance
                    (<?= $filter_year ?><?= $filter_month ? ' — ' . $month_names[$filter_month - 1] : '' ?>)</div>
                <div class="card-value" style="font-size: 0.85rem; font-weight: 400; color: var(--muted);">Best to worst
                    by revenue — use Year/Month above to filter</div>
            </div>
            <span class="card-chip">Trend</span>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <th style="text-align: left; padding: 12px; color: var(--muted); font-weight: 600;">#</th>
                        <th style="text-align: left; padding: 12px; color: var(--muted); font-weight: 600;">Company</th>
                        <th style="text-align: right; padding: 12px; color: var(--muted); font-weight: 600;">Revenue
                        </th>
                        <th style="text-align: right; padding: 12px; color: var(--muted); font-weight: 600;">Orders</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($best_companies)): ?>
                        <tr>
                            <td colspan="4" style="padding: 24px; text-align: center; color: var(--muted);">No orders in
                                this period.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($best_companies as $bc): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px;"><?= $bc['rank'] ?></td>
                                <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($bc['company_name'] ?? '—') ?>
                                </td>
                                <td style="padding: 12px; text-align: right; color: var(--success);">
                                    $<?= number_format((float) ($bc['revenue'] ?? 0), 2) ?></td>
                                <td style="padding: 12px; text-align: right;"><?= (int) ($bc['order_count'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script src="script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') return;

        // Mall revenue line chart (selected year, 12 months)
        var revenueCtx = document.getElementById('dashboardRevenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($revenue_labels) ?>,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: <?= json_encode($revenue_by_month) ?>,
                        borderColor: 'rgba(139, 92, 246, 1)',
                        backgroundColor: 'rgba(139, 92, 246, 0.15)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (v) { return '$' + v; }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Company revenue trend (multi-line, competitive)
        var companyTrendCtx = document.getElementById('dashboardCompanyTrendChart');
        if (companyTrendCtx) {
            var companyTrendDatasets = <?= json_encode($company_trend_datasets ?? []) ?>;
            new Chart(companyTrendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($month_names) ?>,
                    datasets: companyTrendDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (v) { return '$' + v; }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        var lineCtx = document.getElementById('dashboardLineChart');
        if (lineCtx) {
            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($months_labels) ?>,
                    datasets: [
                        {
                            label: 'Customers',
                            data: <?= json_encode($customers_per_month) ?>,
                            borderColor: 'rgba(59, 130, 246, 1)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Companies',
                            data: <?= json_encode($companies_per_month) ?>,
                            borderColor: 'rgba(34, 197, 94, 1)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Pie chart: store overview (Customers, Active companies, Pending companies)
        var pieCtx = document.getElementById('dashboardPieChart');
        if (pieCtx) {
            var totalCust = <?= (int) $totalcustomer ?>;
            var activeCo = <?= (int) $active_companies_count ?>;
            var pendingCo = <?= (int) $pending_companies_count ?>;
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Customers', 'Active companies', 'Pending companies'],
                    datasets: [{
                        data: [totalCust, activeCo, pendingCo],
                        backgroundColor: ['rgba(59, 130, 246, 0.85)', 'rgba(34, 197, 94, 0.85)', 'rgba(251, 191, 36, 0.85)'],
                        borderColor: ['rgba(59, 130, 246, 1)', 'rgba(34, 197, 94, 1)', 'rgba(251, 191, 36, 1)'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '55%',
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var total = (totalCust + activeCo + pendingCo) || 1;
                                    var value = ctx.parsed || 0;
                                    var pct = Math.round((value / total) * 100);
                                    return ctx.label + ': ' + value + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
</body>

</html>