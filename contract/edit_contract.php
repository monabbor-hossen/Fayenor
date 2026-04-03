<?php
session_start();
require_once __DIR__ . '/../app/Config/Config.php';
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Helpers/Security.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'client') {
    die("<h2 style='color:white; text-align:center; font-family:sans-serif; margin-top:50px;'>Unauthorized access.</h2>");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<h2 style='color:white; text-align:center; font-family:sans-serif; margin-top:50px;'>Error: No Client Selected.</h2>");
}

$client_id = intval($_GET['id']);
$db = (new Database())->getConnection();

// Fetch Client Name for header
$stmt = $db->prepare("SELECT client_name, company_name FROM clients WHERE client_id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$client) die("Client not found.");
$clientName = $client['client_name'] ?? $client['company_name'];

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO client_contracts (client_id, objective, permitted_activities, documentation, payment_terms, obligations, timeline_days, timeline_text, bank_name, account_number, iban_number, account_name, additional_scope) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            objective=VALUES(objective), permitted_activities=VALUES(permitted_activities), documentation=VALUES(documentation), 
            payment_terms=VALUES(payment_terms), obligations=VALUES(obligations), timeline_days=VALUES(timeline_days), timeline_text=VALUES(timeline_text),
            bank_name=VALUES(bank_name), account_number=VALUES(account_number), iban_number=VALUES(iban_number), account_name=VALUES(account_name), additional_scope=VALUES(additional_scope)";
    
    $stmtUpdate = $db->prepare($sql);
    $stmtUpdate->execute([
        $client_id, Security::clean($_POST['objective']), Security::clean($_POST['permitted']), Security::clean($_POST['docs']), 
        Security::clean($_POST['payment']), Security::clean($_POST['obligations']), intval($_POST['timeline_days']), Security::clean($_POST['timeline_text']),
        Security::clean($_POST['bank_name']), Security::clean($_POST['account_number']), Security::clean($_POST['iban_number']), Security::clean($_POST['account_name']), Security::clean($_POST['additional_scope'])
    ]);
    
    $_SESSION['contract_success'] = "Contract Terms Saved Successfully!";
    $_SESSION['close_tab'] = true;
    header("Location: edit_contract.php?id=" . $client_id);
    exit();
}

// Check if there is a success message in the session from a recent save
$success_msg = '';
if (isset($_SESSION['contract_success'])) {
    $success_msg = $_SESSION['contract_success'];
    unset($_SESSION['contract_success']);
}

// FETCH GLOBAL DEFAULT TEXT
$stmtDef = $db->query("SELECT * FROM default_contract_settings WHERE id = 1");
$defaults = $stmtDef->fetch(PDO::FETCH_ASSOC);

// Fetch Custom Text (If this client was already specifically edited)
$stmtCheck = $db->prepare("SELECT * FROM client_contracts WHERE client_id = ?");
$stmtCheck->execute([$client_id]);
$custom = $stmtCheck->fetch(PDO::FETCH_ASSOC);

// MAP DATA (Prioritize Custom -> fallback to Default)
$v_obj = $custom['objective'] ?? $defaults['objective'];
$v_per = $custom['permitted_activities'] ?? $defaults['permitted_activities'];
$v_doc = $custom['documentation'] ?? $defaults['documentation'];
$v_pay = $custom['payment_terms'] ?? $defaults['payment_terms'];
$v_obl = $custom['obligations'] ?? $defaults['obligations'];
$v_tdy = $custom['timeline_days'] ?? $defaults['timeline_days'];
$v_ttx = $custom['timeline_text'] ?? $defaults['timeline_text'];

$v_bnk = $custom['bank_name'] ?? $defaults['bank_name'];
$v_acc = $custom['account_number'] ?? $defaults['account_number'];
$v_ibn = $custom['iban_number'] ?? $defaults['iban_number'];
$v_acn = $custom['account_name'] ?? $defaults['account_name'];

$v_add_scope = $custom['additional_scope'] ?? '';

// --- FETCH WORKFLOW TO SHOW AS READ-ONLY ---
$stmtWf = $db->prepare("SELECT * FROM workflow_tracking WHERE client_id = ? LIMIT 1");
$stmtWf->execute([$client_id]);
$clientWf = $stmtWf->fetch(PDO::FETCH_ASSOC);
$wfList = [];
if (!empty($clientWf['hire_foreign_company']) && $clientWf['hire_foreign_company'] !== 'Not Required') { $wfList[] = "Arrangement of a Foreign Company (as required by MISA)"; }
if (!empty($clientWf['misa_application']) && $clientWf['misa_application'] !== 'Not Required') { $wfList[] = "Application and approval of MISA Service License"; }
if (!empty($clientWf['sbc_application']) && $clientWf['sbc_application'] !== 'Not Required') { $wfList[] = "SBC Application & Registration"; }
if (!empty($clientWf['article_association']) && $clientWf['article_association'] !== 'Not Required') { $wfList[] = "Preparation of Articles of Association"; }
if (!empty($clientWf['qiwa']) && $clientWf['qiwa'] !== 'Not Required') { $wfList[] = "Qiwa Registration"; }
if (!empty($clientWf['muqeem']) && $clientWf['muqeem'] !== 'Not Required') { $wfList[] = "Muqeem Registration"; }
if (!empty($clientWf['gosi']) && $clientWf['gosi'] !== 'Not Required') { $wfList[] = "GOSI Registration"; }
if (!empty($clientWf['chamber_commerce']) && $clientWf['chamber_commerce'] !== 'Not Required') { $wfList[] = "Chamber of Commerce Registration"; }

