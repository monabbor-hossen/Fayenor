<?php
// portal/dashboard.php
require_once 'includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

// --- SECURITY: ENSURE ONLY ADMIN/STAFF ACCESS THIS ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'client') {
    header("Location: ../public/login.php");
    exit();
}

// ========================================================================
// 1. BULLETPROOF DATA FETCHING
// ========================================================================
// We set strict defaults so the page NEVER crashes even if a table is empty.
$total_clients = 0;
$total_revenue = 0.0;
$total_paid = 0.0;
$total_expenses = 0.0;
$all_workflows = [];
$recent_payments = [];
$recent_expenses = [];
$pending_clients = [];

try {
    $db = (new Database())->getConnection();

    // 1. Total Clients
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM clients WHERE is_active = 1");
        if ($stmt) $total_clients = intval($stmt->fetchColumn());
    } catch (Throwable $e) {}

    // 2. Expected Revenue
    try {
        $stmt = $db->query("SELECT SUM(contract_value) FROM clients WHERE is_active = 1");
        if ($stmt) $total_revenue = floatval($stmt->fetchColumn());
    } catch (Throwable $e) {}

    // 3. Total Collected
    try {
        $stmt = $db->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'Completed'");
        if ($stmt) $total_paid = floatval($stmt->fetchColumn());
    } catch (Throwable $e) {}

    // 4. Internal Expenses Only
    try {
        $stmt = $db->query("SELECT SUM(e.amount) FROM expenses e LEFT JOIN users u ON e.created_by = u.id WHERE u.role != 'client'");
        if ($stmt) $total_expenses = floatval($stmt->fetchColumn());
    } catch (Throwable $e) {}

    // 5. Workflows
    try {
        $stmt = $db->query("SELECT c.client_id, c.company_name, w.* FROM clients c LEFT JOIN workflow_tracking w ON c.client_id = w.client_id WHERE c.is_active = 1");
        if ($stmt) {
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($res)) $all_workflows = $res;
        }
    } catch (Throwable $e) {}

    // 6. Recent Payments
    try {
        $stmt = $db->query("SELECT p.amount, p.payment_date, p.payment_status, c.company_name FROM payments p JOIN clients c ON p.client_id = c.client_id ORDER BY p.payment_date DESC LIMIT 5");
        if ($stmt) {
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($res)) $recent_payments = $res;
        }
    } catch (Throwable $e) {}

    // 7. Recent Expenses
    try {
        $stmt = $db->query("SELECT e.title, e.amount, e.expense_date, e.category FROM expenses e LEFT JOIN users u ON e.created_by = u.id WHERE u.role != 'client' ORDER BY e.expense_date DESC LIMIT 5");
        if ($stmt) {
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($res)) $recent_expenses = $res;
        }
    } catch (Throwable $e) {}

} catch (Throwable $e) {
    // Master catch prevents complete fatal crashes
    error_log("Dashboard Master Error: " . $e->getMessage());
}

// ========================================================================
// 2. DATA PROCESSING & CALCULATIONS
// ========================================================================
$total_due = max(0, $total_revenue - $total_paid);
$collection_rate = ($total_revenue > 0) ? round(($total_paid / $total_revenue) * 100) : 0;

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
    if (!is_array($wf)) continue;
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
            'client_id' => $wf['client_id'] ?? '-',
            'company_name' => $wf['company_name'] ?? 'Unknown Entity',
            'pending_steps' => $pending_steps
        ];
    }
}
?>

