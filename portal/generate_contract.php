<?php
session_start();
require_once '../app/Config/Config.php';
require_once '../app/Config/Database.php';

// Security Check (Admin/Staff only)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'client') {
    header("Location: ../public/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id'])) {
    $client_id = intval($_POST['client_id']);
    
    try {
        $db = (new Database())->getConnection();

        // 1. Check if a contract already exists for this client
        $stmt = $db->prepare("SELECT client_id FROM client_contracts WHERE client_id = ?");
        $stmt->execute([$client_id]);
        
        if ($stmt->rowCount() == 0) {
            // 2. If it doesn't exist, insert a new blank record.
            // By inserting a blank record, your contract.php logic will automatically 
            // inherit all the text from the Global Default settings!
            $insert = $db->prepare("INSERT INTO client_contracts (client_id) VALUES (?)");
            $insert->execute([$client_id]);
            
            $_SESSION['contract_success'] = "Contract generated successfully! You can now edit it or view the PDF.";
        } else {
            $_SESSION['contract_error'] = "A contract has already been generated for this client.";
        }

    } catch (Exception $e) {
        $_SESSION['contract_error'] = "Database Error: " . $e->getMessage();
    }

    // Redirect them back to the Contract tab list
    header("Location: default-contract.php");
    exit();
} else {
    // If accessed without a POST request
    header("Location: default-contract.php");
    exit();
}