// Define page title and load header
$pageTitle = "Edit Contract - " . htmlspecialchars($clientName);
require_once 'header.php';
?>

    <div class="control-bar">
        <div>
            <h2>Edit Contract Terms</h2>
            <div class="text-white-50 mt-1">Client: <span class="client-name"><?php echo htmlspecialchars($clientName); ?></span></div>
        </div>
        <div>
            <a href="../portal/clients" onclick="rooqSmartBack('../portal/clients'); return false;" class="btn btn-outline-light me-2 fw-bold">Cancel</a>
            <a href="contract?id=<?php echo $client_id; ?>" target="_blank" class="btn fw-bold" style="background-color: var(--theme-accent); color: #111;">Preview PDF</a>
        </div>
    </div>

    <form method="POST" id="editContractForm">
        
        <div class="document-page edit-document-page">
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success fw-bold text-center" style="border-left: 5px solid #198754;">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mb-4">
                <h4 style="color: var(--theme-primary); font-family: 'Montserrat', sans-serif; font-weight: 800;">SERVICE LICENSE AGREEMENT</h4>
                <p style="color: var(--text-muted); font-weight: bold;">(Editable Terms)</p>
            </div>

            <h2 class="contract-heading">1. Objective of the Agreement</h2>
            <textarea name="objective" class="rich-editor"><?php echo htmlspecialchars($v_obj); ?></textarea>

            <h2 class="contract-heading">2. Permitted Activities</h2>
            <textarea name="permitted" class="rich-editor"><?php echo htmlspecialchars($v_per); ?></textarea>

            <h2 class="contract-heading">3. Scope of Services</h2>
            <div class="mb-3 p-3" style="background: #f8f9fa; border: 1px solid #ced4da; border-radius: 4px;">
                <p style="color: var(--theme-primary); font-weight: bold; font-size: 12px; text-transform: uppercase; margin-bottom: 8px;">Included from Workflow (Read-Only)</p>
                <ol style="margin-bottom: 0; color: var(--text-dark); font-size: 14px;">
                    <?php foreach($wfList as $item): ?>
                        <li><?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="mb-4">
                <label style="color: var(--theme-primary); font-weight: bold; font-size: 12px; text-transform: uppercase;">Additional Services (Type ONE per line)</label>
                <textarea name="additional_scope" class="form-control" rows="4" placeholder="Example:&#10;Translation of company documents&#10;Opening local bank account" style="border: 1px solid #ced4da; border-radius: 0; font-size: 14px;"><?php echo htmlspecialchars($v_add_scope); ?></textarea>
            </div>

            <h2 class="contract-heading">4. Client Documentation Requirements</h2>
            <textarea name="docs" class="rich-editor"><?php echo htmlspecialchars($v_doc); ?></textarea>

            <h2 class="contract-heading">6. Payment Terms & Bank Details</h2>
            <textarea name="payment" class="rich-editor"><?php echo htmlspecialchars($v_pay); ?></textarea>
            
            <div class="bank-details-box">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name of Bank</label>
                        <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($v_bnk); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control" value="<?php echo htmlspecialchars($v_acc); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account IBAN Number</label>
                        <input type="text" name="iban_number" class="form-control" value="<?php echo htmlspecialchars($v_ibn); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">AC Name</label>
                        <input type="text" name="account_name" class="form-control" value="<?php echo htmlspecialchars($v_acn); ?>">
                    </div>
                </div>
            </div>

            <h2 class="contract-heading">7. Client Obligations</h2>
            <textarea name="obligations" class="rich-editor"><?php echo htmlspecialchars($v_obl); ?></textarea>

            <h2 class="contract-heading">8. Timeline & Delays</h2>
            <div class="mb-3" style="width: 200px;">
                <label style="color: var(--theme-primary); font-weight: bold; font-size: 12px; text-transform: uppercase;">Estimated Timeline (Days)</label>
                <div class="input-group">
                    <input type="number" name="timeline_days" class="form-control" value="<?php echo htmlspecialchars($v_tdy); ?>">
                    <span class="input-group-text bg-white">Days</span>
                </div>
            </div>
            <textarea name="timeline_text" class="rich-editor"><?php echo htmlspecialchars($v_ttx); ?></textarea>

        </div>
    </form>

    <button type="submit" form="editContractForm" class="floating-save-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4.207a1 1 0 0 0-.293-.707l-2.5-2.5A1 1 0 0 0 10.5 1H2zm13 3.207V13a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1h8.5l3.5 3.5zM4 3h5v3H4V3z"/>
        </svg>
        Save Contract
    </button>

<?php require_once 'footer.php'; ?>