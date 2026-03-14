<?php
// public/auth_process.php
require_once __DIR__ . '/../app/Auth/SessionManager.php';
require_once __DIR__ . '/../app/Config/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new SessionManager();
    $user  = $_POST['username'] ?? '';
    $pass  = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    try {
        if ($auth->login($user, $pass, $token)) {
            // Set Remember Me Cookie
            if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1' && isset($_SESSION['user_id'])) {
                try {
                    $db = (new Database())->getConnection();
                    $remember_token = bin2hex(random_bytes(32)); 
                    $updateToken = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $updateToken->execute([$remember_token, $_SESSION['user_id']]);
                    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                    setcookie('rooq_remember_token', $remember_token, time() + (86400 * 30), "/", "", $isSecure, true); 
                } catch (Exception $e) {}
            }

            // Redirect to appropriate dashboard
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
                header("Location: ../management/dashboard");
            } else {
                header("Location: ../portal/dashboard");
            }
            exit();
        }
    } catch (Exception $e) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['error'] = $e->getMessage();
        header("Location: login");
        exit();
    }
} else {
    header("Location: login");
    exit();
}