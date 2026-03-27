<?php
// portal/audit-finance.php
require_once '../includes/header.php';
require_once __DIR__ . '/../../app/Config/Database.php';

// Only Admins should access Financial Audits
if ($_SESSION['role'] != '2') {
    echo "<div class='container p-5 text-center text-danger'><h4><i class='bi bi-shield-lock-fill me-2'></i> Access Denied. Admins only.</h4></div>";
    require_once '../includes/footer.php';
    exit();
}

$db = (new Database())->getConnection();

// --- FILTER LOGIC ---
$f_start = $_GET['f_start'] ?? date('Y-m-01'); 
$f_end   = $_GET['f_end'] ?? date('Y-m-d');
$f_user  = Security::clean($_GET['f_user'] ?? '');

// STRICTLY FILTER FOR FINANCIAL ACTIONS ONLY
$where_clauses = [
    "DATE(created_at) >= :start", 
    "DATE(created_at) <= :end",
    "(action LIKE '%payment%' OR action LIKE '%SAR%')" // Forces the DB to only pull money actions
];
$params = [':start' => $f_start, ':end' => $f_end];

if (!empty($f_user)) {
    $where_clauses[] = "username LIKE :user";
    $params[':user'] = "%{$f_user}%";
}

$where_sql = implode(" AND ", $where_clauses);

// --- FETCH FINANCIAL LOGS ---
$stmt = $db->prepare("SELECT * FROM activity_logs WHERE $where_sql ORDER BY created_at DESC");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- CALCULATE METRICS ---
$total_transactions = count($logs);
$client_inflow = 0;
$staff_outflow = 0;

foreach ($logs as $log) {
    $action_lower = strtolower($log['action']);
    if (strpos($action_lower, 'client') !== false) {
        $client_inflow++;
    } elseif (strpos($action_lower, 'salary') !== false || strpos($action_lower, 'payroll') !== false) {
        $staff_outflow++;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-white fw-bold mb-0"><i class="bi bi-bank text-gold me-2"></i>Financial Audit Trail</h3>
            <p class="text-white-50 small mb-0">Track and investigate all money movement (Client Invoices & Staff Payroll).</p>
        </div>
        
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="glass-panel p-3 border-bottom border-3 border-info text-center h-100">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Total Transactions</h6>
                <h3 class="text-white mb-0 fw-bold"><?php echo $total_transactions; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-panel p-3 border-bottom border-3 border-success text-center h-100">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Client Payments (Inflow)</h6>
                <h3 class="text-success mb-0 fw-bold"><?php echo $client_inflow; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-panel p-3 border-bottom border-3 border-warning text-center h-100">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Staff Payroll (Outflow)</h6>
                <h3 class="text-warning mb-0 fw-bold"><?php echo $staff_outflow; ?></h3>
            </div>
        </div>
    </div>

    <div class="card-box p-3 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-gold small fw-bold">Date From</label>
                <input type="text" name="f_start" class="form-control glass-input rooq-date" data-hide-buttons="true" value="<?php echo htmlspecialchars($f_start); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label text-gold small fw-bold">Date To</label>
                <input type="text" name="f_end" class="form-control glass-input rooq-date" data-hide-buttons="true" value="<?php echo htmlspecialchars($f_end); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label text-gold small fw-bold">Search User</label>
                <input type="text" name="f_user" class="form-control glass-input" placeholder="Who recorded it?" value="<?php echo htmlspecialchars($f_user); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-rooq-primary w-100"><i class="bi bi-search me-2"></i>Filter</button>
            </div>
        </form>
    </div>

    <div class="card-box p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle" style="background: transparent;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.05);">
                        <th class="py-3 ps-4 text-gold text-uppercase small">Date & Time</th>
                        <th class="py-3 text-gold text-uppercase small">Recorded By</th>
                        <th class="py-3 text-gold text-uppercase small">Transaction Details</th>
                        <th class="py-3 text-end pe-4 text-gold text-uppercase small">IP Trace</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): 
                            // Determine Color Based on Inflow/Outflow
                            $is_client = (strpos(strtolower($log['action']), 'client') !== false);
                            $action_class = $is_client ? 'text-success fw-bold' : 'text-warning fw-bold';
                            $icon = $is_client ? '<i class="bi bi-arrow-down-left-circle-fill me-2 text-success"></i>' : '<i class="bi bi-arrow-up-right-circle-fill me-2 text-warning"></i>';
                        ?>
                        <tr>
                            <td class="ps-4 text-white-50 small"><?php echo date('d M Y - h:i A', strtotime($log['created_at'])); ?></td>
                            <td>
                                <div class="fw-bold text-white"><?php echo htmlspecialchars($log['username']); ?></div>
                                <div class="small text-white-50"><?php echo strtoupper($log['user_type']); ?></div>
                            </td>
                            <td class="<?php echo $action_class; ?>"><?php echo $icon . htmlspecialchars($log['action']); ?></td>
                            <td class="text-end pe-4 text-white-50 small font-monospace"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-white-50">No financial records found for these dates.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>

<?php require_once '../includes/footer.php'; ?>