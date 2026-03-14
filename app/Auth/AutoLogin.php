<?php
// app/Auth/AutoLogin.php

// 1. We must load Config.php FIRST so the database knows the DB_HOST, DB_USER, etc.
require_once __DIR__ . '/../Config/Config.php'; 
require_once __DIR__ . '/../Config/Database.php';

function checkAutoLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // If already logged in via session, do nothing
    if (isset($_SESSION['user_id'])) return;

    if (isset($_COOKIE['rooq_remember_token'])) {
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$_COOKIE['rooq_remember_token']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Setup session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                if ($user['role'] === 'client') {
                    $stmtClient = $db->prepare("SELECT client_id FROM clients WHERE account_id = ?");
                    $stmtClient->execute([$user['id']]);
                    $clientData = $stmtClient->fetch();
                    if ($clientData) $_SESSION['account_id'] = $clientData['client_id'];
                }
                
                // Keep cookie fresh for another 30 days
                $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                setcookie('rooq_remember_token', $_COOKIE['rooq_remember_token'], time() + (86400 * 30), "/", "", $isSecure, true);
            } else {
                // Clear invalid cookie
                setcookie('rooq_remember_token', '', time() - 3600, '/'); 
            }
        } catch (Exception $e) {
            // Silently fail so the page still loads even if DB is down
        }
    }
}