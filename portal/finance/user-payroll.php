<?php
// portal/user-payroll.php
require_once '../includes/header.php';
require_once __DIR__ . '/../../app/Config/Database.php';

$message = "";
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header("Location: ../users/users");
    exit();
}

$db = (new Database())->getConnection();

// --- 1. FETCH USER INFO FIRST (So we know their joining date) ---
$stmt = $db->prepare("SELECT full_name, username, job_title, basic_salary, joining_date, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='alert alert-danger m-4'>User not found.</div>";
    exit();
}

$display_name = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
$base_salary = floatval($user['basic_salary']);

// Determine exact joining date (Fallback to created_at if joining_date is null)
$exact_join_date = !empty($user['joining_date']) ? $user['joining_date'] : date('Y-m-d', strtotime($user['created_at']));
$joined_timestamp = strtotime($exact_join_date);
$joined_month_num = (int)date('n', $joined_timestamp);
$joined_year = (int)date('Y', $joined_timestamp);

// --- 2. HANDLE ADDING A NEW PAYMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    Security::checkCSRF($_POST['csrf_token']);
    
    $month = Security::clean($_POST['pay_month']);
    $year = intval($_POST['pay_year']);
    $amount = floatval($_POST['amount']);
    $method = Security::clean($_POST['payment_method']);
    $date = Security::clean($_POST['payment_date']);
    $notes = Security::clean($_POST['notes']);

    // BACKEND SECURITY CHECK: Ensure payment date is not before joining date
    if (strtotime($date) < $joined_timestamp) {
        $message = "<div class='alert alert-danger bg-danger bg-opacity-25 text-white border-danger'>Error: Payment date cannot be before the user's joining date (" . date('M d, Y', $joined_timestamp) . ").</div>";
    } else {
        try {
            $sql = "INSERT INTO payroll (user_id, pay_month, pay_year, amount, payment_method, payment_date, notes) 
                    VALUES (:uid, :month, :year, :amount, :method, :date, :notes)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':uid' => $user_id, ':month' => $month, ':year' => $year, 
                ':amount' => $amount, ':method' => $method, ':date' => $date, ':notes' => $notes
            ]);
            Security::logActivity("Recorded salary payment of " . $amount . " SAR for " . $display_name);
            $message = "<div class='alert alert-success bg-success bg-opacity-25 text-white border-success'>Salary payment recorded successfully!</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// --- 3. SMART PERIOD FILTER LOGIC ---
$f_period = $_GET['f_period'] ?? '';
$f_month = '';
$f_year = '';

$where_clauses = ["user_id = ?"];
$params = [$user_id];

if (!empty($f_period)) {
    if (strlen($f_period) == 10) {
        $where_clauses[] = "payment_date = ?";
        $params[] = $f_period;
        $f_month = date('F', strtotime($f_period));
        $f_year = date('Y', strtotime($f_period));
    } elseif (strlen($f_period) == 7) {
        $f_year = substr($f_period, 0, 4);
        $month_num = substr($f_period, 5, 2);
        $f_month = date('F', mktime(0, 0, 0, $month_num, 10));
        
        $where_clauses[] = "pay_year = ?";
        $params[] = $f_year;
        $where_clauses[] = "pay_month = ?";
        $params[] = $f_month;
    } elseif (strlen($f_period) == 4) {
        $f_year = $f_period;
        $where_clauses[] = "pay_year = ?";
        $params[] = $f_year;
    }
} else {
    $f_month = date('F');
    $f_year = date('Y');
    $where_clauses[] = "pay_year = ?";
    $params[] = $f_year;
    $where_clauses[] = "pay_month = ?";
    $params[] = $f_month;
}

$where_sql = implode(" AND ", $where_clauses);

// --- FETCH PAYMENT HISTORY WITH FILTERS ---
$stmt = $db->prepare("SELECT * FROM payroll WHERE $where_sql ORDER BY pay_year DESC, payment_date DESC");
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_paid_filtered = 0;
foreach ($payments as $pay) {
    $total_paid_filtered += floatval($pay['amount']);
}

