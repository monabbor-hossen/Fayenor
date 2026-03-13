<?php
// app/Api/fetch_chats.php
require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/../Config/Database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
session_write_close(); // Prevents XAMPP from freezing

// Fallback safety for the encryption key
if (!defined('CHAT_ENCRYPTION_KEY')) {
    define('CHAT_ENCRYPTION_KEY', 'BasmatRooq_Super_Secret_Key_2024!'); 
}

$client_id = $_GET['client_id'] ?? 0;
if (!$client_id || !isset($_SESSION['user_id'])) exit;

$viewer_type = ($_SESSION['role'] === 'client') ? 'client' : 'internal';

try {
    $db = (new Database())->getConnection();
    
    // Mark messages as read
    if ($viewer_type === 'internal') {
        $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE client_id = ? AND sender_type = 'client'")->execute([$client_id]);
    } else {
        $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE client_id = ? AND sender_type IN ('admin', 'staff')")->execute([$client_id]);
    }

    $stmt = $db->prepare("
        SELECT c.*, u.full_name as internal_name, cl.company_name as client_name 
        FROM chat_messages c
        LEFT JOIN users u ON c.sender_id = u.id AND c.sender_type IN ('admin', 'staff')
        LEFT JOIN clients cl ON c.sender_id = cl.client_id AND c.sender_type = 'client'
        WHERE c.client_id = ? ORDER BY c.created_at ASC
    ");
    $stmt->execute([$client_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    foreach ($messages as $msg) {
        $is_me = ($viewer_type === 'client' && $msg['sender_type'] === 'client') || ($viewer_type === 'internal' && in_array($msg['sender_type'], ['admin', 'staff']));
        
        // ========================================================================
        // DECRYPTION LOGIC (With Smart Fallback for old unencrypted messages)
        // ========================================================================
        $encryption_method = 'aes-256-cbc';
        $stored_payload = $msg['message'];
        
        // Attempt to base64 decode
        $decoded_data = base64_decode($stored_payload, true);
        $iv_length = openssl_cipher_iv_length($encryption_method);

        // Check if it's a valid encrypted payload
        if ($decoded_data !== false && strlen($decoded_data) > $iv_length) {
            $iv = substr($decoded_data, 0, $iv_length);
            $encrypted_text = substr($decoded_data, $iv_length);
            $decrypted_message = openssl_decrypt($encrypted_text, $encryption_method, CHAT_ENCRYPTION_KEY, 0, $iv);
            
            // If decryption succeeds, use it. Otherwise, fallback to the raw text
            $display_message = ($decrypted_message !== false) ? $decrypted_message : $stored_payload;
        } else {
            // Old Plain-Text Fallback
            $display_message = $stored_payload;
        }
        // ========================================================================

        // Use Flexbox to align the wrapper instead of margins
        $wrapper_align = $is_me ? 'justify-content-end' : 'justify-content-start';
        $text_align = $is_me ? 'text-end' : 'text-start';
        
        $bg_color = $is_me ? 'background: #800020; color: #fff;' : 'background: rgba(255,255,255,0.05); color: #fff; border-left: 3px solid #D4AF37;';
        $border_radius = $is_me ? 'border-radius: 15px 15px 2px 15px;' : 'border-radius: 15px 15px 15px 2px;';
        $time = date('M d, h:i A', strtotime($msg['created_at']));
        
        $sender_name = '';
        if (!$is_me) {
            $name = ($msg['sender_type'] === 'client') ? $msg['client_name'] : ($msg['internal_name'] ?? 'Basmat Rooq Team');
            // Align the sender's name slightly with the bubble
            $sender_name = "<div class='small text-gold fw-bold mb-1 ps-1'>{$name}</div>";
        }

        $html .= "
            <div class='d-flex {$wrapper_align} mb-3 w-100'>
                <div class='d-flex flex-column {$text_align}' style='max-width: 85%;'>
                    {$sender_name}
                    <div class='p-3 shadow-sm' style='{$bg_color} {$border_radius} display: inline-block; text-align: left; word-break: break-word;'>
                        " . nl2br(htmlspecialchars($display_message)) . "
                    </div>
                    <div class='small text-white-50 mt-1 px-1' style='font-size: 0.7rem;'>{$time}</div>
                </div>
            </div>";
    }
    echo $html;

} catch (PDOException $e) { 
    echo "<div class='text-danger text-center mt-5 bg-dark p-4 rounded border border-danger'>
            <h4>Database Error Occurred</h4>
            <p>" . $e->getMessage() . "</p>
          </div>";
}
?>