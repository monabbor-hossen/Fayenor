<?php
// management/dashboard.php
require_once '../portal/includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

// --- SECURITY: ENSURE ONLY CLIENTS ACCESS THIS ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../public/login.php");
    exit();
}

$db = (new Database())->getConnection();
$account_id = $_SESSION['account_id'] ?? $_SESSION['user_id']; 

// --- 1. FETCH ALL ACTIVE APPLICATIONS FOR THIS CLIENT ---
$stmt = $db->prepare("SELECT c.*, w.* FROM clients c 
                      LEFT JOIN workflow_tracking w ON c.client_id = w.client_id 
                      WHERE c.account_id = ? AND c.is_active = 1 
                      ORDER BY c.client_id DESC");
$stmt->execute([$account_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 2. FETCH PAYMENT HISTORY FOR THIS CLIENT ---
$stmtPay = $db->prepare("SELECT p.*, c.company_name 
                         FROM payments p 
                         JOIN clients c ON p.client_id = c.client_id 
                         WHERE c.account_id = ? 
                         ORDER BY p.payment_date DESC LIMIT 5");
$stmtPay->execute([$account_id]);
$recent_payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

// --- 3. CALCULATE DASHBOARD METRICS ---
$total_apps = count($applications);
$total_contract_value = 0;
$total_paid = 0;
$total_approved_steps = 0;
$total_active_steps = 0;

foreach ($applications as &$app) {
    // Financials
    $contract = floatval($app['contract_value']);
    $total_contract_value += $contract;

    // Fetch total paid for this specific app
    $stmtPaid = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE client_id = ? AND payment_status = 'Completed'");
    $stmtPaid->execute([$app['client_id']]);
    $app_paid = floatval($stmtPaid->fetchColumn());
    $total_paid += $app_paid;
    
    $app['paid_amount'] = $app_paid;
    $app['due_amount'] = $contract - $app_paid;

    // Progress Calculation
    $steps_to_check = [
        $app['hire_foreign_company'] ?? '', $app['misa_application'] ?? '',
        $app['sbc_application'] ?? '',      $app['article_association'] ?? '',
        $app['qiwa'] ?? '',                 $app['muqeem'] ?? '',
        $app['gosi'] ?? '',                 $app['chamber_commerce'] ?? ''
    ];
    
    $app_approved = 0;
    $app_active_steps = 0; 
    
    foreach($steps_to_check as $status) { 
        if ($status !== 'Not Required' && !empty($status)) {
            $app_active_steps++; 
            $total_active_steps++;
            if ($status === 'Approved' || $status === 'Completed') {
                $app_approved++; 
                $total_approved_steps++;
            }
        }
    }
    
    $app['progress_percent'] = ($app_active_steps > 0) ? round(($app_approved / $app_active_steps) * 100) : 0;
}
unset($app);
// --- FETCH TOTAL EXPENSES FOR DASHBOARD ---
$stmtExpTotal = $db->prepare("SELECT SUM(amount) as total FROM expenses WHERE created_by = ?");
$stmtExpTotal->execute([$_SESSION['user_id']]);
$expRow = $stmtExpTotal->fetch(PDO::FETCH_ASSOC);

// CRITICAL FIX: Safely handle if the database returns false (no expenses)
$total_expenses = $expRow ? ($expRow['total'] ?? 0.00) : 0.00;

// --- FETCH 5 RECENT EXPENSES FOR WIDGET ---
$stmtRecentExp = $db->prepare("SELECT title, amount, expense_date, category FROM expenses WHERE created_by = ? ORDER BY expense_date DESC, created_at DESC LIMIT 5");
$stmtRecentExp->execute([$_SESSION['user_id']]);
$recent_expenses = $stmtRecentExp->fetchAll(PDO::FETCH_ASSOC);

$total_due = $total_contract_value - $total_paid;
$overall_progress = ($total_active_steps > 0) ? round(($total_approved_steps / $total_active_steps) * 100) : 0;
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-white fw-bold mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
            <p class="text-white-50 small mb-0">Here is the latest overview of your applications and accounts.</p>
        </div>
        <div class="text-end d-none d-md-block">
            <div class="text-white-50 small">Current Date</div>
            <div class="text-gold fw-bold"><i class="bi bi-calendar3 me-2"></i><?php echo date('F d, Y'); ?></div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5 g-3 mb-5">
        
        <div class="col">
            <div class="glass-panel p-3 border-bottom border-3 border-info text-center h-100 d-flex flex-column justify-content-center" style="background: rgba(255,255,255,0.02); transition: transform 0.3s ease;">
                <div class="icon-box bg-info bg-opacity-25 text-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 48px; height: 48px;">
                    <i class="bi bi-folder-fill fs-5"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Active Projects</h6>
                <h4 class="text-white mb-0 fw-bold"><?php echo $total_apps; ?></h4>
            </div>
        </div>

        <div class="col">
            <div class="glass-panel p-3 border-bottom border-3 border-secondary text-center h-100 d-flex flex-column justify-content-center" style="background: rgba(255,255,255,0.02); transition: transform 0.3s ease;">
                <div class="icon-box bg-secondary bg-opacity-25 text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 48px; height: 48px;">
                    <i class="bi bi-briefcase fs-5"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Contract Value</h6>
                <h4 class="text-white mb-0 fw-bold"><?php echo number_format($total_contract_value, 2); ?></h4>
            </div>
        </div>
        
        <div class="col">
            <div class="glass-panel p-3 border-bottom border-3 border-success text-center h-100 d-flex flex-column justify-content-center" style="background: rgba(255,255,255,0.02); transition: transform 0.3s ease;">
                <div class="icon-box bg-success bg-opacity-25 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 48px; height: 48px;">
                    <i class="bi bi-check-circle fs-5"></i>
                </div>
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Total Paid</h6>
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
                <h6 class="text-white-50 small text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Total Expenses</h6>
                <h4 class="text-warning mb-0 fw-bold"><?php echo number_format($total_expenses, 2); ?></h4>
            </div>
        </div>
        
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h5 class="text-gold fw-bold mb-3"><i class="bi bi-building me-2"></i>Your Applications</h5>
            
            <?php if (count($applications) > 0): ?>
                <div class="row g-3">
                    <?php foreach ($applications as $app): 
                        $progress_color = 'bg-info';
                        if ($app['progress_percent'] == 100) $progress_color = 'bg-success';
                        elseif ($app['progress_percent'] < 30) $progress_color = 'bg-warning';
                    ?>
                    <div class="col-md-6">
                        <div class="glass-panel p-4 h-100" style="transition: transform 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'" onclick="window.location.href='project-details.php?id=<?php echo $app['client_id']; ?>'">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="text-white fw-bold mb-1" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;" title="<?php echo htmlspecialchars($app['company_name']); ?>">
                                        <?php echo htmlspecialchars($app['company_name']); ?>
                                    </h5>
                                    <span class="badge bg-secondary bg-opacity-50 text-white-50 small">ID: #<?php echo $app['client_id']; ?></span>
                                </div>
                                <div class="text-end">
                                    <div class="text-gold fw-bold fs-5"><?php echo $app['progress_percent']; ?>%</div>
                                </div>
                            </div>
                            
                            <div class="progress mb-3" style="height: 8px; background: rgba(255,255,255,0.1);">
                                <div class="progress-bar <?php echo $progress_color; ?> progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo $app['progress_percent']; ?>%;" aria-valuenow="<?php echo $app['progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-secondary border-opacity-25">
                                <div>
                                    <div class="text-white-50 small" style="font-size: 0.7rem;">DUE BALANCE</div>
                                    <div class="<?php echo ($app['due_amount'] > 0) ? 'text-danger' : 'text-success'; ?> fw-bold small">
                                        <?php echo number_format($app['due_amount'], 2); ?> SAR
                                    </div>
                                </div>
                                <a href="project-details.php?id=<?php echo $app['client_id']; ?>" class="btn btn-sm btn-outline-light rounded-pill px-3" style="font-size: 0.8rem;">View Details <i class="bi bi-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="glass-panel p-5 text-center">
                    <i class="bi bi-folder-x fs-1 text-white-50 mb-3 d-block"></i>
                    <h5 class="text-white">No active applications found.</h5>
                    <p class="text-white-50 small">If you believe this is a mistake, please contact support.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <h5 class="text-gold fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Recent Payments</h5>
            
            <div class="card-box p-0 overflow-hidden">
                <?php if (count($recent_payments) > 0): ?>
                    <div class="list-group list-group-flush bg-transparent">
                        <?php foreach ($recent_payments as $pay): ?>
                            <div class="list-group-item bg-transparent border-bottom border-light border-opacity-10 py-3 px-4">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 text-white fw-bold"><?php echo number_format($pay['amount'], 2); ?> <small>SAR</small></h6>
                                    <small class="text-white-50"><i class="bi bi-calendar-event me-1"></i><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-gold text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($pay['company_name']); ?></small>
                                    <?php 
                                        $badge = 'bg-warning';
                                        if ($pay['payment_status'] == 'Completed') $badge = 'bg-success';
                                        elseif ($pay['payment_status'] == 'Failed') $badge = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill" style="font-size: 0.65rem;"><?php echo htmlspecialchars($pay['payment_status']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="p-3 text-center border-top border-light border-opacity-10 bg-dark bg-opacity-25">
                        <a href="billing.php" class="text-gold text-decoration-none small fw-bold hover-white">View All Invoices <i class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                <?php else: ?>
                    <div class="p-5 text-center">
                        <i class="bi bi-receipt text-white-50 fs-2 mb-2 d-block"></i>
                        <span class="text-white-50 small">No payment history yet.</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="glass-panel p-4 mt-4 text-center" style="background: linear-gradient(145deg, rgba(212,175,55,0.1) 0%, rgba(0,0,0,0.4) 100%); border-color: rgba(212,175,55,0.3);">
                <i class="bi bi-headset fs-1 text-gold mb-2 d-block"></i>
                <h6 class="text-white fw-bold">Need Assistance?</h6>
                <p class="text-white-50 small mb-3">Our support team is here to help with your applications.</p>
                <a href="mailto:support@rooqflow.com" class="btn btn-rooq-primary btn-sm rounded-pill w-100 fw-bold">Contact Support</a>
            </div>
        </div>
    </div>

<?php require_once '../portal/includes/footer.php';?>