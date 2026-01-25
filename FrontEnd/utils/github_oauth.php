<?php
session_start();
require_once '../../BackEnd/config/dbconfig.php';
require_once __DIR__ . '/oauth_config.php';

$client_id = GITHUB_CLIENT_ID;
$client_secret = GITHUB_CLIENT_SECRET;
$redirect_uri = BASE_URL . '/utils/github_oauth.php';

$user_type = $_GET['type'] ?? 'supplier'; // 'supplier' or 'customer'

// Step 1: Redirect to GitHub OAuth if code not present
if (!isset($_GET['code'])) {
    $state = bin2hex(random_bytes(16)); // CSRF protection
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_user_type'] = $user_type;

    $auth_url = "https://github.com/login/oauth/authorize?" . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => 'user:email',
        'state' => $state
    ]);

    header('Location: ' . $auth_url);
    exit;
} else {
    // Step 2: Verify state
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        die('Invalid state parameter. Possible CSRF attack.');
    }

    $user_type = $_SESSION['oauth_user_type'] ?? 'supplier';
    unset($_SESSION['oauth_state'], $_SESSION['oauth_user_type']);

    $code = $_GET['code'];

    // Exchange code for access token
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 🔹 DISABLE SSL VERIFY FOR LOCALHOST
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 🔹 DISABLE SSL VERIFY FOR LOCALHOST
    $token_response = curl_exec($ch);
    if ($token_response === false) {
        die('Curl error: ' . curl_error($ch));
    }
    $token = json_decode($token_response, true);

    if (!isset($token['access_token'])) {
        die('Failed to get access token: ' . htmlspecialchars($token_response));
    }

    $access_token = $token['access_token'];

    // Step 3: Get user info
    $ch = curl_init('https://api.github.com/user');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $access_token,
        'User-Agent: Malltiverse-App'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 🔹 LOCALHOST FIX
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 🔹 LOCALHOST FIX
    $user_info = json_decode(curl_exec($ch), true);

    // Step 3a: Get emails
    $ch = curl_init('https://api.github.com/user/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $access_token,
        'User-Agent: Malltiverse-App'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 🔹 LOCALHOST FIX
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 🔹 LOCALHOST FIX
    $emails = json_decode(curl_exec($ch), true);

    $email = null;
    if (is_array($emails)) {
        foreach ($emails as $email_data) {
            if (($email_data['primary'] ?? false) && ($email_data['verified'] ?? false)) {
                $email = $email_data['email'];
                break;
            }
        }
        if (!$email && count($emails) > 0) {
            $email = $emails[0]['email'];
        }
    }

    if (!$email) {
        die('No verified email found for this GitHub account.');
    }

    $name = $user_info['name'] ?? $user_info['login'] ?? 'User';

    // Step 4: Login or register user
    if ($user_type === 'supplier') {
        $stmt = $conn->prepare("SELECT supplier_id FROM suppliers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $supplier = $result->fetch_assoc();
            $_SESSION['supplier_logged_in'] = true;
            $_SESSION['supplierid'] = $supplier['supplier_id'];
            header('Location: ../suppliers/dashboard.php');
        } else {
            $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO suppliers (name, email, password, company_name, status) VALUES (?, ?, ?, ?, 'pending')");
            $company_name = $name . "'s Shop";
            $stmt->bind_param("ssss", $name, $email, $password, $company_name);
            if ($stmt->execute()) {
                $_SESSION['supplier_logged_in'] = true;
                $_SESSION['supplierid'] = $conn->insert_id;
                header('Location: ../suppliers/dashboard.php');
            } else {
                header('Location: ../supplierLogin.php?error=registration_failed');
            }
        }
    } else {
        $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $_SESSION['customer_logged_in'] = true;
            $_SESSION['customer_id'] = $customer['customer_id'];
            header('Location: ../index.html');
        } else {
            $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customers (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $password);
            if ($stmt->execute()) {
                $_SESSION['customer_logged_in'] = true;
                $_SESSION['customer_id'] = $conn->insert_id;
                header('Location: ../index.html');
            } else {
                header('Location: ../customerLogin.php?error=registration_failed');
            }
        }
    }
    exit;
}
