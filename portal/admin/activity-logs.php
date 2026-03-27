<?php
// portal/audit.php
require_once '../includes/header.php';
require_once __DIR__ . '/../../app/Config/Database.php';

// Only Admins should access the Audit System
if ($_SESSION['role'] != '2') {
    echo "<div class='container p-5 text-center text-danger'><h4><i class='bi bi-shield-lock-fill me-2'></i> Access Denied. Admins only.</h4></div>";
    require_once '../includes/footer.php';
    exit();
}

$db = (new Database())->getConnection();

// --- FILTER LOGIC ---
$f_start = $_GET['f_start'] ?? date('Y-m-01'); // Default to 1st of current month
$f_end   = $_GET['f_end'] ?? date('Y-m-d');
$f_user  = Security::clean($_GET['f_user'] ?? '');
$f_action = Security::clean($_GET['f_action'] ?? '');

$where_clauses = ["DATE(created_at) >= :start", "DATE(created_at) <= :end"];
$params = [':start' => $f_start, ':end' => $f_end];

if (!empty($f_user)) {
    $where_clauses[] = "username LIKE :user";
    $params[':user'] = "%{$f_user}%";
}
if (!empty($f_action)) {
    $where_clauses[] = "action LIKE :action";
    $params[':action'] = "%{$f_action}%";
}

$where_sql = implode(" AND ", $where_clauses);

// --- FETCH AUDIT LOGS ---
$stmt = $db->prepare("SELECT * FROM activity_logs WHERE $where_sql ORDER BY created_at DESC");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- CALCULATE METRICS ---
$total_events = count($logs);
$deletions = 0;
$logins = 0;
$financials = 0;

foreach ($logs as $log) {
    if (strpos(strtolower($log['action']), 'deleted') !== false || strpos(strtolower($log['action']), 'deactivated') !== false) $deletions++;
    if (strpos(strtolower($log['action']), 'logged in') !== false) $logins++;
    if (strpos(strtolower($log['action']), 'payment') !== false || strpos(strtolower($log['action']), 'sar') !== false) $financials++;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-white fw-bold mb-0"><i class="bi bi-shield-check text-gold me-2"></i>System Activity Logs</h3>
            <p class="text-white-50 small mb-0">Track, filter, and investigate all system activities securely.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="glass-panel p-3 border-bottom border-3 border-info text-center h-100">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Total Events Recorded</h6>
                <h3 class="text-white mb-0 fw-bold"><?php echo $total_events; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-panel p-3 border-bottom border-3 border-success text-center h-100">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Login Actions</h6>
                <h3 class="text-success mb-0 fw-bold"><?php echo $logins; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-panel p-3 border-bottom border-3 border-warning text-center h-100">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Financial Actions</h6>
                <h3 class="text-warning mb-0 fw-bold"><?php echo $financials; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-panel p-3 border-bottom border-3 border-danger text-center h-100">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Deletions / Warnings</h6>
                <h3 class="text-danger mb-0 fw-bold"><?php echo $deletions; ?></h3>
            </div>
        </div>
    </div>

    <div class="card-box p-3 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-gold small fw-bold">Date From</label>
                <input type="text" name="f_start" class="form-control glass-input rooq-date" data-hide-buttons="true" value="<?php echo htmlspecialchars($f_start); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label text-gold small fw-bold">Date To</label>
                <input type="text" name="f_end" class="form-control glass-input rooq-date" data-hide-buttons="true" value="<?php echo htmlspecialchars($f_end); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label text-gold small fw-bold">Search Username</label>
                <input type="text" name="f_user" class="form-control glass-input" placeholder="e.g. johndoe" value="<?php echo htmlspecialchars($f_user); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label text-gold small fw-bold">Action Type</label>
                <select name="f_action" class="form-select glass-input">
                    <option value="">All Actions</option>
                    <option value="Created" <?php echo ($f_action == 'Created') ? 'selected' : ''; ?>>Creations</option>
                    <option value="Updated" <?php echo ($f_action == 'Updated') ? 'selected' : ''; ?>>Updates</option>
                    <option value="Deleted" <?php echo ($f_action == 'Deleted') ? 'selected' : ''; ?>>Deletions</option>
                    <option value="Payment" <?php echo ($f_action == 'Payment') ? 'selected' : ''; ?>>Payments</option>
                    <option value="Logged In" <?php echo ($f_action == 'Logged In') ? 'selected' : ''; ?>>Logins</option>
                </select>
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
                        <th class="py-3 text-gold text-uppercase small">Target User</th>
                        <th class="py-3 text-gold text-uppercase small">Action Performed</th>
                        <th class="py-3 text-end pe-4 text-gold text-uppercase small">IP Trace</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): 
                            // Determine Icon and Color
                            $action_class = 'text-white';
                            $icon = '<i class="bi bi-record-circle me-2 text-white-50"></i>';
                            
                            if (strpos(strtolower($log['action']), 'logged in') !== false) {
                                $action_class = 'text-success fw-bold';
                                $icon = '<i class="bi bi-box-arrow-in-right me-2 text-success"></i>';
                            } elseif (strpos(strtolower($log['action']), 'created') !== false || strpos(strtolower($log['action']), 'activated') !== false) {
                                $action_class = 'text-info';
                                $icon = '<i class="bi bi-plus-circle me-2 text-info"></i>';
                            } elseif (strpos(strtolower($log['action']), 'deleted') !== false || strpos(strtolower($log['action']), 'deactivated') !== false) {
                                $action_class = 'text-danger fw-bold';
                                $icon = '<i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>';
                            } elseif (strpos(strtolower($log['action']), 'updated') !== false) {
                                $action_class = 'text-warning';
                                $icon = '<i class="bi bi-pencil-square me-2 text-warning"></i>';
                            } elseif (strpos(strtolower($log['action']), 'payment') !== false || strpos(strtolower($log['action']), 'sar') !== false) {
                                $action_class = 'text-success';
                                $icon = '<i class="bi bi-cash-stack me-2 text-success"></i>';
                            }
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
                        <tr><td colspan="4" class="text-center py-5 text-white-50">No records found for the selected filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>

<?php require_once '../includes/footer.php'; ?>