<?php
// portal/dashboard.php

// 1. Load header (which automatically handles Admin/Client security checks)
require_once 'includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

$db = (new Database())->getConnection();

// Initialize variables safely to prevent crashes
$total_clients = 0;
$total_revenue = 0;
$total_paid = 0;
$total_expenses = 0;
$all_workflows = [];
$recent_payments = [];
$recent_expenses = [];

try {
    // ========================================================================
    // 1. CALCULATE TOP KPI METRICS
    // ========================================================================
    $stmtClients = $db->query("SELECT COUNT(*) FROM clients WHERE is_active = 1");
    if ($stmtClients) $total_clients = $stmtClients->fetchColumn() ?: 0;

    $stmtRev = $db->query("SELECT COALESCE(SUM(contract_value), 0) FROM clients WHERE is_active = 1");
    if ($stmtRev) $total_revenue = floatval($stmtRev->fetchColumn());

    $stmtPaid = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'Completed'");
    if ($stmtPaid) $total_paid = floatval($stmtPaid->fetchColumn());

    $stmtExp = $db->query("
        SELECT COALESCE(SUM(e.amount), 0) as total 
        FROM expenses e 
        LEFT JOIN users u ON e.created_by = u.id 
        WHERE u.role != 'client'
    ");
    if ($stmtExp) $total_expenses = floatval($stmtExp->fetchColumn());

    // ========================================================================
    // 2. FETCH ACTIONABLE WORKFLOWS
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

    $stmtRecentExp = $db->query("
        SELECT e.title, e.amount, e.expense_date, e.category 
        FROM expenses e
        LEFT JOIN users u ON e.created_by = u.id 
        WHERE u.role != 'client'
        ORDER BY e.expense_date DESC LIMIT 5
    ");
    if ($stmtRecentExp) $recent_expenses = $stmtRecentExp->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard DB Error: " . $e->getMessage());
}

// Financial Calculations
$total_due = max(0, $total_revenue - $total_paid);
$collection_rate = ($total_revenue > 0) ? round(($total_paid / $total_revenue) * 100) : 0;

// Workflow Processing
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

<style>
    /* Minimalist Dashboard Specific Styles */
    .kpi-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .kpi-card:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(212, 175, 55, 0.2);
    }
    .kpi-icon-bg {
        position: absolute;
        bottom: -15px;
        right: -10px;
        font-size: 6rem;
        opacity: 0.03;
        z-index: 0;
        transform: rotate(-10deg);
    }
    .kpi-content {
        position: relative;
        z-index: 1;
    }
    .clean-table th {
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.4);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 15px;
    }
    .clean-table td {
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        padding: 16px 0;
        vertical-align: middle;
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
</style>

<div class="d-flex justify-content-between align-items-end mb-4 pb-2 border-bottom border-light border-opacity-10">
    <div>
        <h3 class="text-white fw-light mb-1" style="letter-spacing: -0.5px;">Overview</h3>
        <p class="text-white-50 small mb-0">System health and financial summary.</p>
    </div>
    <div class="text-end d-none d-md-block">
        <p class="text-white-50 small mb-0 text-uppercase" style="letter-spacing: 1px;">Date</p>
        <div class="text-gold fw-bold fs-6"><?php echo date('F d, Y'); ?></div>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-4 mb-5">
    
    <div class="col">
        <div class="kpi-card h-100">
            <i class="bi bi-buildings kpi-icon-bg"></i>
            <div class="kpi-content d-flex flex-column h-100 justify-content-between">
                <div class="text-white-50 small text-uppercase mb-3" style="letter-spacing: 1px;">Active Clients</div>
                <div>
                    <div class="display-6 fw-light text-white mb-1"><?php echo $total_clients; ?></div>
                    <div class="small text-white-50">Total operating entities</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="kpi-card h-100 border-start border-3" style="border-left-color: rgba(255,255,255,0.2) !important;">
            <i class="bi bi-wallet2 kpi-icon-bg"></i>
            <div class="kpi-content d-flex flex-column h-100 justify-content-between">
                <div class="text-white-50 small text-uppercase mb-3" style="letter-spacing: 1px;">Expected Revenue</div>
                <div>
                    <div class="fs-3 fw-bold text-white mb-1">SAR <?php echo number_format($total_revenue, 2); ?></div>
                    <div class="small text-white-50">Total contract value</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col">
        <div class="kpi-card h-100 border-start border-3" style="border-left-color: #198754 !important;">
            <i class="bi bi-graph-up-arrow kpi-icon-bg"></i>
            <div class="kpi-content d-flex flex-column h-100 justify-content-between">
                <div class="text-white-50 small text-uppercase mb-3" style="letter-spacing: 1px;">Collected Funds</div>
                <div>
                    <div class="fs-3 fw-bold text-success mb-1">SAR <?php echo number_format($total_paid, 2); ?></div>
                    <div class="small text-success opacity-75">
                        <i class="bi bi-pie-chart-fill me-1"></i> <?php echo $collection_rate; ?>% Collection Rate
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="kpi-card h-100 border-start border-3" style="border-left-color: #dc3545 !important;">
            <i class="bi bi-hourglass-split kpi-icon-bg"></i>
            <div class="kpi-content d-flex flex-column h-100 justify-content-between">
                <div class="text-white-50 small text-uppercase mb-3" style="letter-spacing: 1px;">Outstanding Due</div>
                <div>
                    <div class="fs-3 fw-bold text-danger mb-1">SAR <?php echo number_format($total_due, 2); ?></div>
                    <div class="small text-white-50">Pending client invoices</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="kpi-card h-100 border-start border-3" style="border-left-color: #ffc107 !important;">
            <i class="bi bi-cash-coin kpi-icon-bg"></i>
            <div class="kpi-content d-flex flex-column h-100 justify-content-between">
                <div class="text-white-50 small text-uppercase mb-3" style="letter-spacing: 1px;">Internal Expenses</div>
                <div>
                    <div class="fs-3 fw-bold text-warning mb-1">SAR <?php echo number_format($total_expenses, 2); ?></div>
                    <div class="small text-white-50">Total operational outgoings</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-5">
    <div class="col-xl-8">
        <h6 class="text-gold fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;">Pending Workflows</h6>
        
        <div class="glass-panel p-4 pb-2 overflow-hidden">
            <div class="table-responsive">
                <table class="table clean-table table-borderless text-white mb-0">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Client Entity</th>
                            <th style="width: 45%;">Pending Milestones</th>
                            <th class="text-end" style="width: 20%;">Items</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_clients)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-white-50">
                                    <i class="bi bi-check-all fs-2 d-block mb-2 text-success opacity-50"></i>
                                    All operational workflows are currently up to date.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending_clients as $pc): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-white"><?php echo htmlspecialchars($pc['company_name']); ?></div>
                                        <div class="text-white-50 small" style="font-size: 0.7rem;">ID: #<?php echo $pc['client_id']; ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($pc['pending_steps'] as $step): 
                                                $dotColor = ($step['status'] == 'In Process') ? 'bg-primary' : 'bg-warning';
                                                $textColor = ($step['status'] == 'In Process') ? 'text-white' : 'text-white-50';
                                            ?>
                                                <div class="small <?php echo $textColor; ?> d-flex align-items-center bg-dark bg-opacity-50 px-2 py-1 rounded">
                                                    <span class="status-dot <?php echo $dotColor; ?>"></span>
                                                    <?php echo htmlspecialchars($step['step']); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="text-end text-white-50 small fw-bold">
                                        <?php echo count($pc['pending_steps']); ?> Tasks
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
        <h6 class="text-gold fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;">Recent Ledger</h6>
        
        <div class="glass-panel p-4 mb-4">
            <h6 class="text-white-50 small text-uppercase mb-3 border-bottom border-light border-opacity-10 pb-2">Latest Incoming</h6>
            
            <?php if (count($recent_payments) > 0): ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($recent_payments as $pay): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-truncate pe-3" style="max-width: 60%;">
                                <div class="text-white small fw-bold text-truncate"><?php echo htmlspecialchars($pay['company_name']); ?></div>
                                <div class="text-white-50" style="font-size: 0.65rem;"><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></div>
                            </div>
                            <div class="text-end">
                                <div class="text-success small fw-bold">+SAR <?php echo number_format($pay['amount'], 2); ?></div>
                                <div class="text-white-50" style="font-size: 0.65rem;"><?php echo htmlspecialchars($pay['payment_status']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-white-50 small py-3">No recent incoming transactions.</div>
            <?php endif; ?>
        </div>

        <div class="glass-panel p-4">
            <h6 class="text-white-50 small text-uppercase mb-3 border-bottom border-light border-opacity-10 pb-2">Latest Outgoing</h6>
            
            <?php if (count($recent_expenses) > 0): ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($recent_expenses as $exp): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-truncate pe-3" style="max-width: 60%;">
                                <div class="text-white small fw-bold text-truncate"><?php echo htmlspecialchars($exp['title']); ?></div>
                                <div class="text-white-50" style="font-size: 0.65rem;"><?php echo date('M d, Y', strtotime($exp['expense_date'])); ?> &bull; <?php echo htmlspecialchars($exp['category']); ?></div>
                            </div>
                            <div class="text-end">
                                <div class="text-warning small fw-bold">-SAR <?php echo number_format($exp['amount'], 2); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-white-50 small py-3">No recent outgoing transactions.</div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>