<style>
    /* Elegant Minimalist Dashboard Styles */
    .kpi-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 16px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .kpi-card:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(212, 175, 55, 0.15);
    }
    .kpi-icon-bg {
        position: absolute;
        bottom: -15px;
        right: -10px;
        font-size: 6rem;
        opacity: 0.02;
        z-index: 0;
        transform: rotate(-10deg);
        color: #ffffff;
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
    .ledger-item {
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        padding-bottom: 12px;
        margin-bottom: 12px;
    }
    .ledger-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
</style>

<div class="d-flex justify-content-between align-items-end mb-4 pb-3 border-bottom border-light border-opacity-10">
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
                    <div class="fs-4 fw-bold text-white mb-1">SAR <?php echo number_format($total_revenue, 2); ?></div>
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
                    <div class="fs-4 fw-bold text-success mb-1">SAR <?php echo number_format($total_paid, 2); ?></div>
                    <div class="small text-success opacity-75">
                        <i class="bi bi-pie-chart-fill me-1"></i> <?php echo $collection_rate; ?>% Collection Rate
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="kpi-card h-100 border-start border-3" style="border-left-color: #dc3545 !important;">
            <i class="bi bi-hourglass-split kpi-icon-bg text-danger opacity-10"></i>
            <div class="kpi-content d-flex flex-column h-100 justify-content-between">
                <div class="text-white-50 small text-uppercase mb-3" style="letter-spacing: 1px;">Outstanding Due</div>
                <div>
                    <div class="fs-4 fw-bold text-danger mb-1">SAR <?php echo number_format($total_due, 2); ?></div>
                    <div class="small text-white-50">Pending client invoices</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="kpi-card h-100 border-start border-3" style="border-left-color: #ffc107 !important;">
            <i class="bi bi-cash-coin kpi-icon-bg text-warning opacity-10"></i>
            <div class="kpi-content d-flex flex-column h-100 justify-content-between">
                <div class="text-white-50 small text-uppercase mb-3" style="letter-spacing: 1px;">Internal Expenses</div>
                <div>
                    <div class="fs-4 fw-bold text-warning mb-1">SAR <?php echo number_format($total_expenses, 2); ?></div>
                    <div class="small text-white-50">Operational outgoings</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-5">
    <div class="col-xl-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="text-white-50 fw-bold mb-0 text-uppercase" style="letter-spacing: 1px;">
                <i class="bi bi-diagram-3 me-2 text-gold"></i>Pending Workflows
            </h6>
            <a href="clients.php" class="text-gold small text-decoration-none fw-bold hover-white" style="letter-spacing: 0.5px;">
                View All <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="glass-panel p-4 pb-2 overflow-hidden shadow-sm" style="border-color: rgba(255,255,255,0.05); background: rgba(0,0,0,0.15);">
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
                                        <div class="fw-bold text-white" style="font-size: 0.95rem;"><?php echo htmlspecialchars($pc['company_name']); ?></div>
                                        <div class="text-white-50" style="font-size: 0.7rem;">ID: #<?php echo htmlspecialchars($pc['client_id']); ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($pc['pending_steps'] as $step): 
                                                $dotColor = ($step['status'] == 'In Process') ? 'bg-primary' : 'bg-warning';
                                                $textColor = ($step['status'] == 'In Process') ? 'text-white' : 'text-white-50';
                                            ?>
                                                <div class="small <?php echo $textColor; ?> d-flex align-items-center bg-dark bg-opacity-50 px-2 py-1 rounded" style="font-size: 0.8rem;">
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
        <h6 class="text-white-50 fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;"><i class="bi bi-activity me-2 text-gold"></i>Recent Ledger</h6>
        
        <div class="glass-panel p-4 mb-4 shadow-sm" style="border-color: rgba(255,255,255,0.05); background: rgba(0,0,0,0.15);">
            <div class="d-flex justify-content-between align-items-center border-bottom border-light border-opacity-10 pb-2 mb-4">
                <h6 class="text-white-50 small text-uppercase mb-0" style="letter-spacing: 1px;">Latest Incoming</h6>
                <a href="audit-finance.php" class="text-gold small text-decoration-none hover-white" style="font-size: 0.75rem;">View All <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            
            <?php if (count($recent_payments) > 0): ?>
                <div class="d-flex flex-column">
                    <?php foreach ($recent_payments as $pay): ?>
                        <div class="d-flex justify-content-between align-items-center ledger-item">
                            <div class="text-truncate pe-3" style="max-width: 65%;">
                                <div class="text-white small fw-bold text-truncate"><?php echo htmlspecialchars($pay['company_name']); ?></div>
                                <div class="text-white-50" style="font-size: 0.7rem;"><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></div>
                            </div>
                            <div class="text-end">
                                <div class="text-success small fw-bold">+SAR <?php echo number_format($pay['amount'], 2); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-white-50 small py-3">No recent incoming transactions.</div>
            <?php endif; ?>
        </div>

        <div class="glass-panel p-4 shadow-sm" style="border-color: rgba(255,255,255,0.05); background: rgba(0,0,0,0.15);">
            <div class="d-flex justify-content-between align-items-center border-bottom border-light border-opacity-10 pb-2 mb-4">
                <h6 class="text-white-50 small text-uppercase mb-0" style="letter-spacing: 1px;">Latest Outgoing</h6>
                <a href="expenses.php" class="text-gold small text-decoration-none hover-white" style="font-size: 0.75rem;">View All <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            
            <?php if (count($recent_expenses) > 0): ?>
                <div class="d-flex flex-column">
                    <?php foreach ($recent_expenses as $exp): ?>
                        <div class="d-flex justify-content-between align-items-center ledger-item">
                            <div class="text-truncate pe-3" style="max-width: 65%;">
                                <div class="text-white small fw-bold text-truncate"><?php echo htmlspecialchars($exp['title']); ?></div>
                                <div class="text-white-50" style="font-size: 0.7rem;"><?php echo date('M d, Y', strtotime($exp['expense_date'])); ?> &bull; <?php echo htmlspecialchars($exp['category']); ?></div>
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