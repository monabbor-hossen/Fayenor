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

// --- HANDLE SIGNATURE DELETION ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_signature') {
    // Fetch the current filename to delete it from the server
    $stmt = $db->query("SELECT signature_image FROM default_contract_settings WHERE id = 1");
    $curr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!empty($curr['signature_image'])) {
        $filePath = '../assets/img/signatures/' . $curr['signature_image'];
        // Remove the physical file if it exists
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        // Remove from database
        $db->query("UPDATE default_contract_settings SET signature_image = NULL WHERE id = 1");
    }
    
    $_SESSION['contract_success'] = "Signature image removed successfully!";
    header("Location: default-contract.php");
    exit();
}

// 1. Fetch Global Settings First
$stmt = $db->query("SELECT * FROM default_contract_settings WHERE id = 1");
$defaults = $stmt->fetch(PDO::FETCH_ASSOC);

// --- HANDLE FORM SUBMIT WITH PRG (Post/Redirect/Get) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Set the old signature as default
    $signature_image = $defaults['signature_image'] ?? null;

    // Handle File Upload if a new image was selected
    if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/img/signatures/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Delete the old file before saving the new one
        if (!empty($signature_image) && file_exists($uploadDir . $signature_image)) {
            unlink($uploadDir . $signature_image);
        }

        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($_FILES['signature_file']['name']));
        $targetFilePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        
        if (in_array($fileType, ['jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $targetFilePath)) {
                $signature_image = $fileName;
            }
        }
    }

    $sql = "UPDATE default_contract_settings SET 
            service_provider=?, provider_email=?, signatory_name=?, signature_image=?,
            objective=?, permitted_activities=?, documentation=?, payment_terms=?, 
            obligations=?, timeline_days=?, timeline_text=?, 
            bank_name=?, account_number=?, iban_number=?, account_name=? 
            WHERE id=1";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $_POST['service_provider'], $_POST['provider_email'], $_POST['signatory_name'], $signature_image,
        $_POST['objective'], $_POST['permitted'], $_POST['docs'], 
        $_POST['payment'], $_POST['obligations'], intval($_POST['timeline_days']), $_POST['timeline_text'],
        $_POST['bank_name'], $_POST['account_number'], $_POST['iban_number'], $_POST['account_name']
    ]);
    
    $_SESSION['contract_success'] = "Global Default Settings Saved Successfully!";
    header("Location: default-contract.php");
    exit();
}

