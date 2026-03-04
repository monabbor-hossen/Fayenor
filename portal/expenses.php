<?php
// portal/expenses.php
require_once 'includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

// Security check: Only Admin/Staff can see expenses
if ($_SESSION['role'] === 'client') {
    echo "<script>window.location.href='../management/dashboard.php';</script>";
    exit();
}

$db = (new Database())->getConnection();

// --- Grab messages from the Session (PRG Pattern) ---
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']); // Clear them instantly

// --- Handle DELETE Expense ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        // Save message and safely redirect using JavaScript
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
    $title = trim($_POST['title']);
    $amount = floatval($_POST['amount']);
    $date = $_POST['expense_date'];
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $created_by = $_SESSION['user_id'];

    if (!empty($title) && $amount > 0 && !empty($date) && !empty($category)) {
        try {
            $stmt = $db->prepare("INSERT INTO expenses (title, amount, expense_date, category, description, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $amount, $date, $category, $description, $created_by]);
            
            // Save message and safely redirect using JavaScript
            $_SESSION['success_msg'] = "Expense added successfully!";
            echo "<script>window.location.href='expenses.php';</script>";
            exit();
            
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage(); 
        }
    } else {
        $error_msg = "Please fill in all required fields with valid data.";
    }
}

// --- Fetch Data for UI ---
$stmt = $db->prepare("SELECT e.*, u.full_name FROM expenses e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.expense_date DESC, e.created_at DESC LIMIT 50");
$stmt->execute();
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_stmt = $db->prepare("SELECT SUM(amount) as total FROM expenses");
$total_stmt->execute();
$total_expenses = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0.00;
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-white fw-bold mb-0"><i class="bi bi-wallet2 text-gold me-2"></i>Company Expenses</h3>
            <p class="text-white-50 small mb-0">Track and manage your operational costs.</p>
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
                <h5 class="text-gold fw-bold mb-4 border-bottom border-light border-opacity-10 pb-2">Add New Expense</h5>
                
                <form action="expenses.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Expense Title *</label>
                        <input type="text" name="title" class="form-control glass-input rounded-3" placeholder="e.g., Office Internet" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50 small">Amount (SAR) *</label>
                            <input type="number" step="0.01" name="amount" class="form-control glass-input rounded-3" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white-50 small">Date *</label>
                            <input type="date" name="expense_date" class="form-control glass-input rounded-3" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Category *</label>
                        <select name="category" class="form-select glass-input rounded-3" required>
                            <option value="Office Supplies">Office Supplies</option>
                            <option value="Software & IT">Software & IT</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Travel">Travel</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white-50 small">Description (Optional)</label>
                        <textarea name="description" class="form-control glass-input rounded-3" rows="3" placeholder="Add extra details..."></textarea>
                    </div>

                    <button type="submit" name="add_expense" class="btn btn-rooq-primary w-100 shadow-lg">
                        <i class="bi bi-plus-lg me-2"></i> Save Expense
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-panel p-4 h-100 d-flex flex-column">
                <h5 class="text-gold fw-bold mb-4 border-bottom border-light border-opacity-10 pb-2">Recent Expenses</h5>
                
                <div class="table-responsive flex-grow-1">
                    <table class="table table-dark table-hover align-middle bg-transparent mb-0">
                        <thead class="text-white-50 small text-uppercase">
                            <tr>
                                <th class="bg-transparent border-bottom border-light border-opacity-10 py-3">Date</th>
                                <th class="bg-transparent border-bottom border-light border-opacity-10 py-3">Details</th>
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
                                                <span class="text-gold me-1">[<?php echo htmlspecialchars($exp['category']); ?>]</span>
                                                By <?php echo htmlspecialchars($exp['full_name']); ?>
                                            </div>
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
                                                    '<?php echo htmlspecialchars(addslashes($exp['description'] ?? 'No additional description provided.')); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($exp['full_name'])); ?>'
                                                )">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            <a href="expenses.php?delete_id=<?php echo $exp['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-circle border-0 shadow-none" 
                                               title="Delete Expense"
                                               onclick="return confirm('Are you sure you want to completely delete this expense? This action cannot be undone.');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-white-50 py-5 bg-transparent border-0">
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

<div class="modal fade" id="viewExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal rounded-4 shadow-lg">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white fw-bold"><i class="bi bi-receipt text-gold me-2"></i>Expense Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <h4 id="viewTitle" class="text-white fw-bold mb-3">--</h4>
                
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="view-label">Amount</div>
                        <div class="view-value text-danger fw-bold fs-4">SAR <span id="viewAmount">0.00</span></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">Category</div>
                        <div class="view-value"><span id="viewCategory" class="badge bg-dark border border-gold text-gold px-3 py-2">--</span></div>
                    </div>
                </div>

                <div class="row mb-4 border-top border-light border-opacity-10 pt-3">
                    <div class="col-6">
                        <div class="view-label">Date</div>
                        <div class="view-value fs-6" id="viewDate">--</div>
                    </div>
                    <div class="col-6">
                        <div class="view-label">Added By</div>
                        <div class="view-value fs-6" id="viewUser">--</div>
                    </div>
                </div>

                <div class="bg-dark bg-opacity-50 p-3 rounded-3 border border-light border-opacity-10">
                    <div class="view-label mb-2"><i class="bi bi-card-text me-1 text-gold"></i> Description</div>
                    <div class="text-white-50 small" id="viewDesc" style="white-space: pre-wrap; line-height: 1.6;">--</div>
                </div>

            </div>
            <div class="modal-footer border-top border-light border-opacity-10">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>