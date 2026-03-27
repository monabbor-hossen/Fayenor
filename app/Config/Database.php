<?php
// app/Config/Database.php

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            // Set error mode to exception for security and debugging
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Disable emulated prepared statements for better security
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            // Log error safely without exposing credentials
            error_log("Connection error: " . $exception->getMessage());
            die("Critical Error: Unable to connect to the database.");
        }
        return $this->conn;
    }
}
?>