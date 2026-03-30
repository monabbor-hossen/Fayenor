<?php
// portal/users/user-delete.php
require_once __DIR__ . '/../../app/Config/Config.php';
require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Helpers/Security.php';

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
        // FIXED: Redirect to the index of the current folder
        header("Location: ./index?error=cannot_delete_self");
        exit();
    }

    // 3. Delete from Database
    try {
        $db = (new Database())->getConnection();

        // FIXED: Actually fetch the username BEFORE we delete it so the log works!
        $stmtFetch = $db->prepare("SELECT username FROM users WHERE id = :id LIMIT 1");
        $stmtFetch->execute([':id' => $delete_id]);
        $user = $stmtFetch->fetch(PDO::FETCH_ASSOC);
        $deleted_username = $user ? $user['username'] : 'Unknown User';

        // Now delete the user
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $delete_id);
        
        if ($stmt->execute()) {
            Security::logActivity("Deleted user account: " . $deleted_username);
            header("Location: ./index?msg=deleted"); // FIXED: Correct routing
        } else {
            header("Location: ./index?error=failed");
        }
        exit();

    } catch (Exception $e) {
        // FIXED: Removed the broken rollBack() command
        header("Location: ./index?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Redirect if accessed directly via GET
    header("Location: ./index");
    exit();
}
?>