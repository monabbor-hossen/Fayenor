<?php
// management/billing.php
require_once '../portal/includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

// --- 1. SECURITY: STRICT ACCESS CONTROL ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../public/login.php");
    exit();
}

$db = (new Database())->getConnection();
$account_id = intval($_SESSION['account_id'] ?? $_SESSION['user_id']); 

// --- 2. SECURITY: STRICT INPUT VALIDATION & SANITIZATION ---
// Validate client_id filter
$raw_filter = $_GET['client_id'] ?? 'all';
$filter_client_id = ($raw_filter === 'all') ? 'all' : intval($raw_filter);

// Helper function to strictly validate dates
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

$raw_start = $_GET['start_date'] ?? '';
$raw_end = $_GET['end_date'] ?? '';

$start_date = isValidDate($raw_start) ? $raw_start : '';
$end_date = isValidDate($raw_end) ? $raw_end : '';

try {
    // --- 3. FETCH ALL ACTIVE PROJECTS FOR DROPDOWN ---
    $stmtApps = $db->prepare("SELECT client_id, company_name, contract_value FROM clients WHERE account_id = ? AND is_active = 1 ORDER BY company_name ASC");
    $stmtApps->execute([$account_id]);
    $apps = $stmtApps->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. CALCULATE FILTERED CONTRACT SUMMARY (Optimized Math) ---
    $total_contract = 0.00;
    $filtered_apps_count = 0;

    foreach ($apps as $app) {
        if ($filter_client_id === 'all' || $filter_client_id === intval($app['client_id'])) {
            $total_contract += floatval($app['contract_value']);
            $filtered_apps_count++;
        }
    }

    // --- 5. FETCH PAYMENT HISTORY (WITH DATE & PROJECT FILTERS) ---
    $pay_query = "SELECT p.*, c.company_name, c.client_id 
                  FROM payments p 
                  JOIN clients c ON p.client_id = c.client_id 
                  WHERE c.account_id = ?";
    $pay_params = [$account_id];

    if ($filter_client_id !== 'all') {
        $pay_query .= " AND p.client_id = ?";
        $pay_params[] = $filter_client_id;
    }
    if ($start_date !== '') {
        $pay_query .= " AND p.payment_date >= ?";
        $pay_params[] = $start_date;
    }
    if ($end_date !== '') {
        $pay_query .= " AND p.payment_date <= ?";
        $pay_params[] = $end_date;
    }
    $pay_query .= " ORDER BY p.payment_date DESC";

    $stmtPay = $db->prepare($pay_query);
    $stmtPay->execute($pay_params);
    $payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

    // --- 6. CALCULATE PAID & DUE ---
    $total_paid = 0.00;
    foreach ($payments as $pay) {
        if ($pay['payment_status'] === 'Completed') {
            $total_paid += floatval($pay['amount']);
        }
    }
    $total_due = max(0, $total_contract - $total_paid); // max() prevents negative due amounts

    // --- 7. FETCH TOTAL & INDIVIDUAL EXPENSES (WITH DATE & PROJECT FILTER) ---
    $exp_query = "SELECT e.*, c.company_name 
                  FROM expenses e 
                  LEFT JOIN clients c ON e.client_id = c.client_id 
                  WHERE e.created_by = ?";
    $exp_params = [intval($_SESSION['user_id'])];

    if ($filter_client_id !== 'all') {
        $exp_query .= " AND e.client_id = ?";
        $exp_params[] = $filter_client_id;
    }
    if ($start_date !== '') {
        $exp_query .= " AND e.expense_date >= ?";
        $exp_params[] = $start_date;
    }
    if ($end_date !== '') {
        $exp_query .= " AND e.expense_date <= ?";
        $exp_params[] = $end_date;
    }
    $exp_query .= " ORDER BY e.expense_date DESC";

    $stmtExp = $db->prepare($exp_query);
    $stmtExp->execute($exp_params);
    $expense_records = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

    // Calculate the total from the fetched records
    $total_expenses = 0.00;
    foreach ($expense_records as $exp) {
        $total_expenses += floatval($exp['amount']);
    }

} catch (PDOException $e) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { document.querySelector('.global-loader').classList.add('hidden'); });</script>";
    // Security: In a real production environment, you might want to hide the exact $e->getMessage() from the client to prevent exposing table names.
    die("<div class='container mt-5'><div class='alert alert-danger p-4 rounded-4 shadow'><b>Database Error:</b> An error occurred while fetching your financial records. Please contact support.</div></div>");
}
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="dashboard.php" class="text-white-50 text-decoration-none hover-white small fw-bold">
            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-white fw-bold mb-1"><i class="bi bi-receipt text-gold me-3"></i>Billing & Financials</h2>
            <p class="text-white-50 small mb-0">Overview of all your project financials, payments, and expenses.</p>
        </div>
        <button class="btn btn-outline-light btn-sm rounded-pill px-4 d-none d-md-block" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Print Statement
        </button>
    </div>

    <div class="glass-panel p-3 mb-4">
        <h6 class="text-gold fw-bold mb-3"><i class="bi bi-funnel-fill me-2"></i>Advanced Filters</h6>
        <form method="GET" action="billing.php" class="m-0 row g-3">
            
            <div class="col-md-4">
                <label class="text-white-50 small mb-1">Project / Company</label>
                <select name="client_id" class="form-select glass-input rounded-3 shadow-sm py-2 px-3" style="cursor: pointer;">
                    <option value="all" <?php echo ($filter_client_id === 'all') ? 'selected' : ''; ?>>All Companies & Projects</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?php echo $app['client_id']; ?>" <?php echo ($filter_client_id == $app['client_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($app['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
