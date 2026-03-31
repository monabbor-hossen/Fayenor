<?php
// management/project-details.php
require_once '../portal/includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

// --- SECURITY: ENSURE ONLY CLIENTS ACCESS THIS ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../public/login");
    exit();
}

$client_id = $_GET['id'] ?? null;

if (!$client_id) {
    header("Location: dashboard");
    exit();
}

$db = (new Database())->getConnection();
$account_id = $_SESSION['account_id'] ?? $_SESSION['user_id']; 

// --- 1. SECURELY FETCH PROJECT DATA ---
// We strictly enforce that the account_id must match the logged-in client
$stmt = $db->prepare("SELECT c.*, w.* FROM clients c 
                      LEFT JOIN workflow_tracking w ON c.client_id = w.client_id 
                      WHERE c.client_id = ? AND c.account_id = ? AND c.is_active = 1 LIMIT 1");
$stmt->execute([$client_id, $account_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo "<div class='container mt-5 text-center'>
            <div class='glass-panel p-5 border-danger border-top border-3'>
                <i class='bi bi-shield-lock text-danger fs-1 mb-3 d-block'></i>
                <h4 class='text-white'>Access Denied or Project Not Found</h4>
                <p class='text-white-50'>You do not have permission to view this project or it has been deactivated.</p>
                <a href="dashboard" class='btn btn-outline-light mt-3'>Return to Dashboard</a>
            </div>
          </div>";
    require_once 'includes/footer.php';
    exit();
}

// --- 2. FETCH PAYMENT HISTORY FOR THIS PROJECT ---
$stmtPay = $db->prepare("SELECT * FROM payments WHERE client_id = ? ORDER BY payment_date DESC");
$stmtPay->execute([$client_id]);
$payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

// --- 3. CALCULATE PROGRESS & FINANCIALS ---
$total_paid = 0;
foreach ($payments as $pay) {
    if ($pay['payment_status'] === 'Completed') {
        $total_paid += floatval($pay['amount']);
    }
}
$contract_value = floatval($project['contract_value']);
$due_amount = $contract_value - $total_paid;

// Define Workflow Steps
$workflow_steps = [
    'hire_foreign_company' => 'Hire Foreign Company',
    'misa_application' => 'MISA Application',
    'sbc_application' => 'SBC Application',
    'article_association' => 'Article of Association',
    'qiwa' => 'Qiwa Registration',
    'muqeem' => 'Muqeem Portal',
    'gosi' => 'GOSI Registration',
    'chamber_commerce' => 'Chamber of Commerce'
];

$approved_count = 0;
$active_steps = 0;

foreach ($workflow_steps as $db_col => $title) {
    $status = $project[$db_col] ?? 'Pending';
    if ($status !== 'Not Required') {
        $active_steps++;
        if ($status === 'Approved' || $status === 'Completed') {
            $approved_count++;
        }
    }
}

$progress_percent = ($active_steps > 0) ? round(($approved_count / $active_steps) * 100) : 0;
$progress_color = ($progress_percent == 100) ? 'bg-success' : (($progress_percent < 30) ? 'bg-warning' : 'bg-info');
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="dashboard" class="text-white-50 text-decoration-none hover-white small fw-bold">
            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="text-white fw-bold mb-1"><i class="bi bi-building text-secondary me-3"></i><?php echo htmlspecialchars($project['company_name']); ?></h2>
            <div class="text-white-50 small">
                <span class="me-3"><i class="bi bi-hash me-1"></i>Project ID: #<?php echo htmlspecialchars($project['client_id']); ?></span>
                <span><i class="bi bi-calendar-check me-1"></i>Created: <?php echo date('M d, Y', strtotime($project['created_at'])); ?></span>
            </div>
        </div>
        <div>
            <span class="badge <?php echo ($progress_percent == 100) ? 'bg-success' : 'bg-primary'; ?> fs-6 px-3 py-2 rounded-pill shadow-sm">
                <?php echo ($progress_percent == 100) ? 'Completed' : 'In Progress'; ?>
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-box h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="text-secondary fw-bold mb-0"><i class="bi bi-list-check me-2"></i>Application Status</h5>
                    <div class="text-white fw-bold fs-5"><?php echo $progress_percent; ?>%</div>
                </div>
                
                <div class="progress mb-5" style="height: 10px; background: rgba(255,255,255,0.05); border-radius: 10px;">
                    <div class="progress-bar <?php echo $progress_color; ?> progress-bar-striped progress-bar-animated rounded-pill" role="progressbar" style="width: <?php echo $progress_percent; ?>%;"></div>
                </div>

                <div class="row g-3">
                    <?php foreach ($workflow_steps as $db_col => $title): 
                        $status = $project[$db_col] ?? 'Pending';
                        if (empty($status)) $status = 'Pending';
                        
                        // Theme Mapping for Statuses
                        $icon = 'bi-circle text-white-50';
                        $border = 'border-secondary border-opacity-25';
                        $text_class = 'text-white-50';
                        $bg = 'bg-transparent';
                        $opacity = '';

                        if ($status === 'Approved' || $status === 'Completed') {
                            $icon = 'bi-check-circle-fill text-success';
                            $border = 'border-success border-opacity-50';
                            $text_class = 'text-success';
                            $bg = 'bg-success bg-opacity-10';
                        } elseif ($status === 'Processing' || $status === 'Submitted') {
                            $icon = 'bi-arrow-repeat text-warning spin-icon'; // Add custom animation class if you have one
                            $border = 'border-warning border-opacity-50';
                            $text_class = 'text-warning';
                            $bg = 'bg-warning bg-opacity-10';
                        } elseif ($status === 'Not Required') {
                            $icon = 'bi-dash-circle text-secondary';
                            $opacity = 'opacity: 0.5;';
                        }
                    ?>
                    <div class="col-md-6">
                        <div class="p-3 border rounded <?php echo $border; ?> <?php echo $bg; ?>" style="transition: all 0.3s ease; <?php echo $opacity; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="bi <?php echo $icon; ?> fs-4 me-3"></i>
                                    <div>
                                        <div class="text-white fw-bold small"><?php echo $title; ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-dark border border-light border-opacity-10 <?php echo $text_class; ?> rounded-pill">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            
            <div class="card-box mb-4 p-3 rounded" style="background: linear-gradient(145deg, rgba(30,30,30,0.9) 0%, rgba(10,10,10,0.95) 100%);">
                <h6 class="text-secondary fw-bold mb-4 text-uppercase" style="font-size: 0.8rem;"><i class="bi bi-wallet2 me-2"></i>Financial Overview</h6>
                
                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom border-light border-opacity-10">
                    <span class="text-white-50">Total Contract Value</span>
                    <span class="text-white fw-bold"><?php echo number_format($contract_value, 2); ?> SAR</span>
                </div>
                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom border-light border-opacity-10">
                    <span class="text-white-50">Amount Paid</span>
                    <span class="text-success fw-bold"><?php echo number_format($total_paid, 2); ?> SAR</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-white-50">Remaining Balance</span>
                    <span class="<?php echo ($due_amount > 0) ? 'text-danger' : 'text-success'; ?> fs-4 fw-bold">
                        <?php echo number_format($due_amount, 2); ?> SAR
                    </span>
                </div>
            </div>

            <div class="card-box p-0 overflow-hidden mb-4">
                <div class="p-3 border-bottom border-light border-opacity-10 bg-dark bg-opacity-50">
                    <h6 class="text-secondary fw-bold mb-0 text-uppercase" style="font-size: 0.8rem;"><i class="bi bi-receipt me-2"></i>Payment History</h6>
                </div>
                <div class="list-group list-group-flush bg-transparent">
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $pay): ?>
                            <div class="list-group-item bg-transparent border-bottom border-light border-opacity-10 p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-white"><?php echo number_format($pay['amount'], 2); ?> SAR</div>
                                    <?php 
                                        $badge = 'bg-warning';
                                        if ($pay['payment_status'] == 'Completed') $badge = 'bg-success';
                                        elseif ($pay['payment_status'] == 'Failed') $badge = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill" style="font-size: 0.65rem;"><?php echo htmlspecialchars($pay['payment_status']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between text-white-50 small">
                                    <span><i class="bi bi-calendar-event me-1"></i><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></span>
                                    <span><?php echo htmlspecialchars($pay['payment_method']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-4 text-center">
                            <span class="text-white-50 small">No payments recorded yet.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass-panel p-4 border-top border-3 border-secondary">
                <h6 class="text-secondary fw-bold mb-3 text-uppercase" style="font-size: 0.8rem;"><i class="bi bi-info-circle me-2"></i>Registered Details</h6>
                <div class="mb-2">
                    <small class="text-white-50 d-block">Contact Name</small>
                    <span class="text-white"><?php echo htmlspecialchars($project['client_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="mb-2">
                    <small class="text-white-50 d-block">Email Address</small>
                    <span class="text-white"><?php echo htmlspecialchars($project['email'] ?? 'N/A'); ?></span>
                </div>
                <div>
                    <small class="text-white-50 d-block">Phone Number</small>
                    <span class="text-white"><?php echo htmlspecialchars($project['phone_number'] ?? 'N/A'); ?></span>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../portal/includes/footer.php'; ?>