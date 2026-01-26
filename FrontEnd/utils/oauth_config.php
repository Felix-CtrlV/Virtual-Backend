<?php
/**
 * OAuth Configuration File
 * 
 * INSTRUCTIONS:
 * 1. For Google OAuth:
 *    - Go to https://console.cloud.google.com/
 *    - Create a new project or select existing
 *    - Enable Google+ API
 *    - Go to Credentials > Create Credentials > OAuth 2.0 Client ID
 *    - Add authorized redirect URI: http://yourdomain.com/FrontEnd/utils/google_oauth.php
 *    - Copy Client ID and Client Secret below
 * 
 * 2. For GitHub OAuth:
 *    - Go to https://github.com/settings/developers
 *    - Click "New OAuth App"
 *    - Set Authorization callback URL: http://yourdomain.com/FrontEnd/utils/github_oauth.php
 *    - Copy Client ID and Client Secret below
 */

// Google OAuth Credentials
define('GOOGLE_CLIENT_ID', '466100344494-kdrg075kh1f8ob3k2bfdchqjegkmek8r.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// GitHub OAuth Credentials
define('GITHUB_CLIENT_ID', 'Ov23livJcudpX9qQPoOi');
define('GITHUB_CLIENT_SECRET', '64976b12e34a0810bf11f0ba4bd77128a049bc05');

// Base URL (auto-detect or set manually)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_path = dirname(dirname($_SERVER['PHP_SELF']));
define('BASE_URL', $protocol . '://' . $host . $base_path);
?>
