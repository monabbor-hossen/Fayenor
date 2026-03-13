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

        // 1. Check if a contract already exists for this client in the database
        $stmt = $db->prepare("SELECT client_id FROM client_contracts WHERE client_id = ?");
        $stmt->execute([$client_id]);
        
        // 2. If it does NOT exist, create the record so it shows up in your lists
        if ($stmt->rowCount() == 0) {
            $insert = $db->prepare("INSERT INTO client_contracts (client_id) VALUES (?)");
            $insert->execute([$client_id]);
        }

        // 3. PERFECT REDIRECT: Send them directly to view the contract!
        header("Location: ../contract/contract.php?id=" . $client_id);
        exit();

    } catch (Exception $e) {
        // If there is a database error, go back and show the error message
        $_SESSION['contract_error'] = "Database Error: " . $e->getMessage();
        header("Location: default-contract.php");
        exit();
    }
} else {
    // If accessed directly without clicking the button
    header("Location: default-contract.php");
    exit();
}