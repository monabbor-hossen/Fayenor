<?php
require_once __DIR__ . '/app/Config/Config.php';
require_once __DIR__ . '/app/Config/Database.php';

try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("DELETE FROM login_attempts");
    echo "<h1 style='color: green; text-align: center; margin-top: 50px;'>Success! Security Lockout Cleared.</h1>";
    echo "<p style='text-align: center;'>You can safely close this tab and go back to the login page.</p>";
    echo "<script>setTimeout(() => { window.location.href = 'public/login'; }, 3000);</script>";
} catch (Exception $e) {
    echo "Error clearing DB: " . $e->getMessage();
}
?>
