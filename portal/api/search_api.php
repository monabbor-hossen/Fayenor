<?php
// portal/search_api.php

// 1. Disable HTML Errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. JSON Header
header('Content-Type: application/json; charset=utf-8');

try {
    // 3. Load Dependencies
    $appPath = dirname(dirname(__DIR__)) . '/app';
    require_once $appPath . '/Config/Config.php';
    require_once $appPath . '/Config/Database.php';

    // 4. Check Login
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([]);
        exit;
    }

    // 5. Input Validation
    $term = $_GET['term'] ?? '';
    if (strlen($term) < 2) {
        echo json_encode([]);
        exit;
    }

    // 6. Database Connection
    $db = (new Database())->getConnection();

    // 7. QUERY (Added phone_number)
    $sql = "SELECT c.*, 
            w.hire_foreign_company, w.misa_application, w.sbc_application, 
            w.article_association, w.qiwa, w.muqeem, w.gosi, w.chamber_commerce,
            w.license_scope_status,
            COALESCE((SELECT SUM(amount) FROM payments WHERE client_id = c.client_id AND payment_status = 'Completed'), 0) as total_paid
            FROM clients c 
            LEFT JOIN workflow_tracking w ON c.client_id = w.client_id
            WHERE (c.company_name LIKE :s1 
               OR c.client_name LIKE :s2 
               OR c.email LIKE :s3 
               OR c.phone_number LIKE :s4 
               OR c.client_id = :sid) 
            LIMIT 5";

    $stmt = $db->prepare($sql);
    
    // 8. EXECUTE (Added :s4 binding)
    $likeTerm = "%$term%";
    $stmt->execute([
        ':s1'  => $likeTerm,
        ':s2'  => $likeTerm,
        ':s3'  => $likeTerm,
        ':s4'  => $likeTerm, // Phone Number Match
        ':sid' => intval($term)
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 9. Format Results
    foreach ($results as &$row) {
        // Due Calculation
        $row['due_val'] = floatval($row['contract_value']) - floatval($row['total_paid']);
        
        // Progress Calculation
        $steps = [
            $row['hire_foreign_company'], $row['misa_application'], $row['sbc_application'], 
            $row['article_association'], $row['qiwa'], $row['muqeem'], $row['gosi'], $row['chamber_commerce']
        ];
        
        $approved = 0;
        $active_steps = 0;
        
        foreach($steps as $s) {
            if ($s !== 'Not Required') {
                $active_steps++;
                if ($s === 'Approved') $approved++;
            }
        }
        
        $row['progress_val'] = ($active_steps > 0) ? round(($approved / $active_steps) * 100) : 0;
        $row['approved_count'] = $approved;
        $row['total_active_steps'] = $active_steps;

        // Clean Strings
        array_walk_recursive($row, function(&$item){
            if(is_string($item)) {
                $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                $item = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
            }
        });
    }

    echo json_encode($results);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}