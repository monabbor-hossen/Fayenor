<?php
// Determine protocol and domain for BASE_URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $domain . '/rooqflow/');

// Brand Identity
define('COLOR_BURGUNDY', '#800020');
define('COLOR_GOLD', '#D4AF37');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'rooqflow');
define('DB_USER', 'jihan');
define('DB_PASS', '123456');

// Add this to the bottom of app/Config/Config.php
define('CHAT_ENCRYPTION_KEY', 'Your_Super_Secret_32_Character_Key!!');
?>
