<?php
// portal/client-finance.php
require_once __DIR__ . '/../app/Config/Config.php';
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Helpers/Security.php';
require_once 'includes/header.php';

$client_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['client_id']) ? intval($_GET['client_id']) : 0);
if (!$client_id) { echo "<script>window.location.href='clients.php';</script>"; exit(); }

$db = (new Database())->getConnection();

// --- FETCH THE COMPANY NAME ---
$company_name = "Unknown Company";
if ($client_id > 0) {
    $stmtClient = $db->prepare("SELECT company_name FROM clients WHERE client_id = ?");
    $stmtClient->execute([$client_id]);
    $fetched_name = $stmtClient->fetchColumn();
    if ($fetched_name) {
        $company_name = $fetched_name;
    }
}

// --- GRAB PRG SESSION MESSAGES ---
$message = "";
if (isset($_SESSION['success_msg'])) {
    $message = "<div class='alert alert-success bg-success bg-opacity-25 text-white border-success alert-dismissible fade show rounded-3'>" . $_SESSION['success_msg'] . "<button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button></div>";
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $message = "<div class='alert alert-danger bg-danger bg-opacity-25 text-white border-danger alert-dismissible fade show rounded-3'>" . $_SESSION['error_msg'] . "<button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button></div>";
    unset($_SESSION['error_msg']);
}

// --- 1. PRE-CALCULATE FINANCIALS (Must happen before form submission) ---
$stmt = $db->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);
$contract_value = floatval($client['contract_value']);

$stmtPayTotal = $db->prepare("SELECT SUM(amount) as total_paid FROM payments WHERE client_id = ? AND payment_status = 'Completed'");
$stmtPayTotal->execute([$client_id]);
$total_paid = floatval($stmtPayTotal->fetchColumn() ?? 0);

$due_amount = max(0, $contract_value - $total_paid);

// --- 2. ADD PAYMENT LOGIC (PRG PATTERN WITH OVERPAYMENT PROTECTION) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    Security::checkCSRF($_POST['csrf_token']);
    
    $amount = floatval($_POST['amount']);
    $method = Security::clean($_POST['payment_method']);
    $status = Security::clean($_POST['payment_status']);
    $note   = Security::clean($_POST['notes']);

    // SECURITY CHECK: Ensure amount does not exceed due amount
    if ($amount > $due_amount) {
        $_SESSION['error_msg'] = "Error: Payment amount (" . number_format($amount, 2) . ") cannot exceed the remaining balance (" . number_format($due_amount, 2) . ").";
        echo "<script>window.location.href='client-finance.php?id=" . $client_id . "';</script>";
        exit();
    }

    try {
        $stmtInsert = $db->prepare("INSERT INTO payments (client_id, amount, payment_method, payment_status, notes) VALUES (?, ?, ?, ?, ?)");
        if ($stmtInsert->execute([$client_id, $amount, $method, $status, $note])) {
            
            Security::logActivity("Recorded client payment of " . number_format($amount, 2) . " SAR for: " . $company_name);
            
            $_SESSION['success_msg'] = "Payment recorded successfully!";
            echo "<script>window.location.href='client-finance.php?id=" . $client_id . "';</script>";
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
        echo "<script>window.location.href='client-finance.php?id=" . $client_id . "';</script>";
        exit();
    }
}

// --- 3. FETCH FULL PAYMENT HISTORY FOR TABLE ---
$stmtHistory = $db->prepare("SELECT * FROM payments WHERE client_id = ? ORDER BY payment_date DESC");
$stmtHistory->execute([$client_id]);
$payments = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex portal-wrapper">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="w-100 p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="clients.php" class="text-white-50 text-decoration-none hover-white">
                    <i class="bi bi-arrow-left me-2"></i> Back to Clients
                </a>
                <h4 class="text-white fw-bold mb-0">Finance: <?php echo htmlspecialchars($client['company_name'] ?? 'Unknown'); ?></h4>
            </div>

            <?php echo $message; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card-box text-center border-warning">
                        <small class="text-gold text-uppercase fw-bold">Contract Value</small>
                        <h2 class="text-white mt-2"><?php echo number_format($contract_value, 2); ?> SAR</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-box text-center">
                        <small class="text-success text-uppercase fw-bold">Total Paid</small>
                        <h2 class="text-success mt-2"><?php echo number_format($total_paid, 2); ?> SAR</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-box text-center <?php echo ($due_amount > 0) ? 'border-danger' : 'border-success'; ?>">
                        <small class="text-danger text-uppercase fw-bold">Due Amount</small>
                        <h2 class="text-danger mt-2"><?php echo number_format($due_amount, 2); ?> SAR</h2>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card-box">
                        <?php if ($due_amount <= 0): ?>
                            <div class="text-center py-5">
                                <div class="icon-box bg-success bg-opacity-25 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                    <i class="bi bi-check-all fs-2"></i>
                                </div>
                                <h5 class="text-success fw-bold">Contract Fully Paid</h5>
                                <p class="text-white-50 small mb-0">No further payments can be added to this project.</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="text-gold mb-0"><i class="bi bi-plus-circle me-2"></i>Add Payment</h5>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="unlockPaymentForm">
                                    <label class="form-check-label text-white-50 small" for="unlockPaymentForm">Enable Edit</label>
                                </div>
                            </div>

                            <form method="POST" id="paymentForm">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">
                                <input type="hidden" name="add_payment" value="1">

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <label class="text-white-50 small mb-1">Amount</label>
                                        <small class="text-gold">Max: <?php echo number_format($due_amount, 2); ?></small>
                                    </div>
                                    <input type="number" step="0.01" max="<?php echo $due_amount; ?>" name="amount" class="form-control glass-input" required placeholder="0.00" disabled>
                                </div>

                                <div class="mb-3">
                                    <label class="text-white-50 small mb-1">Payment Method</label>
                                    <select name="payment_method" class="form-select glass-input" disabled>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Card">Card</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="text-white-50 small mb-1">Status</label>
                                    <select name="payment_status" class="form-select glass-input" disabled>
                                        <option value="Completed">Completed</option>
                                        <option value="Pending">Pending</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="text-white-50 small mb-1">Notes</label>
                                    <textarea name="notes" class="form-control glass-input" rows="2" disabled></textarea>
                                </div>

                                <button type="submit" class="btn btn-rooq-primary w-100 fw-bold" id="submitBtn" disabled>
                                    Record Payment
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card-box">
                        <h5 class="text-white fw-bold mb-3">Transaction History</h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0 align-middle" style="background: transparent;">
                                <thead>
                                    <tr class="text-white-50 border-bottom border-secondary">
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($payments as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="text-white small"><?php echo date('d M Y', strtotime($p['payment_date'])); ?></div>
                                            <div class="text-white-50" style="font-size: 0.75rem;">#<?php echo $p['payment_id']; ?></div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($p['payment_method']); ?>
                                            <?php if($p['notes']): ?>
                                                <i class="bi bi-info-circle ms-1 text-gold" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($p['notes']); ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($p['payment_status'] == 'Completed'): ?>
                                                <span class="badge bg-success text-dark">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            <?php echo number_format($p['amount'], 2); ?> SAR
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($payments)): ?>
                                        <tr><td colspan="4" class="text-center text-white-50 py-3">No transactions found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>


<?php require_once 'includes/footer.php'; ?>