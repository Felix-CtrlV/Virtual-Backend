<?php
// ================= SESSION & COOKIE FIXES =================
ini_set('session.cookie_samesite', 'Lax'); // required for OAuth redirects
ini_set('session.cookie_secure', '0');     // localhost only
session_start();

// ================= REQUIRE FILES =================
require_once '../../BackEnd/config/dbconfig.php';
require_once __DIR__ . '/oauth_config.php';

// ================= GOOGLE CONFIG =================
$client_id     = GOOGLE_CLIENT_ID;
$client_secret = GOOGLE_CLIENT_SECRET;
$redirect_uri  = BASE_URL . '/utils/google_oauth.php';

// Optional return URL after login
$return_url = $_GET['return_url'] ?? null;

// ================= USER TYPE =================
$user_type = $_GET['type'] ?? 'supplier';

// ================= STEP 1: REDIRECT TO GOOGLE =================
if (!isset($_GET['code'])) {
    // Generate CSRF state
    $state = bin2hex(random_bytes(16));

    $_SESSION['oauth_state']     = $state;
    $_SESSION['oauth_user_type'] = $user_type;
    $_SESSION['oauth_return_url'] = $return_url;

    $auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'response_type' => 'code',
        'scope'         => 'email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account'
    ]);

    header('Location: ' . $auth_url);
    exit;
}

// ================= STEP 2: STATE VALIDATION =================
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Invalid or missing OAuth state. Session may have expired.');
}

// Restore user type and return URL
$user_type  = $_SESSION['oauth_user_type'] ?? 'supplier';
$return_url = $_SESSION['oauth_return_url'] ?? null;

// Cleanup session
unset($_SESSION['oauth_state'], $_SESSION['oauth_user_type'], $_SESSION['oauth_return_url']);

// ================= STEP 3: TOKEN EXCHANGE =================
$token_url = 'https://oauth2.googleapis.com/token';
$post_fields = [
    'client_id'     => $client_id,
    'client_secret' => $client_secret,
    'code'          => $_GET['code'],
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($post_fields),
    CURLOPT_SSL_VERIFYPEER => false, // localhost fix
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);

if ($response === false) {
    die('Curl error (Token Exchange): ' . curl_error($ch));
}

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    die('Failed to retrieve access token. Google Response: ' . $response);
}

$access_token = $token_data['access_token'];

// ================= STEP 4: GET USER INFO =================
$userinfo_url = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $access_token;

$ch = curl_init($userinfo_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$user_info = json_decode(curl_exec($ch), true);

$email    = $user_info['email'] ?? null;
$name     = $user_info['name'] ?? null;
$verified = $user_info['verified_email'] ?? false;

if (!$email || !$verified) {
    die('Google account email is not verified.');
}

// ================= STEP 5: ROLE HANDLING =================

// ===== ADMIN =====
if ($user_type === 'admin') {
    $stmt = $conn->prepare("SELECT adminid FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die('Access Denied: Admin not registered.');
    }

    $admin = $result->fetch_assoc();
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['adminid'] = $admin['adminid'];

    header('Location: ../admin/dashboard.php');
    exit;
}

// ===== SUPPLIER =====
if ($user_type === 'supplier') {
    $stmt = $conn->prepare("SELECT supplier_id, status FROM suppliers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $supplier = $result->fetch_assoc();

        if (($supplier['status'] ?? '') === 'banned') {
            $_SESSION['banned_user'] = [
                'id' => $supplier['supplier_id'],
                'type' => 'supplier'
            ];
            header('Location: ../banned.php');
            exit;
        }

        $_SESSION['supplier_logged_in'] = true;
        $_SESSION['supplierid'] = $supplier['supplier_id'];
    } else {
        header('Location: ../supplierLogin.php?error=not_registered');
        exit;
    }

    $redirect_to = $return_url ?: '../suppliers/dashboard.php';
    header('Location: ' . $redirect_to);
    exit;
}

// ===== CUSTOMER (DEFAULT) =====
$stmt = $conn->prepare("SELECT customer_id, status FROM customers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();

    if (($customer['status'] ?? '') === 'banned') {
        $_SESSION['banned_user'] = [
            'id' => $customer['customer_id'],
            'type' => 'customer'
        ];
        header('Location: ../banned.php');
        exit;
    }

    $_SESSION['customer_logged_in'] = true;
    $_SESSION['customer_id'] = $customer['customer_id'];
} else {
    header('Location: ../customerLogin.php?error=not_registered');
    exit;
}

// Redirect to return URL if set, otherwise homepage
$redirect_to = $return_url ?: '../index.html';
header('Location: ' . $redirect_to);
exit;