// --- CHECK IF FILTER IS BEFORE JOIN DATE ---
$is_pre_join = false;
if (!empty($f_month) && !empty($f_year)) {
    $filtered_month_num = date('n', strtotime($f_month));
    if ($f_year < $joined_year || ($f_year == $joined_year && $filtered_month_num < $joined_month_num)) {
        $is_pre_join = true;
    }
}

// --- ADVANCED DUE CALCULATION ---
$due_amount = null;
$due_label = "Select Month & Year to see Due";
$due_color = "text-white";
$due_border = "#f1c40f";
$modal_default_amount = $base_salary;

if ($is_pre_join) {
    $due_label = "Status ($f_month $f_year)";
    $due_amount = "Not Joined Yet";
    $due_color = "text-white-50";
    $due_border = "#95a5a6";
    $modal_default_amount = 0;
} elseif (!empty($f_month) && !empty($f_year)) {
    $balance = $base_salary - $total_paid_filtered;
    
    if ($balance > 0) {
        $due_label = "Remaining Due ($f_month)";
        $due_amount = number_format($balance, 2) . " SAR";
        $due_color = "text-danger";
        $due_border = "#e74c3c";
        $modal_default_amount = $balance;
    } elseif ($balance < 0) {
        $due_label = "Extra Paid ($f_month)";
        $due_amount = "+ " . number_format(abs($balance), 2) . " SAR";
        $due_color = "text-info";
        $due_border = "#3498db";
        $modal_default_amount = 0;
    } else {
        $due_label = "Status ($f_month)";
        $due_amount = "Fully Paid";
        $due_color = "text-success";
        $due_border = "#2ecc71";
        $modal_default_amount = 0;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="javascript:void(0);" onclick="history.length > 1 ? history.back() : window.location.href='../users/';" class="text-white-50 text-decoration-none mb-2 d-inline-block hover-white">
                <i class="bi bi-arrow-left me-2"></i> Back to Users
            </a>
            <h3 class="text-white fw-bold mb-0">Payroll: <?php echo htmlspecialchars($display_name); ?></h3>
            <p class="text-secondary small mb-0"><?php echo htmlspecialchars($user['job_title'] ?? 'Staff'); ?></p>
        </div>
        <button class="btn btn-rooq-primary rounded-pill px-4 shadow-lg" data-bs-toggle="modal" data-bs-target="#payModal">
            <i class="bi bi-wallet2 me-2"></i> Record Payment
        </button>
    </div>

    <?php echo $message; ?>

    <?php if ($is_pre_join): ?>
        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning text-warning d-flex align-items-center mb-4">
            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
            <div>
                <strong>Notice:</strong> This user joined the company on <strong><?php echo date('F d, Y', $joined_timestamp); ?></strong>. The selected filter is before their joining date.
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4" id="summary-cards-container" style="transition: opacity 0.3s ease;">
        <div class="col-md-4">
            <div class="glass-panel p-3 text-center h-100" style="border-bottom: 3px solid #3498db;">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Basic Salary (Monthly)</h6>
                <h3 class="text-white mb-0 fw-bold"><?php echo number_format($base_salary, 2); ?> <small class="fs-6 text-white-50">SAR</small></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-panel p-3 text-center h-100" style="border-bottom: 3px solid #2ecc71;">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2">Total Paid (Filtered)</h6>
                <h3 class="text-success mb-0 fw-bold"><?php echo number_format($total_paid_filtered, 2); ?> <small class="fs-6 text-white-50">SAR</small></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-panel p-3 text-center h-100" style="border-bottom: 3px solid <?php echo $due_border; ?>;">
                <h6 class="text-white-50 small text-uppercase fw-bold mb-2"><?php echo $due_label; ?></h6>
                <?php if ($due_amount !== null): ?>
                    <h3 class="mb-0 fw-bold <?php echo $due_color; ?>">
                        <?php echo $due_amount; ?>
                    </h3>
                <?php else: ?>
                    <h5 class="text-white-50 mt-2 fst-italic">- N/A -</h5>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 mt-4">
        <h6 class="text-secondary m-0 text-uppercase fw-bold mb-3 mb-md-0" style="font-size: 0.85rem; letter-spacing: 1px;">
            <i class="bi bi-clock-history me-2"></i>Payment History
        </h6>
        
        <form method="GET" id="payrollFilterForm" style="width: 100%; max-width: 380px;">
            <input type="hidden" name="id" value="<?php echo $user_id; ?>">
            <div class="input-group align-items-center" style="background: var(--glass-10); border: 1px solid var(--glass-20); border-radius: 50px; overflow: hidden; transition: all 0.3s ease;">
                <span class="input-group-text bg-transparent border-0 text-secondary ps-3 pe-2"><i class="bi bi-calendar2-range"></i></span>
                <input type="text" name="f_period" id="f_period" class="form-control bg-transparent border-0 text-white shadow-none rooq-date auto-filter py-2 px-1" 
                       value="<?php echo htmlspecialchars($f_period); ?>" placeholder="Filter by date, month, or year..." style="font-size: 0.85rem;">
                <button type="button" onclick="clearPayrollFilters(this.form)" class="btn bg-transparent border-0 text-white-50 pe-3 shadow-none" 
                        onmouseover="this.classList.replace('text-white-50', 'text-danger')" 
                        onmouseout="this.classList.replace('text-danger', 'text-white-50')" title="Clear Filter">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="card-box p-0 overflow-hidden" id="payroll-table-container" style="transition: opacity 0.3s ease;">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle" style="background: transparent;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.05);">
                        <th class="py-3 ps-4 text-secondary text-uppercase small">Month / Year</th>
                        <th class="py-3 text-secondary text-uppercase small">Payment Date</th>
                        <th class="py-3 text-secondary text-uppercase small">Method</th>
                        <th class="py-3 text-secondary text-uppercase small">Amount Paid</th>
                        <th class="py-3 text-secondary text-uppercase small">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-white"><?php echo htmlspecialchars($pay['pay_month']) . ' ' . $pay['pay_year']; ?></td>
                            <td class="text-white-50"><i class="bi bi-calendar me-2"></i><?php echo date('d M, Y', strtotime($pay['payment_date'])); ?></td>
                            <td><span class="badge bg-secondary opacity-75"><?php echo htmlspecialchars($pay['payment_method']); ?></span></td>
                            <td class="text-success fw-bold">+<?php echo number_format($pay['amount'], 2); ?> SAR</td>
                            <td class="text-white-50 small fst-italic"><?php echo htmlspecialchars($pay['notes']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-white-50">No salary payments match your filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>

<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-white fw-bold"><i class="bi bi-wallet2 me-2 text-secondary"></i>Record Salary Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST" onsubmit="return validatePaymentDate();">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">
                    <input type="hidden" name="add_payment" value="1">
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label text-white-50 small">Salary Month</label>
                            <select name="pay_month" class="form-select glass-input" required>
                                <?php 
                                $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                                foreach($months as $m) {
                                    $sel = ($m == ($f_month ?: date('F'))) ? 'selected' : '';
                                    echo "<option value='$m' $sel>$m</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-white-50 small">Year</label>
                            <input type="number" name="pay_year" class="form-control glass-input" value="<?php echo htmlspecialchars($f_year ?: date('Y')); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold">Amount to Pay (SAR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control glass-input fw-bold text-success" 
                                   value="<?php echo $modal_default_amount; ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-white-50 small">Payment Date</label>
                            <input type="text" name="payment_date" id="modalPaymentDate" class="form-control glass-input rooq-date" 
                                   value="<?php echo date('Y-m-d'); ?>" data-hide-buttons="true" required placeholder="Select Date...">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-white-50 small">Transfer Method</label>
                            <select name="payment_method" class="form-select glass-input">
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Notes / Deductions</label>
                            <textarea name="notes" class="form-control glass-input" rows="2" placeholder="Any deductions or bonuses?"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-rooq-primary px-4">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- <script>
function validatePaymentDate() {
    var paymentDate = document.getElementById('modalPaymentDate').value;
    var joinDate = '<php echo $exact_join_date; ?>';
    
    if (new Date(paymentDate) < new Date(joinDate)) {
        alert("Payment Date cannot be before the user's Joining Date (" + joinDate + ").");
        return false; // Stops form from submitting
    }
    return true; // Allows form submission
}
</script> -->

<?php require_once '../includes/footer.php'; ?>