$success_msg = '';
if (isset($_SESSION['contract_success'])) {
    $success_msg = $_SESSION['contract_success'];
    unset($_SESSION['contract_success']);
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<link rel="stylesheet" href="../contract/contract.css">

<main id="main" class="main">
    
    <div class="pagetitle mb-4">
        <h1 style="color: #800020; font-weight: 800; font-family: 'Montserrat', sans-serif; letter-spacing: -0.5px;">Global Contract Template</h1>
        <p class="text-muted mt-2" style="font-size: 15px; font-weight: 500;">
            Manage default terms, provider details, signatures, and bank accounts for all new client contracts.
        </p>
    </div>

    <form method="POST" id="defaultContractForm" enctype="multipart/form-data">
        <div class="document-page edit-document-page">
            
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success fw-bold text-center shadow-sm" style="border-left: 5px solid #198754; background: #d1e7dd; color: #0f5132;">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mb-4">
                <h4 style="color: #800020; font-family: 'Montserrat', sans-serif; font-weight: 800;">MASTER TEMPLATE SETTINGS</h4>
            </div>

            <h2 class="contract-heading">General Details</h2>
            <div class="bank-details-box">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Service Provider Company Name</label>
                        <input type="text" name="service_provider" class="form-control" value="<?php echo htmlspecialchars($defaults['service_provider'] ?? 'Basmat Rooq Company Limited'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Provider Email</label>
                        <input type="email" name="provider_email" class="form-control" value="<?php echo htmlspecialchars($defaults['provider_email'] ?? 'info@flyburjco.com'); ?>">
                    </div>
                </div>
            </div>

            <h2 class="contract-heading">1. Objective of the Agreement</h2>
            <textarea name="objective" class="rich-editor"><?php echo htmlspecialchars($defaults['objective'] ?? ''); ?></textarea>

            <h2 class="contract-heading">2. Permitted Activities</h2>
            <textarea name="permitted" class="rich-editor"><?php echo htmlspecialchars($defaults['permitted_activities'] ?? ''); ?></textarea>

            <h2 class="contract-heading">4. Client Documentation Requirements</h2>
            <textarea name="docs" class="rich-editor"><?php echo htmlspecialchars($defaults['documentation'] ?? ''); ?></textarea>

            <h2 class="contract-heading">6. Payment Terms & Bank Details</h2>
            <textarea name="payment" class="rich-editor"><?php echo htmlspecialchars($defaults['payment_terms'] ?? ''); ?></textarea>
            
            <div class="bank-details-box">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name of Bank</label>
                        <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($defaults['bank_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control" value="<?php echo htmlspecialchars($defaults['account_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account IBAN Number</label>
                        <input type="text" name="iban_number" class="form-control" value="<?php echo htmlspecialchars($defaults['iban_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">AC Name</label>
                        <input type="text" name="account_name" class="form-control" value="<?php echo htmlspecialchars($defaults['account_name'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <h2 class="contract-heading">7. Client Obligations</h2>
            <textarea name="obligations" class="rich-editor"><?php echo htmlspecialchars($defaults['obligations'] ?? ''); ?></textarea>

            <h2 class="contract-heading">8. Timeline & Delays</h2>
            <div class="mb-3" style="width: 250px;">
                <label style="color: #800020; font-weight: bold; font-size: 12px; text-transform: uppercase;">Estimated Timeline (Days)</label>
                <div class="input-group">
                    <input type="number" name="timeline_days" class="form-control" value="<?php echo htmlspecialchars($defaults['timeline_days'] ?? 40); ?>">
                    <span class="input-group-text bg-white">Days</span>
                </div>
            </div>
            <textarea name="timeline_text" class="rich-editor"><?php echo htmlspecialchars($defaults['timeline_text'] ?? ''); ?></textarea>

            <h2 class="contract-heading">9. Acceptance & Signatures</h2>
            <div class="bank-details-box">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Signatory Name (Company Representative)</label>
                        <input type="text" name="signatory_name" class="form-control" value="<?php echo htmlspecialchars($defaults['signatory_name'] ?? 'Saifullah'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Upload New Signature (PNG/JPG)</label>
                        <input type="file" id="signatureFileInput" name="signature_file" class="form-control" accept="image/png, image/jpeg">
                        
                        <?php 
                            $hasSignature = !empty($defaults['signature_image']); 
                            $signatureUrl = $hasSignature ? '../assets/img/signatures/' . htmlspecialchars($defaults['signature_image']) : '';
                        ?>
                        
                        <div id="signaturePreviewBox" class="mt-3 p-3 bg-white border rounded justify-content-between align-items-center shadow-sm <?php echo $hasSignature ? 'd-flex' : 'd-none'; ?>">
                            <div>
                                <span id="signaturePreviewLabel" class="d-block small text-muted text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 1px;">
                                    <?php echo $hasSignature ? 'Current Signature:' : 'New Signature Preview:'; ?>
                                </span>
                                <img id="signaturePreviewImg" src="<?php echo $signatureUrl; ?>" alt="Signature" style="max-height: 50px; max-width: 100%;">
                            </div>
                            
                            <div id="signatureActionBtns">
                                <?php if($hasSignature): ?>
                                    <a href="default-contract.php?action=delete_signature" id="btnDeleteServer" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to permanently remove this signature?');">
                                        <i class="bi bi-trash"></i> Remove
                                    </a>
                                <?php endif; ?>
                                
                                <button type="button" id="btnCancelUpload" class="btn btn-sm btn-outline-warning" style="display: none;">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>

    <button type="submit" form="defaultContractForm" class="floating-save-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4.207a1 1 0 0 0-.293-.707l-2.5-2.5A1 1 0 0 0 10.5 1H2zm13 3.207V13a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1h8.5l3.5 3.5zM4 3h5v3H4V3z"/>
        </svg>
        Save Global Template
    </button>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="../contract/contract.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('signatureFileInput');
    const previewBox = document.getElementById('signaturePreviewBox');
    const previewImg = document.getElementById('signaturePreviewImg');
    const previewLabel = document.getElementById('signaturePreviewLabel');
    const btnDeleteServer = document.getElementById('btnDeleteServer');
    const btnCancelUpload = document.getElementById('btnCancelUpload');
    
    const originalImgSrc = "<?php echo $signatureUrl; ?>";
    const hasOriginal = "<?php echo $hasSignature; ?>" === "1";

    // 1. When user selects a file
    fileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewLabel.innerText = "New Signature Preview:";
                previewBox.classList.remove('d-none');
                previewBox.classList.add('d-flex');
                
                if (btnDeleteServer) btnDeleteServer.style.display = 'none';
                btnCancelUpload.style.display = 'inline-block';
            }
            reader.readAsDataURL(file);
        }
    });

    // 2. When user clicks "Cancel" on their local preview
    btnCancelUpload.addEventListener('click', function() {
        fileInput.value = ''; // Clear file input
        
        if (hasOriginal) {
            // Revert to old server image
            previewImg.src = originalImgSrc;
            previewLabel.innerText = "Current Signature:";
            if (btnDeleteServer) btnDeleteServer.style.display = 'inline-block';
            btnCancelUpload.style.display = 'none';
        } else {
            // No original image, hide the box completely
            previewBox.classList.remove('d-flex');
            previewBox.classList.add('d-none');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>