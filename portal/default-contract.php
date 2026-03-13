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

// ========================================================================
// 1. HANDLE SIGNATURE DELETION (Triggered by the global modal)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_signature') {
    $stmt = $db->query("SELECT signature_image FROM default_contract_settings WHERE id = 1");
    $curr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!empty($curr['signature_image'])) {
        $filePath = '../assets/img/signatures/' . $curr['signature_image'];
        if (file_exists($filePath)) {
            @unlink($filePath); 
        }
    }
    
    $db->exec("UPDATE default_contract_settings SET signature_image = NULL WHERE id = 1");
    
    $_SESSION['contract_success'] = "Signature image successfully removed!";
    header("Location: default-contract.php");
    exit();
}


// ========================================================================
// 2. HANDLE SAVING THE GLOBAL TEMPLATE
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    
    $stmt = $db->query("SELECT signature_image FROM default_contract_settings WHERE id = 1");
    $defaults = $stmt->fetch(PDO::FETCH_ASSOC);
    $signature_image = $defaults['signature_image'] ?? null;
    $maxFileSize = 2 * 1024 * 1024; // 2MB in bytes

    if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
        
        if ($_FILES['signature_file']['size'] > $maxFileSize) {
            $_SESSION['contract_error'] = "Upload Failed: The signature image must be less than 2MB.";
            header("Location: default-contract.php");
            exit();
        }

        $uploadDir = '../assets/img/signatures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!empty($signature_image) && file_exists($uploadDir . $signature_image)) {
            @unlink($uploadDir . $signature_image);
        }

        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($_FILES['signature_file']['name']));
        $targetFilePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        
        if (in_array($fileType, ['jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $targetFilePath)) {
                $signature_image = $fileName;
            }
        } else {
            $_SESSION['contract_error'] = "Upload Failed: Only JPG and PNG files are allowed.";
            header("Location: default-contract.php");
            exit();
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

// 3. Fetch Global Settings
$stmt = $db->query("SELECT * FROM default_contract_settings WHERE id = 1");
$defaults = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. Fetch All Clients for the Contract List
$clientsStmt = $db->query("SELECT client_id, company_name, client_name, contract_value FROM clients ORDER BY client_id DESC");
$allClients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);

// Check for session messages
$success_msg = '';
$error_msg = '';
if (isset($_SESSION['contract_success'])) {
    $success_msg = $_SESSION['contract_success'];
    unset($_SESSION['contract_success']);
}
if (isset($_SESSION['contract_error'])) {
    $error_msg = $_SESSION['contract_error'];
    unset($_SESSION['contract_error']);
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<link rel="stylesheet" href="../contract/contract.css">

<style>
    /* Beautiful Custom Tabs matching your Burgundy/Gold Theme */
    .rooq-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 25px;
    }
    .rooq-tabs .nav-link {
        color: #6c757d;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 14px;
        border: none;
        background: transparent;
        padding: 12px 20px;
        position: relative;
        transition: all 0.3s ease;
    }
    .rooq-tabs .nav-link:hover {
        color: #800020;
    }
    .rooq-tabs .nav-link.active {
        color: #800020;
    }
    .rooq-tabs .nav-link::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 3px;
        background-color: #D4AF37;
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    .rooq-tabs .nav-link.active::after {
        transform: scaleX(1);
    }
    
    /* Subtle Table Styling */
    .contract-table th {
        background-color: #800020;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }
    .contract-table td {
        vertical-align: middle;
        font-size: 14px;
    }
</style>

<main id="main" class="main">
    
    <div class="pagetitle mb-4">
        <h1 style="color: #800020; font-weight: 800; font-family: 'Montserrat', sans-serif; letter-spacing: -0.5px;">Contract Management</h1>
        <p class="text-muted mt-2" style="font-size: 15px; font-weight: 500;">
            Manage your global master template or view specific client contracts.
        </p>
    </div>

    <?php if(!empty($success_msg)): ?>
        <div class="alert alert-success fw-bold text-center shadow-sm" style="border-left: 5px solid #198754; background: #d1e7dd; color: #0f5132;">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($error_msg)): ?>
        <div class="alert alert-danger fw-bold text-center shadow-sm" style="border-left: 5px solid #dc3545; background: #f8d7da; color: #842029;">
            <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs rooq-tabs" id="contractTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="template-tab" data-bs-toggle="tab" data-bs-target="#template" type="button" role="tab" aria-controls="template" aria-selected="true">
                <i class="bi bi-file-earmark-text me-2"></i>Global Master Template
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab" aria-controls="list" aria-selected="false">
                <i class="bi bi-list-ul me-2"></i>Generated Contracts
            </button>
        </li>
    </ul>

    <div class="tab-content" id="contractTabsContent">
        
        <div class="tab-pane fade show active" id="template" role="tabpanel" aria-labelledby="template-tab">
            <form method="POST" id="defaultContractForm" enctype="multipart/form-data">
                <div class="document-page edit-document-page mt-0">
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
                                <label class="form-label">Upload New Signature <span class="text-danger">(Max 2MB, PNG/JPG)</span></label>
                                <input type="file" id="signatureFileInput" name="signature_file" class="form-control" accept="image/png, image/jpeg">
                                
                                <div id="fileSizeError" class="text-danger mt-1 fw-bold" style="display: none; font-size: 13px;">
                                    <i class="bi bi-x-circle-fill"></i> File is too large. Maximum size is 2MB.
                                </div>
                                
                                <?php 
                                    $hasSignature = !empty($defaults['signature_image']); 
                                    $signatureUrl = $hasSignature ? '../assets/img/signatures/' . htmlspecialchars($defaults['signature_image']) : '';
                                ?>
                                
                                <div id="signaturePreviewBox" 
                                     data-has-original="<?php echo $hasSignature ? '1' : '0'; ?>" 
                                     data-original-url="<?php echo $signatureUrl; ?>" 
                                     class="mt-3 p-3 bg-white border rounded justify-content-between align-items-center shadow-sm <?php echo $hasSignature ? 'd-flex' : 'd-none'; ?>">
                                    
                                    <div>
                                        <span id="signaturePreviewLabel" class="d-block small text-muted text-uppercase fw-bold mb-1" style="font-size: 10px; letter-spacing: 1px;">
                                            <?php echo $hasSignature ? 'Current Signature:' : 'New Signature Preview:'; ?>
                                        </span>
                                        <img id="signaturePreviewImg" src="<?php echo $signatureUrl; ?>?t=<?php echo time(); ?>" alt="Signature" style="max-height: 50px; max-width: 100%;">
                                    </div>
                                    
                                    <div id="signatureActionBtns">
                                        <?php if($hasSignature): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 opacity-50 hover-opacity-100" 
                                                    onclick="triggerFormModal('deleteSignatureForm', 'Are you absolutely sure you want to permanently remove this signature image?')">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
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
        </div>

        <div class="tab-pane fade" id="list" role="tabpanel" aria-labelledby="list-tab">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover contract-table align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Company Name</th>
                                    <th>Client Representative</th>
                                    <th>Contract Value</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($allClients)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No clients found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($allClients as $client): ?>
                                        <tr>
                                            <td class="fw-bold text-muted">#<?php echo $client['client_id']; ?></td>
                                            <td class="fw-bold" style="color: #800020;"><?php echo htmlspecialchars($client['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($client['client_name']); ?></td>
                                            <td>SAR <?php echo number_format($client['contract_value'], 2); ?></td>
                                            <td class="text-end">
                                                <a href="../contract/edit_contract.php?id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold" title="Customize for this specific client">
                                                    <i class="bi bi-pencil-square"></i> Customize
                                                </a>
                                                <a href="../contract/contract.php?id=<?php echo $client['client_id']; ?>" class="btn btn-sm rounded-pill fw-bold text-white ms-1" style="background-color: #D4AF37; border-color: #D4AF37;" title="View Final PDF">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i> View PDF
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <button type="submit" form="defaultContractForm" id="floatingSaveBtn" class="floating-save-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4.207a1 1 0 0 0-.293-.707l-2.5-2.5A1 1 0 0 0 10.5 1H2zm13 3.207V13a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1h8.5l3.5 3.5zM4 3h5v3H4V3z"/>
        </svg>
        Save Global Template
    </button>

    <form id="deleteSignatureForm" method="POST" action="default-contract.php" style="display: none;">
        <input type="hidden" name="action" value="delete_signature">
    </form>

</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="../contract/contract.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const templateTabBtn = document.getElementById('template-tab');
    const listTabBtn = document.getElementById('list-tab');
    const floatingSaveBtn = document.getElementById('floatingSaveBtn');

    if (templateTabBtn && listTabBtn && floatingSaveBtn) {
        // When List tab is clicked, hide button
        listTabBtn.addEventListener('shown.bs.tab', function () {
            floatingSaveBtn.style.display = 'none';
        });
        
        // When Template tab is clicked, show button
        templateTabBtn.addEventListener('shown.bs.tab', function () {
            floatingSaveBtn.style.display = 'flex'; // or 'block' depending on your CSS
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>