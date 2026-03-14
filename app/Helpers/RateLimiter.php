<?php
// app/Helpers/RateLimiter.php

require_once __DIR__ . '/../Config/Database.php';

class RateLimiter {
    private $db;
    private $max_attempts = 5; // Lock after 5 fails
    private $lockout_time = 15; // Lock for 15 minutes

    public function __construct() {
        try {
            $this->db = (new Database())->getConnection();
        } catch (Exception $e) {
            // Fail silently so we don't crash the whole app if DB has a hiccup
        }
    }

    /**
     * Check if the IP is currently locked out.
     */
    public function isLocked($ip) {
        if (!$this->db) return false;

        try {
            $stmt = $this->db->prepare("SELECT attempts, locked_until FROM login_attempts WHERE ip_address = :ip LIMIT 1");
            $stmt->execute([':ip' => $ip]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) return false;

            // SAFE DATE CHECK: Only evaluate if 'locked_until' is actually set
            if (!empty($data['locked_until'])) {
                $lockTime = new DateTime($data['locked_until']);
                $now = new DateTime();
                
                if ($now < $lockTime) {
                    return true; // Still locked
                } else {
                    $this->reset($ip); // Lock expired, clear it
                    return false;
                }
            }

            return false;
        } catch (Exception $e) {
            return false; // If date parsing fails, let them try logging in
        }
    }

    /**
     * Record a failed login attempt.
     */
    public function increment($ip) {
        if (!$this->db) return "Error connecting to security database.";

        try {
            // Check existing record
            $stmt = $this->db->prepare("SELECT attempts FROM login_attempts WHERE ip_address = :ip");
            $stmt->execute([':ip' => $ip]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $attempts = $row['attempts'] + 1;
                
                if ($attempts >= $this->max_attempts) {
                    // Lock the user
                    $lock_time = date('Y-m-d H:i:s', strtotime("+$this->lockout_time minutes"));
                    $upd = $this->db->prepare("UPDATE login_attempts SET attempts = :a, last_attempt = NOW(), locked_until = :l WHERE ip_address = :ip");
                    $upd->execute([':a' => $attempts, ':l' => $lock_time, ':ip' => $ip]);
                    return "Security Alert: Too many failed attempts. Locked for $this->lockout_time minutes.";
                } else {
                    // Just increment
                    $upd = $this->db->prepare("UPDATE login_attempts SET attempts = :a, last_attempt = NOW() WHERE ip_address = :ip");
                    $upd->execute([':a' => $attempts, ':ip' => $ip]);
                    $remaining = $this->max_attempts - $attempts;
                    return "Invalid credentials. $remaining attempts remaining.";
                }
            } else {
                // First fail
                $ins = $this->db->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (:ip, 1, NOW())");
                $ins->execute([':ip' => $ip]);
                return "Invalid credentials.";
            }
        } catch (Exception $e) {
            return "System error while recording login attempt.";
        }
    }

    /**
     * Clear attempts after successful login.
     */
    public function reset($ip) {
        if (!$this->db) return;
        try {
            $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = :ip");
            $stmt->execute([':ip' => $ip]);
        } catch (Exception $e) {}
    }
}