<div class="col-md-3">
                <label class="text-white-50 small mb-1">Start Date</label>
                <div class="position-relative">
                    <input type="text" name="start_date" class="form-control glass-input rounded-3 shadow-sm py-2 px-3 rooq-date" 
                           placeholder="YYYY-MM-DD" 
                           value="<?php echo htmlspecialchars($start_date); ?>" 
                           readonly style="cursor: pointer;">
                    <i class="bi bi-calendar-date position-absolute text-gold" style="right: 15px; top: 10px; pointer-events: none;"></i>
                </div>
            </div>

            <div class="col-md-3">
                <label class="text-white-50 small mb-1">End Date</label>
                <div class="position-relative">
                    <input type="text" name="end_date" class="form-control glass-input rounded-3 shadow-sm py-2 px-3 rooq-date" 
                           placeholder="YYYY-MM-DD" 
                           value="<?php echo htmlspecialchars($end_date); ?>" 
                           readonly style="cursor: pointer;">
                    <i class="bi bi-calendar-date position-absolute text-gold" style="right: 15px; top: 10px; pointer-events: none;"></i>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-rooq-primary w-100 rounded-3 shadow-sm py-2">
                    <i class="bi bi-search me-1"></i> Apply Filter
                </button>
            </div>
            
            <?php if ($filter_client_id !== 'all' || !empty($start_date) || !empty($end_date)): ?>
                <div class="col-12 mt-2 text-end">
                    <a href="billing.php" class="text-danger small text-decoration-none hover-white"><i class="bi bi-x-circle me-1"></i>Clear Filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="glass-panel p-4 border-bottom border-3 border-secondary text-center h-100" style="background: rgba(255,255,255,0.02);">
                <div class="icon-box bg-secondary bg-opacity-25 text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-briefcase fs-4"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1">Contract Value</h6>
                <h4 class="text-white mb-0 fw-bold"><?php echo number_format($total_contract, 2); ?></h4>
                <div class="small text-white-50 mt-2"><?php echo $filtered_apps_count; ?> active project(s)</div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="glass-panel p-4 border-bottom border-3 border-success text-center h-100" style="background: rgba(255,255,255,0.02);">
                <div class="icon-box bg-success bg-opacity-25 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1">Total Paid</h6>
                <h4 class="text-success mb-0 fw-bold"><?php echo number_format($total_paid, 2); ?></h4>
                <div class="small text-white-50 mt-2">Completed transactions</div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="glass-panel p-4 border-bottom border-3 border-danger text-center h-100" style="background: rgba(255,255,255,0.02);">
                <div class="icon-box bg-danger bg-opacity-25 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-exclamation-circle fs-4"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1">Remaining Balance</h6>
                <h4 class="text-danger mb-0 fw-bold"><?php echo number_format($total_due, 2); ?></h4>
                <div class="small text-white-50 mt-2">Amount currently due</div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="glass-panel p-4 border-bottom border-3 border-warning text-center h-100" style="background: rgba(255,255,255,0.02);">
                <div class="icon-box bg-warning bg-opacity-25 text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1">Total Expenses</h6>
                <h4 class="text-warning mb-0 fw-bold"><?php echo number_format($total_expenses, 2); ?></h4>
                <div class="small text-white-50 mt-2">In selected date range</div>
            </div>
        </div>
    </div>

    <div class="card-box p-0 overflow-hidden">
        <div class="p-4 border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center bg-dark bg-opacity-50">
            <h5 class="text-gold fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Filtered Transactions</h5>
        </div>
        
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle" style="background: transparent;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.05);">
                        <th class="py-3 ps-4 text-gold text-uppercase small">Date</th>
                        <th class="py-3 text-gold text-uppercase small">Application / Project</th>
                        <th class="py-3 text-gold text-uppercase small">Amount (SAR)</th>
                        <th class="py-3 text-gold text-uppercase small">Method</th>
                        <th class="py-3 text-center text-gold text-uppercase small">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="text-white fw-bold"><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></div>
                                <div class="small text-white-50"><?php echo date('h:i A', strtotime($pay['created_at'] ?? $pay['payment_date'])); ?></div>
                            </td>
                            <td>
                                <a href="project-details.php?id=<?php echo $pay['client_id']; ?>" class="text-white text-decoration-none hover-gold fw-bold d-flex align-items-center">
                                    <i class="bi bi-building text-white-50 me-2"></i>
                                    <?php echo htmlspecialchars($pay['company_name']); ?>
                                </a>
                                <div class="small text-white-50">ID: #<?php echo $pay['client_id']; ?></div>
                            </td>
                            <td>
                                <span class="fw-bold fs-6 text-white"><?php echo number_format($pay['amount'], 2); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-50">
                                    <i class="bi bi-credit-card me-1"></i><?php echo htmlspecialchars($pay['payment_method']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php 
                                    if ($pay['payment_status'] == 'Completed') {
                                        echo '<span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i>Completed</span>';
                                    } elseif ($pay['payment_status'] == 'Pending') {
                                        echo '<span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';
                                    } else {
                                        echo '<span class="badge bg-danger rounded-pill px-3 py-2"><i class="bi bi-x-circle me-1"></i>' . htmlspecialchars($pay['payment_status']) . '</span>';
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-funnel text-white-50 fs-1 mb-3 d-block"></i>
                                <h5 class="text-white">No transactions found for these filters.</h5>
                                <p class="text-white-50 small mb-0">Try adjusting your date range or project selection.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card-box p-0 overflow-hidden mt-5">
        <div class="p-4 border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center bg-dark bg-opacity-50">
            <h5 class="text-warning fw-bold mb-0"><i class="bi bi-wallet2 me-2"></i>Recorded Expenses</h5>
            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 shadow-sm">
                Total: SAR <?php echo number_format($total_expenses, 2); ?>
            </span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle" style="background: transparent;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.05);">
                        <th class="py-3 ps-4 text-warning text-uppercase small">Date</th>
                        <th class="py-3 text-warning text-uppercase small">Expense Title</th>
                        <th class="py-3 text-warning text-uppercase small">Category</th>
                        <th class="py-3 text-end pe-4 text-warning text-uppercase small">Amount (SAR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($expense_records) > 0): ?>
                        <?php foreach ($expense_records as $exp): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="text-white fw-bold"><?php echo date('M d, Y', strtotime($exp['expense_date'])); ?></div>
                            </td>
                            <td>
                                <div class="text-white fw-bold"><?php echo htmlspecialchars($exp['title']); ?></div>
                                <?php if (!empty($exp['description'])): ?>
                                    <div class="small text-white-50 text-truncate" style="max-width: 300px;">
                                        <?php echo htmlspecialchars($exp['description']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-warning text-warning px-2 py-1">
                                    <?php echo htmlspecialchars($exp['category']); ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <span class="fw-bold fs-6 text-warning">- <?php echo number_format($exp['amount'], 2); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="bi bi-inbox text-white-50 fs-1 mb-3 d-block"></i>
                                <h5 class="text-white">No expenses recorded.</h5>
                                <p class="text-white-50 small mb-0">Expenses falling within your date filter will appear here.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require_once '../portal/includes/footer.php'; ?>