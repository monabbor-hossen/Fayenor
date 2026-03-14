<?php
// public/logout.php
require_once __DIR__ . '/../app/Config/Config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["params"],
        $params["secure"], $params["httponly"]
    );
}

// Clear Remember Me Token from Database
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../app/Config/Database.php';
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch(Exception $e) {}
}

// Destroy Cookie in Browser
setcookie('rooq_remember_token', '', time() - 3600, '/');
// Destroy the session
session_destroy();


// Redirect to login page
header("Location: " . BASE_URL . "public/login.php");
exit();
?>