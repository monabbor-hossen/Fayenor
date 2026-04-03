<?php
// portal/payroll.php
require_once '../includes/header.php';
require_once __DIR__ . '/../../app/Config/Database.php';

$db = (new Database())->getConnection();

$curr_month_name = date('F');
$curr_year = date('Y');
$curr_month_num = (int)date('n');

// --- 1. FETCH ALL STAFF/ADMINS WITH CURRENT MONTH PAYMENT DATA ---
$query = "SELECT u.id, u.username, u.full_name, u.job_title, u.basic_salary, u.joining_date, u.resigning_date, u.is_active,
          (SELECT MAX(payment_date) FROM payroll WHERE user_id = u.id) as last_payment_date,
          (SELECT COALESCE(SUM(amount), 0) FROM payroll WHERE user_id = u.id AND pay_month = :curr_month AND pay_year = :curr_year) as current_month_paid
          FROM users u 
          WHERE u.role IN ('1', '2') 
          ORDER BY u.is_active DESC, u.id ASC";

$stmt = $db->prepare($query);
$stmt->execute([':curr_month' => $curr_month_name, ':curr_year' => $curr_year]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 2. CALCULATE TOTAL MONTHLY LIABILITY ---
$total_monthly_liability = 0;
$active_employees = 0;

foreach ($users as $u) {
    $is_resigned = (!empty($u['resigning_date']) && strtotime($u['resigning_date']) <= time());
    if (!$is_resigned && $u['is_active'] == 1) {
        $total_monthly_liability += floatval($u['basic_salary']);
        $active_employees++;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-white fw-bold mb-0">Payroll Management</h3>
            <p class="text-white-50 small mb-0">Overview of staff salaries and payment status</p>
        </div>
        <a href="../users/" class="btn btn-outline-light btn-sm rounded-pill px-4">
            <i class="bi bi-people me-2"></i> Manage Users
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="glass-panel p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 text-uppercase small fw-bold mb-1">Active Employees</h6>
                    <h2 class="text-white fw-bold mb-0"><?php echo $active_employees; ?></h2>
                </div>
                <div class="icon-box bg-rooq-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="glass-panel p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 text-uppercase small fw-bold mb-1">Est. Monthly Liability</h6>
                    <h2 class="text-secondary fw-bold mb-0"><?php echo number_format($total_monthly_liability, 2); ?> <small class="fs-6 text-white-50">SAR</small></h2>
                </div>
                <div class="icon-box bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card-box p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle" style="background: transparent;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.05);">
                        <th class="py-3 ps-4 text-secondary text-uppercase small">Employee</th>
                        <th class="py-3 text-secondary text-uppercase small">Basic Salary</th>
                        <th class="py-3 text-secondary text-uppercase small">Last Payment</th>
                        <th class="py-3 text-center text-secondary text-uppercase small">Status (<?php echo date('M Y'); ?>)</th>
                        <th class="py-3 text-end pe-4 text-secondary text-uppercase small">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): 
                            // --- CHECK EMPLOYMENT DATES ---
                            $join_time = strtotime($user['joining_date'] ?: 'now');
                            $join_m = (int)date('n', $join_time);
                            $join_y = (int)date('Y', $join_time);
                            
                            $is_resigned = false;
                            $resign_m = 0; $resign_y = 0;
                            if (!empty($user['resigning_date'])) {
                                $resign_time = strtotime($user['resigning_date']);
                                $is_resigned = ($resign_time <= time());
                                $resign_m = (int)date('n', $resign_time);
                                $resign_y = (int)date('Y', $resign_time);
                            }

                            $row_style = $is_resigned ? 'opacity: 0.5; filter: grayscale(100%);' : '';

                            // --- CALCULATE THIS MONTH'S STATUS ---
                            $status_html = '';
                            $balance = floatval($user['basic_salary']) - floatval($user['current_month_paid']);

                            // Check if they haven't joined yet
                            if (($curr_year < $join_y) || ($curr_year == $join_y && $curr_month_num < $join_m)) {
                                $status_html = '<span class="badge bg-secondary opacity-50">Not Joined Yet</span>';
                            } 
                            // Check if they resigned before this month
                            elseif ($is_resigned && (($curr_year > $resign_y) || ($curr_year == $resign_y && $curr_month_num > $resign_m))) {
                                $status_html = '<span class="badge bg-danger opacity-75">Resigned</span>';
                            }
                            // Otherwise calculate pay
                            else {
                                if ($balance > 0) {
                                    $status_html = '<div class="text-danger fw-bold small"><i class="bi bi-exclamation-circle me-1"></i>Due: ' . number_format($balance, 2) . ' SAR</div>';
                                } elseif ($balance < 0) {
                                    $status_html = '<div class="text-info fw-bold small"><i class="bi bi-arrow-up-circle me-1"></i>Extra: +' . number_format(abs($balance), 2) . ' SAR</div>';
                                } else {
                                    $status_html = '<div class="text-success fw-bold small"><i class="bi bi-check-circle-fill me-1"></i>Fully Paid</div>';
                                }
                            }
                        ?>
                        <tr style="<?php echo $row_style; ?>">
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-small me-3" style="width: 35px; height: 35px; font-size: 0.9rem; background: rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:center; border-radius:50%; color: #fff;">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-white"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        <div class="small text-white-50"><?php echo htmlspecialchars($user['job_title'] ?? 'Staff'); ?></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <span class="fw-bold text-white"><?php echo number_format($user['basic_salary'], 2); ?> <small class="text-white-50 fw-normal">SAR</small></span>
                            </td>

                            <td>
                                <?php if ($user['last_payment_date']): ?>
                                    <div class="text-white small"><i class="bi bi-calendar-check me-1 text-secondary"></i> <?php echo date('M d, Y', strtotime($user['last_payment_date'])); ?></div>
                                <?php else: ?>
                                    <span class="text-white-50 small fst-italic">Never Paid</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <?php echo $status_html; ?>
                            </td>

                            <td class="text-end pe-4">
                                <a href="user-payroll?id=<?php echo $user['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success opacity-75 hover-opacity-100 rounded-pill px-3 shadow-sm">
                                    <i class="bi bi-wallet2 me-1"></i> Manage
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-white-50">No employees found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>

<?php require_once '../includes/footer.php'; ?>