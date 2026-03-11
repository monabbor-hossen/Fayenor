<?php
session_start();

// Security Check (Admin/Staff only)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'client') {
    header("Location: ../public/login.php");
    exit();
}

require_once '../app/Config/Config.php';
require_once '../app/Config/Database.php';

$db = (new Database())->getConnection();
$success_msg = '';

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE default_contract_settings SET 
            objective=?, permitted_activities=?, documentation=?, payment_terms=?, 
            obligations=?, timeline_days=?, timeline_text=?, 
            bank_name=?, account_number=?, iban_number=?, account_name=? 
            WHERE id=1";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $_POST['objective'], $_POST['permitted'], $_POST['docs'], 
        $_POST['payment'], $_POST['obligations'], intval($_POST['timeline_days']), $_POST['timeline_text'],
        $_POST['bank_name'], $_POST['account_number'], $_POST['iban_number'], $_POST['account_name']
    ]);
    $success_msg = "Global Default Settings Saved Successfully!";
}

// Fetch Global Settings
$stmt = $db->query("SELECT * FROM default_contract_settings WHERE id = 1");
$defaults = $stmt->fetch(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<link href="<?php echo BASE_URL;?>assets/css/summernote-lite.min.css" rel="stylesheet">
<style>
    .note-editor.note-frame { background: white; color: black; border-radius: 8px; overflow: hidden; }
</style>

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1>Global Contract Template</h1>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Home</a></li><li class="breadcrumb-item active">Contract Template</li></ol></nav>
        </div>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <section class="section">
        <div class="card shadow-sm">
            <div class="card-body pt-4">
                <div class="alert alert-info text-center fw-bold">
                    <i class="bi bi-info-circle"></i> Editing this page changes the DEFAULT template for all future contracts. 
                    (If a client's contract was previously edited individually, it will keep its custom edits).
                </div>

                <form method="POST">
                    <h5 class="card-title mt-0">1. Objective of the Agreement</h5>
                    <textarea name="objective" class="rich-editor"><?php echo htmlspecialchars($defaults['objective']); ?></textarea>

                    <h5 class="card-title">2. Permitted Activities</h5>
                    <textarea name="permitted" class="rich-editor"><?php echo htmlspecialchars($defaults['permitted_activities']); ?></textarea>

                    <h5 class="card-title">4. Client Documentation Requirements</h5>
                    <textarea name="docs" class="rich-editor"><?php echo htmlspecialchars($defaults['documentation']); ?></textarea>

                    <h5 class="card-title">6. Payment Terms & Bank Details</h5>
                    <textarea name="payment" class="rich-editor mb-3"><?php echo htmlspecialchars($defaults['payment_terms']); ?></textarea>
                    
                    <div class="row g-3 bg-light p-3 rounded border border-secondary-subtle">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($defaults['bank_name']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Account Number</label>
                            <input type="text" name="account_number" class="form-control" value="<?php echo htmlspecialchars($defaults['account_number']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">IBAN Number</label>
                            <input type="text" name="iban_number" class="form-control" value="<?php echo htmlspecialchars($defaults['iban_number']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Account Name</label>
                            <input type="text" name="account_name" class="form-control" value="<?php echo htmlspecialchars($defaults['account_name']); ?>">
                        </div>
                    </div>

                    <h5 class="card-title mt-4">7. Client Obligations</h5>
                    <textarea name="obligations" class="rich-editor"><?php echo htmlspecialchars($defaults['obligations']); ?></textarea>

                    <h5 class="card-title">8. Timeline & Delays</h5>
                    <div class="mb-3" style="width: 250px;">
                        <label class="form-label fw-bold">Estimated Timeline (Days)</label>
                        <div class="input-group">
                            <input type="number" name="timeline_days" class="form-control" value="<?php echo htmlspecialchars($defaults['timeline_days']); ?>">
                            <span class="input-group-text">Days</span>
                        </div>
                    </div>
                    <textarea name="timeline_text" class="rich-editor"><?php echo htmlspecialchars($defaults['timeline_text']); ?></textarea>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg w-50 fw-bold"><i class="bi bi-save"></i> Save Global Template</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<script src="<?php echo BASE_URL;?>assets/js/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/summernote-lite.min.js"></script>
    
<script>
  $('.rich-editor').summernote({
    tabsize: 2,
    height: 150,
    toolbar: [
      ['style', ['bold', 'italic', 'underline', 'clear']],
      ['para', ['ul', 'ol', 'paragraph']],
    ]
  });
</script>

<?php require_once 'includes/footer.php'; ?>