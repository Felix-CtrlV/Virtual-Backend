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

$rentquery = " SELECT
COUNT(DISTINCT s.supplier_id) AS total_shops, COUNT(DISTINCT CASE WHEN rp.paid_date <= LAST_DAY(CURRENT_DATE) AND rp.due_date  >= CURRENT_DATE THEN s.supplier_id END) AS paid_shops,
ROUND( COUNT(DISTINCT CASE WHEN rp.paid_date <= LAST_DAY(CURRENT_DATE) AND rp.due_date  >= CURRENT_DATE THEN s.supplier_id END) * 100.0 / NULLIF(COUNT(DISTINCT s.supplier_id), 0),2) AS payment_percentage,
COUNT(DISTINCT CASE WHEN rp.due_date < CURRENT_DATE THEN s.supplier_id END) AS overdue_shops,COALESCE(SUM(CASE WHEN rp.paid_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') AND rp.paid_date <= CURRENT_DATE
THEN rp.paid_amount END), 0) AS total_collected_amount FROM suppliers s LEFT JOIN rent_payments rp ON rp.supplier_id = s.supplier_id WHERE s.status = 'active';";
$rentresult = mysqli_query($conn, $rentquery);
$rentrow = mysqli_fetch_assoc($rentresult);
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
                    <div class="card-title">Active Suppliers</div>
                    <div class="card-value"><?= $activesupplierscount ?></div>
                </div>
                <span class="card-chip">Onboarded</span>
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
            <div class="card-trend trend-down"><?= $rentrow['overdue_shops'] ?> overdue shop(s)</div>
        </div>
    </div>
</section>

<script src="script.js"></script>
</body>

</html>