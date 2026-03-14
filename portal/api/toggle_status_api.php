<?php
// portal/toggle_status_api.php
require_once __DIR__ . '/../../app/Config/Config.php';
require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Helpers/Security.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Security Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$type = $data['type'] ?? '';
$id = $data['id'] ?? 0;
$status = $data['status'] ? 1 : 0;

if (!$type || !$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    $target_name = "ID #" . $id; // Default fallback
    $action_text = $status ? "Activated" : "Deactivated"; // Default log text
    
    if ($type === 'user') {
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account.']);
            exit;
        }
        $sql = "UPDATE users SET is_active = :status WHERE id = :id";
        $stmtName = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmtName->execute([$id]);
        $target_name = $stmtName->fetchColumn() ?: $target_name;
        $log_message = $action_text . " login access for user: " . $target_name;
        
    } elseif ($type === 'client') {
        $sql = "UPDATE client_accounts SET is_active = :status WHERE account_id = :id";
        $stmtName = $db->prepare("SELECT username FROM client_accounts WHERE account_id = ?");
        $stmtName->execute([$id]);
        $target_name = $stmtName->fetchColumn() ?: $target_name;
        $log_message = $action_text . " login access for client: " . $target_name;
        
    } elseif ($type === 'license') {
        $sql = "UPDATE clients SET is_active = :status WHERE client_id = :id";
        $stmtName = $db->prepare("SELECT company_name FROM clients WHERE client_id = ?");
        $stmtName->execute([$id]);
        $target_name = $stmtName->fetchColumn() ?: $target_name;
        $log_message = $action_text . " login access for license: " . $target_name;
        
    // ==========================================
    // NEW LOGIC FOR EXPENSE TOGGLE
    // ==========================================
    } elseif ($type === 'expense') {
        $sql = "UPDATE clients SET show_expenses = :status WHERE client_id = :id";
        
        $stmtName = $db->prepare("SELECT company_name FROM clients WHERE client_id = ?");
        $stmtName->execute([$id]);
        $target_name = $stmtName->fetchColumn() ?: $target_name;
        
        $action_text = $status ? "Granted" : "Revoked";
        $log_message = $action_text . " expense access for client: " . $target_name;
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        exit;
    }
    
    // Execute the database update
    $stmt = $db->prepare($sql);
    $stmt->execute([':status' => $status, ':id' => $id]);

    // Log the activity
    Security::logActivity($log_message);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // Let's send the exact SQL error so we know if the column is missing!
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System error']);
}