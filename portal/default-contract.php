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

// --- FIX: HANDLE FORM SUBMIT WITH PRG (Post/Redirect/Get) ---
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
    
    // Save success message to session and REDIRECT to stop form resubmission
    $_SESSION['contract_success'] = "Global Default Settings Saved Successfully!";
    header("Location: default-contract.php");
    exit();
}

// Check for success message in session
$success_msg = '';
if (isset($_SESSION['contract_success'])) {
    $success_msg = $_SESSION['contract_success'];
    unset($_SESSION['contract_success']); // Remove so it only shows once
}
// ------------------------------------------------------------

// Fetch Global Settings
$stmt = $db->query("SELECT * FROM default_contract_settings WHERE id = 1");
$defaults = $stmt->fetch(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');

    /* A4 Document Styling inside the Admin Portal */
    .document-page.edit-document-page {
        width: 100%;
        max-width: 210mm;
        background-color: #ffffff;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        margin: 0 auto 40px auto;
        padding: 25mm 20mm;
        box-sizing: border-box;
        border-top: 5px solid #800020;
        font-family: 'Segoe UI', Roboto, sans-serif;
        color: #111111;
        border-radius: 4px;
    }

    h2.contract-heading {
        color: #800020;
        font-size: 13pt;
        border-bottom: 1px dashed #D4AF37;
        padding-bottom: 5px;
        margin-top: 25px;
        margin-bottom: 10px;
        text-transform: uppercase;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
    }

    /* Bank Inputs matching Contract Table vibe */
    .bank-details-box {
        background-color: #f8f9fa;
        border-left: 3px solid #D4AF37;
        padding: 20px;
        margin: 20px 0;
    }

    .bank-details-box label {
        color: #800020;
        font-weight: bold;
        font-size: 12px;
        text-transform: uppercase;
    }

    .bank-details-box .form-control {
        border: 1px solid #ced4da;
        border-radius: 0;
        font-size: 14px;
    }

    .bank-details-box .form-control:focus {
        border-color: #800020;
        box-shadow: none;
    }

    /* Summernote Editor Overrides */
    .note-editor.note-frame {
        border: 1px solid #e0e0e0 !important;
        box-shadow: none !important;
        border-radius: 4px;
        margin-bottom: 20px;
        background: white;
    }

    .note-editor .note-editing-area {
        font-family: 'Segoe UI', Roboto, sans-serif !important;
        font-size: 11pt !important;
        line-height: 1.6 !important;
    }

    /* Floating Save Button */
    .floating-save-btn {
        position: fixed;
        bottom: 40px;
        right: 40px;
        z-index: 1050;
        background-color: #800020;
        color: white;
        border: 2px solid #D4AF37;
        border-radius: 50px;
        padding: 12px 30px;
        font-size: 16px;
        font-weight: bold;
        font-family: 'Montserrat', sans-serif;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }

    .floating-save-btn:hover {
        transform: translateY(-5px) scale(1.05);
        background-color: #6a001a;
        color: white;
    }
</style>

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Global Contract Template</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Contract Template</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="POST" id="defaultContractForm">
        <div class="document-page edit-document-page">

            <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success fw-bold text-center"
                style="border-left: 5px solid #198754; background: #d1e7dd; color: #0f5132;">
                <?php echo $success_msg; ?>
            </div>
            <?php endif; ?>

            <div class="text-center mb-4">
                <h4 style="color: #800020; font-family: 'Montserrat', sans-serif; font-weight: 800;">GLOBAL DEFAULT
                    TEMPLATE</h4>
                <p style="color: #555555; font-weight: bold;">(All new contracts will inherit these terms)</p>
            </div>

            <h2 class="contract-heading">1. Objective of the Agreement</h2>
            <textarea name="objective"
                class="rich-editor"><?php echo htmlspecialchars($defaults['objective'] ?? ''); ?></textarea>

            <h2 class="contract-heading">2. Permitted Activities</h2>
            <textarea name="permitted"
                class="rich-editor"><?php echo htmlspecialchars($defaults['permitted_activities'] ?? ''); ?></textarea>

            <h2 class="contract-heading">4. Client Documentation Requirements</h2>
            <textarea name="docs"
                class="rich-editor"><?php echo htmlspecialchars($defaults['documentation'] ?? ''); ?></textarea>

            <h2 class="contract-heading">6. Payment Terms & Bank Details</h2>
            <textarea name="payment"
                class="rich-editor"><?php echo htmlspecialchars($defaults['payment_terms'] ?? ''); ?></textarea>

            <div class="bank-details-box">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name of Bank</label>
                        <input type="text" name="bank_name" class="form-control"
                            value="<?php echo htmlspecialchars($defaults['bank_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control"
                            value="<?php echo htmlspecialchars($defaults['account_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account IBAN Number</label>
                        <input type="text" name="iban_number" class="form-control"
                            value="<?php echo htmlspecialchars($defaults['iban_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">AC Name</label>
                        <input type="text" name="account_name" class="form-control"
                            value="<?php echo htmlspecialchars($defaults['account_name'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <h2 class="contract-heading">7. Client Obligations</h2>
            <textarea name="obligations"
                class="rich-editor"><?php echo htmlspecialchars($defaults['obligations'] ?? ''); ?></textarea>

            <h2 class="contract-heading">8. Timeline & Delays</h2>
            <div class="mb-3" style="width: 250px;">
                <label style="color: #800020; font-weight: bold; font-size: 12px; text-transform: uppercase;">Estimated
                    Timeline (Days)</label>
                <div class="input-group">
                    <input type="number" name="timeline_days" class="form-control"
                        value="<?php echo htmlspecialchars($defaults['timeline_days'] ?? 40); ?>">
                    <span class="input-group-text bg-white">Days</span>
                </div>
            </div>
            <textarea name="timeline_text"
                class="rich-editor"><?php echo htmlspecialchars($defaults['timeline_text'] ?? ''); ?></textarea>

        </div>
    </form>

    <button type="submit" form="defaultContractForm" class="floating-save-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path
                d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4.207a1 1 0 0 0-.293-.707l-2.5-2.5A1 1 0 0 0 10.5 1H2zm13 3.207V13a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1h8.5l3.5 3.5zM4 3h5v3H4V3z" />
        </svg>
        Save Global Template
    </button>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $(document).ready(function () {
        $('.rich-editor').summernote({
            tabsize: 2,
            height: 140,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
            ]
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>