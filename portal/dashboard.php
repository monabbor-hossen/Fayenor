<?php
// portal/dashboard.php

// 1. Load header (which automatically handles Admin/Client security checks)
require_once 'includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

$db = (new Database())->getConnection();

// Initialize variables safely to prevent crashes if DB fails
$total_clients = 0;
$total_revenue = 0;
$total_paid = 0;
$total_expenses = 0;
$all_workflows = [];
$recent_payments = [];
$recent_expenses = [];

try {
    // ========================================================================
    // 1. CALCULATE TOP KPI METRICS (Safely using COALESCE)
    // ========================================================================
    $stmtClients = $db->query("SELECT COUNT(*) FROM clients WHERE is_active = 1");
    if ($stmtClients) $total_clients = $stmtClients->fetchColumn() ?: 0;

    $stmtRev = $db->query("SELECT COALESCE(SUM(contract_value), 0) FROM clients WHERE is_active = 1");
    if ($stmtRev) $total_revenue = $stmtRev->fetchColumn() ?: 0;

    $stmtPaid = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'Completed'");
    if ($stmtPaid) $total_paid = $stmtPaid->fetchColumn() ?: 0;

    // --- STRICT INTERNAL EXPENSES ONLY ---
    $stmtExp = $db->query("
        SELECT COALESCE(SUM(e.amount), 0) as total 
        FROM expenses e 
        LEFT JOIN users u ON e.created_by = u.id 
        WHERE u.role != 'client'
    ");
    if ($stmtExp) $total_expenses = $stmtExp->fetchColumn() ?: 0;


    // ========================================================================
    // 2. FETCH ACTIONABLE WORKFLOWS (LEFT JOIN for safety)
    // ========================================================================
    $stmtWF = $db->query("
        SELECT c.client_id, c.company_name, w.* FROM clients c 
        LEFT JOIN workflow_tracking w ON c.client_id = w.client_id 
        WHERE c.is_active = 1
    ");
    if ($stmtWF) $all_workflows = $stmtWF->fetchAll(PDO::FETCH_ASSOC);


    // ========================================================================
    // 3. FETCH RECENT FINANCIAL ACTIVITY
    // ========================================================================
    $stmtRecentPay = $db->query("
        SELECT p.*, c.company_name 
        FROM payments p 
        JOIN clients c ON p.client_id = c.client_id 
        ORDER BY p.payment_date DESC LIMIT 5
    ");
    if ($stmtRecentPay) $recent_payments = $stmtRecentPay->fetchAll(PDO::FETCH_ASSOC);

    // --- STRICT INTERNAL RECENT EXPENSES ONLY ---
    $stmtRecentExp = $db->query("
        SELECT e.title, e.amount, e.expense_date, e.category 
        FROM expenses e
        LEFT JOIN users u ON e.created_by = u.id 
        WHERE u.role != 'client'
        ORDER BY e.expense_date DESC LIMIT 5
    ");
    if ($stmtRecentExp) $recent_expenses = $stmtRecentExp->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If an error happens, we silently log it so the UI still loads beautifully
    error_log("Dashboard DB Error: " . $e->getMessage());
}

// Calculate due amount safely
$total_due = $total_revenue - $total_paid;

// Safely process workflow data
$pending_clients = [];
$workflow_columns = [
    'hire_foreign_company' => 'Foreign Hire',
    'misa_application' => 'MISA',
    'sbc_application' => 'SBC',
    'article_association' => 'AoA',
    'qiwa' => 'Qiwa',
    'muqeem' => 'Muqeem',
    'gosi' => 'GOSI',
    'chamber_commerce' => 'Chamber'
];

foreach ($all_workflows as $wf) {
    $pending_steps = [];
    foreach ($workflow_columns as $col => $label) {
        // Use isset() to prevent "Undefined array key" PHP crashes
        if (isset($wf[$col]) && in_array($wf[$col], ['Pending', 'In Process', 'Applied'])) {
            $pending_steps[] = [
                'step' => $label,
                'status' => $wf[$col]
            ];
        }
    }
    if (!empty($pending_steps)) {
        $pending_clients[] = [
            'client_id' => $wf['client_id'],
            'company_name' => $wf['company_name'],
            'pending_steps' => $pending_steps
        ];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-white fw-bold mb-0">Admin Command Center</h3>
        <p class="text-white-50 small mb-0">Company overview and actionable tasks.</p>
    </div>
    
    <div class="d-none d-lg-flex gap-2">
        <a href="client-add.php" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold"><i class="bi bi-person-plus-fill me-1"></i> New Client</a>
        <a href="expenses.php" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold"><i class="bi bi-receipt me-1"></i> Log Expense</a>
        <a href="default-contract.php" class="btn btn-sm btn-rooq-primary rounded-pill px-3 fw-bold"><i class="bi bi-file-earmark-text me-1"></i> Contracts</a>
    </div>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-5">
    
    <div class="col">
        <div class="glass-panel p-3 border-bottom border-3 border-info text-center h-100 d-flex flex-column justify-content-center" style="background: rgba(255,255,255,0.02); transition: transform 0.3s ease;">
            <div class="icon-box bg-info bg-opacity-25 text-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-people-fill fs-5"></i>
            </div>
            <h6 class="text-white-50 small text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Active Clients</h6>
            <h4 class="text-white mb-0 fw-bold"><?php echo $total_clients; ?></h4>
        </div>
    </div>

    <div class="col">
        <div class="glass-panel p-3 border-bottom border-3 border-secondary text-center h-100 d-flex flex-column justify-content-center" style="background: rgba(255,255,255,0.02); transition: transform 0.3s ease;">
            <div class="icon-box bg-secondary bg-opacity-25 text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-briefcase fs-5"></i>
            </div>
            <h6 class="text-white-50 small text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Total Expected</h6>
            <h4 class="text-white mb-0 fw-bold"><?php echo number_format($total_revenue, 2); ?></h4>
        </div>
    </div>
    
    <div class="col">
        <div class="glass-panel p-3 border-bottom border-3 border-success text-center h-100 d-flex flex-column justify-content-center" style="background: rgba(255,255,255,0.02); transition: transform 0.3s ease;">
            <div class="icon-box bg-success bg-opacity-25 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-cash-stack fs-5"></i>
            </div>
            <h6 class="text-white-50 small text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Total Collected</h6>
            <h4 class="text-success mb-0 fw-bold"><?php echo number_format($total_paid, 2); ?></h4>
        </div>
    </div>

    <div class="col">
        <div class="glass-panel p-3 border-bottom border-3 border-danger text-center h-100 d-flex flex-column justify-content-center" style="background: rgba(255,255,255,0.02); transition: transform 0.3s ease;">
            <div class="icon-box bg-danger bg-opacity-25 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-exclamation-circle fs-5"></i>
            </div>
            <h6 class="text-white-50 small text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Remaining Due</h6>
            <h4 class="text-danger mb-0 fw-bold"><?php echo number_format($total_due, 2); ?></h4>
        </div>
    </div>

    <div class="col">
        <div class="glass-panel p-3 border-bottom border-3 border-warning text-center h-100 d-flex flex-column justify-content-center" style="background: rgba(255,255,255,0.02); transition: transform 0.3s ease;">
            <div class="icon-box bg-warning bg-opacity-25 text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-wallet2 fs-5"></i>
            </div>
            <h6 class="text-white-50 small text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Company Expenses</h6>
            <h4 class="text-warning mb-0 fw-bold"><?php echo number_format($total_expenses, 2); ?></h4>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <h5 class="text-gold fw-bold mb-3"><i class="bi bi-exclamation-octagon me-2"></i>Action Required Workflows</h5>
        
        <div class="glass-panel p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover contract-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Client Name</th>
                            <th>Pending / Processing Steps</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_clients)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-white-50">
                                    <i class="bi bi-check2-circle fs-1 d-block mb-2 text-success opacity-50"></i>
                                    All active workflows are completed!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending_clients as $pc): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-white"><?php echo htmlspecialchars($pc['company_name']); ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($pc['pending_steps'] as $step): 
                                                $badgeClass = ($step['status'] == 'In Process') ? 'bg-primary' : 'bg-warning text-dark';
                                            ?>
                                                <span class="badge <?php echo $badgeClass; ?> border border-light border-opacity-10 shadow-sm" style="font-size: 0.7rem;">
                                                    <?php echo htmlspecialchars($step['step']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="client-edit.php?id=<?php echo $pc['client_id']; ?>" class="btn btn-sm btn-outline-light rounded-pill px-3" style="font-size: 0.8rem;">
                                            Manage <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <h5 class="text-gold fw-bold mb-3"><i class="bi bi-activity me-2"></i>Recent Financials</h5>
        
        <div class="glass-panel p-0 overflow-hidden mb-4">
            <div class="bg-dark bg-opacity-50 p-2 px-3 border-bottom border-light border-opacity-10 text-white-50 small fw-bold text-uppercase">
                Latest Payments In
            </div>
            <?php if (count($recent_payments) > 0): ?>
                <div class="list-group list-group-flush bg-transparent">
                    <?php foreach ($recent_payments as $pay): ?>
                        <div class="list-group-item bg-transparent border-bottom border-light border-opacity-10 py-2 px-3">
                            <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 text-success fw-bold">+<?php echo number_format($pay['amount'], 2); ?> <small>SAR</small></h6>
                                <small class="text-white-50" style="font-size: 0.7rem;"><?php echo date('M d', strtotime($pay['payment_date'])); ?></small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-gold text-truncate" style="max-width: 180px; font-size: 0.75rem;"><?php echo htmlspecialchars($pay['company_name']); ?></small>
                                <?php 
                                    $badge = 'bg-warning';
                                    if ($pay['payment_status'] == 'Completed') $badge = 'bg-success';
                                    elseif ($pay['payment_status'] == 'Failed') $badge = 'bg-danger';
                                ?>
                                <span class="badge <?php echo $badge; ?> rounded-pill" style="font-size: 0.6rem;"><?php echo htmlspecialchars($pay['payment_status']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="p-4 text-center">
                    <span class="text-white-50 small">No recent payments.</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-panel p-0 overflow-hidden">
            <div class="bg-dark bg-opacity-50 p-2 px-3 border-bottom border-light border-opacity-10 text-white-50 small fw-bold text-uppercase">
                Latest Expenses Out
            </div>
            <?php if (count($recent_expenses) > 0): ?>
                <div class="list-group list-group-flush bg-transparent">
                    <?php foreach ($recent_expenses as $exp): ?>
                        <div class="list-group-item bg-transparent border-bottom border-light border-opacity-10 py-2 px-3">
                            <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 text-danger fw-bold">-<?php echo number_format($exp['amount'], 2); ?> <small>SAR</small></h6>
                                <small class="text-white-50" style="font-size: 0.7rem;"><?php echo date('M d', strtotime($exp['expense_date'])); ?></small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-white text-truncate" style="max-width: 150px; font-size: 0.75rem;"><?php echo htmlspecialchars($exp['title']); ?></small>
                                <span class="badge bg-secondary rounded-pill" style="font-size: 0.6rem;"><?php echo htmlspecialchars($exp['category']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="p-4 text-center">
                    <span class="text-white-50 small">No recent expenses.</span>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>