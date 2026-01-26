<?php
session_start();
require_once '../../BackEnd/config/dbconfig.php';
require_once __DIR__ . '/oauth_config.php';

$client_id = GITHUB_CLIENT_ID;
$client_secret = GITHUB_CLIENT_SECRET;
$redirect_uri = BASE_URL . '/utils/github_oauth.php';
$return_url = $_GET['return_url'] ?? null;

// Default to supplier if no type is specified
$user_type = $_GET['type'] ?? 'supplier';

// Step 1: Redirect to GitHub
if (!isset($_GET['code'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_user_type'] = $user_type;

    // Save return_url in session to survive GitHub redirect
    $_SESSION['oauth_return_url'] = $return_url;

    $auth_url = "https://github.com/login/oauth/authorize?" . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => 'user:email',
        'state' => $state
    ]);

    header('Location: ' . $auth_url);
    exit;
}

// Step 2: Handle GitHub callback
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Invalid state parameter.');
}

$code = $_GET['code'];
$user_type = $_SESSION['oauth_user_type'] ?? 'supplier';
$return_url = $_SESSION['oauth_return_url'] ?? null;

// Clear session values
unset($_SESSION['oauth_state'], $_SESSION['oauth_user_type'], $_SESSION['oauth_return_url']);

// Step 3: Exchange code for access token
$token_url = 'https://github.com/login/oauth/access_token';
$post_fields = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'code' => $code,
    'redirect_uri' => $redirect_uri
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
// Localhost SSL bypass
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$token_response = curl_exec($ch);
if ($token_response === false) die('Curl error (Token Exchange): ' . curl_error($ch));

$token = json_decode($token_response, true);
if (!isset($token['access_token'])) die('Failed to get access token: ' . htmlspecialchars($token_response));

$access_token = $token['access_token'];

// Step 4: Get user info
$ch = curl_init('https://api.github.com/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $access_token,
    'User-Agent: Malltiverse-App'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$user_info = json_decode(curl_exec($ch), true);

// Step 4a: Get user email
$ch = curl_init('https://api.github.com/user/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $access_token,
    'User-Agent: Malltiverse-App'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$emails = json_decode(curl_exec($ch), true);

$email = null;
if (is_array($emails)) {
    foreach ($emails as $email_data) {
        if (($email_data['primary'] ?? false) && ($email_data['verified'] ?? false)) {
            $email = $email_data['email'];
            break;
        }
    }
    if (!$email && count($emails) > 0) $email = $emails[0]['email'];
}
if (!$email) die('No verified email found for this GitHub account.');

$name = $user_info['name'] ?? $user_info['login'] ?? 'User';

// Step 5: Login / Register based on user type
switch ($user_type) {
    case 'admin':
        $stmt = $conn->prepare("SELECT adminid FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['adminid'] = $admin['adminid'];
            $redirect_to = $return_url ?: '../admin/dashboard.php';
        } else {
            die("Access Denied: GitHub account not registered as Admin.");
        }
        break;

    case 'supplier':
        $stmt = $conn->prepare("SELECT supplier_id FROM suppliers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $supplier = $result->fetch_assoc();
            $_SESSION['supplier_logged_in'] = true;
            $_SESSION['supplierid'] = $supplier['supplier_id'];
            $redirect_to = $return_url ?: '../suppliers/dashboard.php';
        } else {
            // Register supplier
            $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $company_name = $name . "'s Shop";
            $stmt = $conn->prepare("INSERT INTO suppliers (name, email, password, company_name, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("ssss", $name, $email, $password, $company_name);
            if ($stmt->execute()) {
                $_SESSION['supplier_logged_in'] = true;
                $_SESSION['supplierid'] = $conn->insert_id;
                $redirect_to = $return_url ?: '../suppliers/dashboard.php';
            } else {
                $redirect_to = '../supplierLogin.php?error=registration_failed';
            }
        }
        break;

    case 'customer':
    default:
        $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $_SESSION['customer_logged_in'] = true;
            $_SESSION['customer_id'] = $customer['customer_id'];
            $redirect_to = $return_url ?: '../index.html';
        } else {
            // Register customer
            $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customers (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $password);
            if ($stmt->execute()) {
                $_SESSION['customer_logged_in'] = true;
                $_SESSION['customer_id'] = $conn->insert_id;
                $redirect_to = $return_url ?: '../index.html';
            } else {
                $redirect_to = '../customerLogin.php?error=registration_failed';
            }
        }
        break;
}

// Redirect user
header('Location: ' . $redirect_to);
exit;
?>
