<?php
// app/Api/check_notifications.php
require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/../Config/Database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $notifications = [];
    $unread_count = 0;

    if ($_SESSION['role'] === 'client') {
        $acc_id = $_SESSION['account_id'] ?? $_SESSION['user_id'];
        $stmt = $db->prepare("SELECT c.client_id, c.message, c.created_at, 'Basmat Rooq Team' as sender_name 
                              FROM chat_messages c 
                              JOIN clients cl ON c.client_id = cl.client_id 
                              WHERE cl.account_id = ? AND c.sender_type IN ('admin', 'staff') AND c.is_read = 0 
                              ORDER BY c.created_at DESC LIMIT 5");
        $stmt->execute([$acc_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare("SELECT c.client_id, c.message, c.created_at, cl.company_name as sender_name 
                              FROM chat_messages c 
                              JOIN clients cl ON c.client_id = cl.client_id 
                              WHERE c.sender_type = 'client' AND c.is_read = 0 
                              ORDER BY c.created_at DESC LIMIT 5");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $unread_count = count($notifications);

    // Build the Dropdown HTML to return
    $html = '';
    if ($unread_count > 0) {
        foreach ($notifications as $notif) {
            $time = date('h:i A', strtotime($notif['created_at']));
            $sender = htmlspecialchars($notif['sender_name']);
            $msg = htmlspecialchars($notif['message']);
            
            $html .= "
            <li>
                <a class='dropdown-item py-3 px-3 border-bottom border-light border-opacity-10 text-white hover-white' href='chat?client_id={$notif['client_id']}' style='white-space: normal;'>
                    <div class='d-flex justify-content-between align-items-center mb-1'>
                        <span class='fw-bold small'>{$sender}</span>
                        <span class='text-secondary' style='font-size: 0.65rem;'>{$time}</span>
                    </div>
                    <div class='text-white-50 small text-truncate' style='max-width: 270px;'>\"{$msg}\"</div>
                </a>
            </li>";
        }
        $html .= "<li><a class='dropdown-item text-center text-secondary small py-2 fw-bold' href='chat'>View All Messages</a></li>";
    } else {
        $html .= "<li><div class='dropdown-item text-white-50 small py-4 text-center'>No new messages</div></li>";
    }

    echo json_encode(['count' => $unread_count, 'html' => $html]);

} catch (Exception $e) {
    echo json_encode(['count' => 0, 'html' => '']);
}
?>