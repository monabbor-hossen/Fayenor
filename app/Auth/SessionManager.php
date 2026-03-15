<?php
// app/Auth/SessionManager.php

require_once dirname(__DIR__) . '/Config/Config.php';
require_once dirname(__DIR__) . '/Config/Database.php';
require_once dirname(__DIR__) . '/Helpers/Security.php';
require_once dirname(__DIR__) . '/Helpers/RateLimiter.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SessionManager {
    private $db;
    private $limiter;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->limiter = new RateLimiter();
    }

    // FIXED: Properly writes to activity_logs table without crashing
    private function logActivity($username, $ip, $activity, $user_type = 'internal') {
        try {
            $sql = "INSERT INTO activity_logs (user_id, user_type, username, action, ip_address) VALUES (:uid, :utype, :uname, :act, :ip)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':uid' => $_SESSION['user_id'] ?? 0,
                ':utype' => $user_type,
                ':uname' => $username,
                ':act' => $activity,
                ':ip' => $ip
            ]);
        } catch (Exception $e) {
            // Fail silently so a log error never stops a user from logging in
        }
    }

    public function login($username, $password, $csrf_token) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $clean_user = Security::clean($username);

        // Check Rate Limit
        if ($this->limiter->isLocked($ip)) {
            $this->logActivity($clean_user, $ip, "Blocked: IP Locked");
            throw new Exception("Security Alert: Too many failed attempts. Your IP is locked for 15 minutes.");
        }

        Security::checkCSRF($csrf_token);

        try {
            $query = "SELECT id, username, password, role, is_active, full_name FROM users WHERE username = :user LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user', $clean_user);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    throw new Exception("Security Alert: Account deactivated.");
                }

                if ($user['role'] === 'client') {
                    $stmtClient = $this->db->prepare("SELECT client_id FROM clients WHERE account_id = :id LIMIT 1");
                    $stmtClient->execute([':id' => $user['id']]);
                    $clientData = $stmtClient->fetch(PDO::FETCH_ASSOC);
                    $user['client_id'] = $clientData ? $clientData['client_id'] : null;
                    
                    $this->createSession($user, 'client');
                    $this->logActivity($clean_user, $ip, "Client Logged In", 'client'); // FIXED
                } else {
                    $this->createSession($user, 'internal');
                    $this->logActivity($clean_user, $ip, "User Logged In", 'internal'); // FIXED
                }

                $this->limiter->reset($ip);
                return true;
            }

            // LOGIN FAILED
            $error_msg = $this->limiter->increment($ip);
            $this->logActivity($clean_user, $ip, "Failed: Invalid Credentials");
            throw new Exception($error_msg);

        } catch (PDOException $e) {
            throw new Exception("System error occurred.");
        }
    }
    
    private function createSession($data, $type) {
        $_SESSION['user_id'] = $data['id'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['full_name'] = !empty($data['full_name']) ? $data['full_name'] : $data['username'];
        
        if ($type === 'client') {
            $_SESSION['client_id'] = $data['client_id'] ?? null;
            $_SESSION['account_id'] = $data['id']; 
            $_SESSION['user_type'] = 'external';
        } else {
            $_SESSION['user_type'] = 'internal';
        }
        
        $_SESSION['last_regen'] = time();
        session_regenerate_id();
    }

    public function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: " . BASE_URL . "public/login"); // Removed .php for clean URLs
        exit();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}
?>