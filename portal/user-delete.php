<?php
// portal/user-delete.php
require_once __DIR__ . '/../app/Config/Config.php';
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Helpers/Security.php';

// Ensure user is logged in
Security::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid security token.");
    }

    $delete_id = $_POST['delete_id'];
    $current_user_id = $_SESSION['user_id'];

    // 2. Prevent Self-Deletion
    if ($delete_id == $current_user_id) {
        header("Location: users?error=cannot_delete_self");
        exit();
    }

    // 3. Delete from Database
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $delete_id);
        // Assuming you have fetched the username before deleting it for the log
        Security::logActivity("Deleted user account: " . $deleted_username);
        if ($stmt->execute()) {
            header("Location: users?msg=deleted");
        } else {
            header("Location: users?error=failed");
        }
    } catch (PDOException $e) {
        header("Location: users.php?error=" . urlencode($e->getMessage()));
    }
    exit();
} else {
    // Redirect if accessed directly via GET
    header("Location: users");
    exit();
}
?>