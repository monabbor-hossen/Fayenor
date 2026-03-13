<?php
// management/expenses.php
require_once '../portal/includes/header.php'; 
require_once __DIR__ . '/../app/Config/Database.php';

// Security check: Only Clients can access this specific page
if ($_SESSION['role'] !== 'client') {
    echo "<script>window.location.href='../portal/dashboard.php';</script>";
    exit();
}
if ($_SESSION['role'] === 'client') {
    $account_id = $_SESSION['account_id'] ?? $_SESSION['user_id'];
    $stmtCheck = $db->prepare("SELECT show_expenses FROM clients WHERE account_id = ? LIMIT 1");
    $stmtCheck->execute([$account_id]);
    $perm = $stmtCheck->fetch();
    if (!$perm || $perm['show_expenses'] == 0) {
        header("Location: dashboard.php");
        exit();
    }
}

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id']; 
$account_id = $_SESSION['account_id'] ?? $_SESSION['user_id'];

// --- Grab messages from the Session (PRG Pattern) ---
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// --- Fetch Active Companies for the Dropdown ---
$stmtApps = $db->prepare("SELECT client_id, company_name FROM clients WHERE account_id = ? AND is_active = 1 ORDER BY company_name ASC");
$stmtApps->execute([$account_id]);
$client_companies = $stmtApps->fetchAll(PDO::FETCH_ASSOC);

// --- Handle DELETE Expense ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $stmt = $db->prepare("DELETE FROM expenses WHERE id = ? AND created_by = ?");
        $stmt->execute([$delete_id, $user_id]);
        
        $_SESSION['success_msg'] = "Expense deleted successfully!";
        echo "<script>window.location.href='expenses.php';</script>";
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
        echo "<script>window.location.href='expenses.php';</script>";
        exit();
    }
}

// --- Handle ADD Expense Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $project_id = !empty($_POST['client_id']) ? intval($_POST['client_id']) : null; // Can be null if "General Expense"
    $title = trim($_POST['title']);
    $amount = floatval($_POST['amount']);
    $date = $_POST['expense_date'];
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);

    if (!empty($title) && $amount > 0 && !empty($date) && !empty($category)) {
        try {
            // INSERT with the new client_id column!
            $stmt = $db->prepare("INSERT INTO expenses (client_id, title, amount, expense_date, category, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$project_id, $title, $amount, $date, $category, $description, $user_id]);
            
            $_SESSION['success_msg'] = "Expense recorded successfully!";
            echo "<script>window.location.href='expenses.php';</script>";
            exit();
            
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage(); 
        }
    } else {
        $error_msg = "Please fill in all required fields with valid data.";
    }
}

