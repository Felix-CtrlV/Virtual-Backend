<?php
/**
 * Init Rent Payment: stores pending rent in session and redirects to Crediverse.
 * After payment, Crediverse returns to rentpayment.php?payment_status=success.
 */
session_start();
include("../../../BackEnd/config/dbconfig.php");

if (!isset($_SESSION["supplier_logged_in"]) || !isset($_SESSION["supplierid"])) {
    header("Location: ../supplierLogin.php");
    exit();
}

$months = isset($_GET['months']) ? max(1, min(12, (int) $_GET['months'])) : 1;

// Get company_id and renting_price from companies table
$stmt = $conn->prepare("SELECT company_id, renting_price FROM companies WHERE supplier_id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION["supplierid"]);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    header("Location: ../rentpayment.php?error=no_company");
    exit();
}

$company_id = $res['company_id'];
$renting_price = (float) ($res['renting_price'] ?? 1000);
$amount = $months * $renting_price;
$today = date('Y-m-d');

// Get current contract end: due_date of the latest payment (by paid_date)
$lastStmt = $conn->prepare("SELECT due_date FROM rent_payments WHERE company_id = ? ORDER BY paid_date DESC LIMIT 1");
$lastStmt->bind_param("i", $company_id);
$lastStmt->execute();
$lastRow = $lastStmt->get_result()->fetch_assoc();
$lastStmt->close();

$last_due_date = $lastRow['due_date'] ?? null;

// Overdue: today is past last due date → new row starts today, due = today + months
// Paying ahead: today <= last due date → new row starts at last_due_date, due = last_due_date + months
if ($last_due_date === null || $today > $last_due_date) {
    $paid_date = $today;
    $due_date = date('Y-m-d', strtotime("+{$months} month", strtotime($today)));
} else {
    $paid_date = $last_due_date;
    $due_date = date('Y-m-d', strtotime("+{$months} month", strtotime($last_due_date)));
}

// Store pending rent so we can INSERT on success callback
$_SESSION['pending_rent'] = [
    'company_id' => $company_id,
    'months'     => $months,
    'amount'     => $amount,
    'paid_date'  => $paid_date,
    'due_date'   => $due_date,
];

// Fetch admin account number (receiver) - same logic as nav.php
$adminStmt = $conn->prepare("SELECT account_number FROM admins LIMIT 1");
$adminStmt->execute();
$adminResult = $adminStmt->get_result();
$adminRow = $adminResult->fetch_assoc();
$adminStmt->close();
$admin_account = $adminRow['account_number'] ?? '0000000';

// Build return URL: back to suppliers rentpayment with success
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$return_url = $protocol . '://' . $host . dirname(dirname($_SERVER['PHP_SELF'])) . '/rentpayment.php?payment_status=success';

$payment_url = "https://crediverse.base44.app/payment?" . http_build_query([
    'amount'         => $amount,
    'merchant'       => 'MALLTIVERSE',
    'return_url'     => $return_url,
    'account_number' => $admin_account,
]);

header("Location: " . $payment_url);
exit();
