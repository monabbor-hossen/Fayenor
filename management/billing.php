<?php
// management/billing.php
require_once '../portal/includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

// --- SECURITY: ENSURE ONLY CLIENTS ACCESS THIS ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../public/login.php");
    exit();
}

$db = (new Database())->getConnection();
$account_id = $_SESSION['account_id'] ?? $_SESSION['user_id']; 

// --- GET FILTER ---
$filter_client_id = isset($_GET['client_id']) ? $_GET['client_id'] : 'all';

try {
    // --- 1. FETCH ALL ACTIVE PROJECTS FOR DROPDOWN ---
    $stmtApps = $db->prepare("SELECT client_id, company_name, contract_value FROM clients WHERE account_id = ? AND is_active = 1 ORDER BY company_name ASC");
    $stmtApps->execute([$account_id]);
    $apps = $stmtApps->fetchAll(PDO::FETCH_ASSOC);

    // --- 2. CALCULATE FILTERED FINANCIAL SUMMARY ---
    $total_contract = 0;
    $filtered_apps_count = 0;

    foreach ($apps as $app) {
        // If "all" is selected OR this app matches the selected filter
        if ($filter_client_id === 'all' || $filter_client_id == $app['client_id']) {
            $total_contract += floatval($app['contract_value']);
            $filtered_apps_count++;
        }
    }

    // --- 3. FETCH PAYMENT HISTORY BASED ON FILTER ---
    if ($filter_client_id !== 'all') {
        // Fetch only payments for the specific filtered project
        $stmtPay = $db->prepare("SELECT p.*, c.company_name, c.client_id 
                                 FROM payments p 
                                 JOIN clients c ON p.client_id = c.client_id 
                                 WHERE c.account_id = ? AND p.client_id = ? 
                                 ORDER BY p.payment_date DESC");
        $stmtPay->execute([$account_id, $filter_client_id]);
    } else {
        // Fetch all payments for all projects
        $stmtPay = $db->prepare("SELECT p.*, c.company_name, c.client_id 
                                 FROM payments p 
                                 JOIN clients c ON p.client_id = c.client_id 
                                 WHERE c.account_id = ? 
                                 ORDER BY p.payment_date DESC");
        $stmtPay->execute([$account_id]);
    }
    $payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. CALCULATE PAID & DUE ---
    $total_paid = 0;
    foreach ($payments as $pay) {
        if ($pay['payment_status'] === 'Completed') {
            $total_paid += floatval($pay['amount']);
        }
    }

    $total_due = $total_contract - $total_paid;

} catch (PDOException $e) {
    // Safety Net - If database crashes, hide loader and show error
    echo "<script>document.addEventListener('DOMContentLoaded', function() { document.querySelector('.global-loader').classList.add('hidden'); });</script>";
    die("<div class='container mt-5'><div class='alert alert-danger p-4 rounded-4 shadow'><b>Database Error:</b> " . $e->getMessage() . "</div></div>");
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
            <h2 class="text-white fw-bold mb-1"><i class="bi bi-receipt text-gold me-3"></i>Billing & Invoices</h2>
            <p class="text-white-50 small mb-0">Overview of all your project financials and payment history.</p>
        </div>
        <button class="btn btn-outline-light btn-sm rounded-pill px-4 d-none d-md-block" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Print Statement
        </button>
    </div>

    <div class="glass-panel p-3 mb-4 d-flex justify-content-between align-items-center" style="padding: 15px 25px !important;">
        <div>
            <h6 class="text-gold fw-bold mb-0"><i class="bi bi-funnel-fill me-2"></i>Filter by Company/Project</h6>
        </div>
        <form method="GET" action="billing.php" class="m-0">
            <select name="client_id" class="form-select glass-input rounded-pill shadow-sm py-2 px-4" style="min-width: 280px; cursor: pointer;" onchange="this.form.submit()">
                <option value="all" <?php echo ($filter_client_id === 'all') ? 'selected' : ''; ?>>All Companies & Projects</option>
                <?php foreach ($apps as $app): ?>
                    <option value="<?php echo $app['client_id']; ?>" <?php echo ($filter_client_id == $app['client_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($app['company_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="glass-panel p-4 border-bottom border-3 border-secondary text-center h-100" style="background: rgba(255,255,255,0.02);">
                <div class="icon-box bg-secondary bg-opacity-25 text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-briefcase fs-4"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1">Total Contract Value</h6>
                <h3 class="text-white mb-0 fw-bold"><?php echo number_format($total_contract, 2); ?> <small class="fs-6 text-white-50">SAR</small></h3>
                <div class="small text-white-50 mt-2">Across <?php echo $filtered_apps_count; ?> active project(s)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-panel p-4 border-bottom border-3 border-success text-center h-100" style="background: rgba(255,255,255,0.02);">
                <div class="icon-box bg-success bg-opacity-25 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1">Total Paid</h6>
                <h3 class="text-success mb-0 fw-bold"><?php echo number_format($total_paid, 2); ?> <small class="fs-6 text-white-50">SAR</small></h3>
                <div class="small text-white-50 mt-2">All completed transactions</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-panel p-4 border-bottom border-3 border-danger text-center h-100" style="background: rgba(255,255,255,0.02);">
                <div class="icon-box bg-danger bg-opacity-25 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-exclamation-circle fs-4"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1">Remaining Balance</h6>
                <h3 class="text-danger mb-0 fw-bold"><?php echo number_format($total_due, 2); ?> <small class="fs-6 text-white-50">SAR</small></h3>
                <div class="small text-white-50 mt-2">Total amount currently due</div>
            </div>
        </div>
    </div>

    <div class="card-box p-0 overflow-hidden">
        <div class="p-4 border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center bg-dark bg-opacity-50">
            <h5 class="text-gold fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Transaction History</h5>
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
                                <i class="bi bi-receipt text-white-50 fs-1 mb-3 d-block"></i>
                                <h5 class="text-white">No transactions found.</h5>
                                <p class="text-white-50 small mb-0">Your payment history will appear here once recorded by the administration.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../portal/includes/footer.php'; ?>