// --- Fetch Data for UI (Joining with clients table to get company name) ---
$stmt = $db->prepare("
    SELECT e.*, c.company_name 
    FROM expenses e 
    LEFT JOIN clients c ON e.client_id = c.client_id 
    WHERE e.created_by = ? 
    ORDER BY e.expense_date DESC, e.created_at DESC LIMIT 50
");
$stmt->execute([$user_id]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_stmt = $db->prepare("SELECT SUM(amount) as total FROM expenses WHERE created_by = ?");
$total_stmt->execute([$user_id]);
$total_expenses = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0.00;
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-white fw-bold mb-0"><i class="bi bi-wallet2 text-gold me-2"></i>My Expenses</h3>
            <p class="text-white-50 small mb-0">Track and manage your project costs.</p>
        </div>
        <div class="text-end bg-dark bg-opacity-50 px-4 py-2 rounded-pill border border-gold border-opacity-25 shadow-sm d-none d-sm-block">
            <span class="text-white-50 small text-uppercase fw-bold me-2">Total Expenses:</span>
            <span class="text-gold fw-bold fs-5">SAR <?php echo number_format($total_expenses, 2); ?></span>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert bg-success bg-opacity-25 text-success border border-success border-opacity-25 alert-dismissible fade show rounded-3">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
        <div class="alert bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 alert-dismissible fade show rounded-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-panel p-4 h-100">
                <h5 class="text-gold fw-bold mb-4 border-bottom border-light border-opacity-10 pb-2">Record Expense</h5>
                
                <form action="expenses.php" method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Project / Company</label>
                        <select name="client_id" class="form-select glass-input rounded-3" style="cursor: pointer;">
                            <option value="">-- General Expense (No Project) --</option>
                            <?php foreach ($client_companies as $company): ?>
                                <option value="<?php echo $company['client_id']; ?>">
                                    <?php echo htmlspecialchars($company['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Expense Title *</label>
                        <input type="text" name="title" class="form-control glass-input rounded-3" placeholder="e.g., Material Costs" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50 small">Amount (SAR) *</label>
                            <input type="number" step="0.01" name="amount" class="form-control glass-input rounded-3" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50 small">Date *</label>
                            <div class="position-relative">
                                <input type="text" name="expense_date" class="form-control glass-input rounded-3 rooq-date" value="<?php echo date('Y-m-d'); ?>" readonly style="cursor: pointer;" required>
                                <i class="bi bi-calendar-date position-absolute text-gold" style="right: 15px; top: 10px; pointer-events: none;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Category *</label>
                        <select name="category" class="form-select glass-input rounded-3" required>
                            <option value="Materials">Materials</option>
                            <option value="Services">Services</option>
                            <option value="Logistics">Logistics</option>
                            <option value="Gov Fees">Government Fees</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white-50 small">Description (Optional)</label>
                        <textarea name="description" class="form-control glass-input rounded-3" rows="3" placeholder="Add extra details..."></textarea>
                    </div>

                    <button type="submit" name="add_expense" class="btn btn-rooq-primary w-100 shadow-lg">
                        <i class="bi bi-plus-lg me-2"></i> Submit Expense
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-panel p-4 h-100 d-flex flex-column">
                <h5 class="text-gold fw-bold mb-4 border-bottom border-light border-opacity-10 pb-2">My Recent Expenses</h5>
                
                <div class="table-responsive flex-grow-1">
                    <table class="table table-dark table-hover align-middle bg-transparent mb-0">
                        <thead class="text-white-50 small text-uppercase">
                            <tr>
                                <th class="bg-transparent border-bottom border-light border-opacity-10 py-3">Date</th>
                                <th class="bg-transparent border-bottom border-light border-opacity-10 py-3">Expense Details</th>
                                <th class="bg-transparent border-bottom border-light border-opacity-10 py-3">Project / Company</th>
                                <th class="bg-transparent border-bottom border-light border-opacity-10 py-3 text-end">Amount</th>
                                <th class="bg-transparent border-bottom border-light border-opacity-10 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-light border-opacity-10">
                            <?php if (count($expenses) > 0): ?>
                                <?php foreach ($expenses as $exp): ?>
                                    <tr>
                                        <td class="bg-transparent text-white-50 border-light border-opacity-10">
                                            <?php echo date('M d, Y', strtotime($exp['expense_date'])); ?>
                                        </td>
                                        <td class="bg-transparent border-light border-opacity-10">
                                            <div class="fw-bold text-white"><?php echo htmlspecialchars($exp['title']); ?></div>
                                            <div class="small text-white-50">
                                                <span class="badge bg-dark border border-warning text-warning px-2 py-1 mt-1"><?php echo htmlspecialchars($exp['category']); ?></span>
                                            </div>
                                        </td>
                                        <td class="bg-transparent border-light border-opacity-10">
                                            <?php if (!empty($exp['company_name'])): ?>
                                                <span class="text-info fw-bold"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($exp['company_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-white-50 small fst-italic">General (No Project)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="bg-transparent border-light border-opacity-10 text-end fw-bold text-danger">
                                            - <?php echo number_format($exp['amount'], 2); ?>
                                        </td>
                                        <td class="bg-transparent border-light border-opacity-10 text-center">
                                            <button type="button" class="btn btn-sm btn-outline-info rounded-circle border-0 me-1 shadow-none" 
                                                title="View Details"
                                                onclick="viewExpense(
                                                    '<?php echo htmlspecialchars(addslashes($exp['title'])); ?>', 
                                                    '<?php echo number_format($exp['amount'], 2); ?>', 
                                                    '<?php echo date('F d, Y', strtotime($exp['expense_date'])); ?>', 
                                                    '<?php echo htmlspecialchars(addslashes($exp['category'])); ?>', 
                                                    '<?php echo htmlspecialchars(addslashes($exp['description'] ?? 'No description provided.')); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($exp['company_name'] ?? 'General (No Project)')); ?>'
                                                )">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            <a href="javascript:void(0);" 
                                                class="btn btn-sm btn-outline-danger rounded-circle border-0 shadow-none" 
                                                title="Delete Expense"
                                                onclick="triggerLinkModal('expenses.php?delete_id=<?php echo $exp['id']; ?>', 'Are you sure you want to completely delete this expense? This action cannot be undone.')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-white-50 py-5 bg-transparent border-0">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 text-gold opacity-50"></i>
                                        No expenses recorded yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../portal/includes/footer.php'; ?>