<?php
// public/auth_process.php
require_once __DIR__ . '/../app/Auth/SessionManager.php';
require_once __DIR__ . '/../app/Config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new SessionManager();
    
    $user  = $_POST['username'] ?? '';
    $pass  = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    try {
        // Attempt Login
        if ($auth->login($user, $pass, $token)) {
            
            // ====================================================================
            // REMEMBER ME LOGIC (Set Cookie for 30 Days)
            // ====================================================================
            if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1' && isset($_SESSION['user_id'])) {
                try {
                    $db = (new Database())->getConnection();
                    // Generate a highly secure random token
                    $remember_token = bin2hex(random_bytes(32)); 
                    
                    // Save token to database
                    $updateToken = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $updateToken->execute([$remember_token, $_SESSION['user_id']]);
                    
                    // Set the cookie (Expires in 30 days, HttpOnly for XSS protection)
                    // It detects if you are using HTTPS to set the secure flag appropriately
                    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                    setcookie('rooq_remember_token', $remember_token, time() + (86400 * 30), "/", "", $isSecure, true); 
                } catch (Exception $e) {
                    // Fail silently so the login still succeeds even if the cookie fails
                }
            }
            // ====================================================================

            // Success Redirect
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
                header("Location: ../management/dashboard.php");
            } else {
                header("Location: ../portal/dashboard.php");
            }
            exit();
        }
    } catch (Exception $e) {
        // Security Error (Locked out, or Wrong Password)
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['error'] = $e->getMessage(); // Show the specific security message
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}