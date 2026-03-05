<?php
session_start();
require_once __DIR__ . '/../Config/Database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if (isset($data['client_id']) && isset($data['show_expenses'])) {
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE clients SET show_expenses = ? WHERE client_id = ?");
        $stmt->execute([intval($data['show_expenses']), intval($data['client_id'])]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}