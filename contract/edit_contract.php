<?php
session_start();
require_once __DIR__ . '/../app/Config/Config.php';
require_once __DIR__ . '/../app/Config/Database.php';

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
    $sql = "INSERT INTO client_contracts (client_id, objective, permitted_activities, documentation, payment_terms, obligations, timeline_days, timeline_text, bank_name, account_number, iban_number, account_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            objective=VALUES(objective), permitted_activities=VALUES(permitted_activities), documentation=VALUES(documentation), 
            payment_terms=VALUES(payment_terms), obligations=VALUES(obligations), timeline_days=VALUES(timeline_days), timeline_text=VALUES(timeline_text),
            bank_name=VALUES(bank_name), account_number=VALUES(account_number), iban_number=VALUES(iban_number), account_name=VALUES(account_name)";
    
    $stmtUpdate = $db->prepare($sql);
    $stmtUpdate->execute([
        $client_id, $_POST['objective'], $_POST['permitted'], $_POST['docs'], 
        $_POST['payment'], $_POST['obligations'], intval($_POST['timeline_days']), $_POST['timeline_text'],
        $_POST['bank_name'], $_POST['account_number'], $_POST['iban_number'], $_POST['account_name']
    ]);
    
    // --- THE FIX: Save message to session and REDIRECT ---
    $_SESSION['contract_success'] = "Contract Terms Saved Successfully!";
    header("Location: edit_contract.php?id=" . $client_id);
    exit(); // Stop the script here so the redirect happens instantly
}

// Check if there is a success message in the session from a recent save
$success_msg = '';
if (isset($_SESSION['contract_success'])) {
    $success_msg = $_SESSION['contract_success'];
    unset($_SESSION['contract_success']); // Delete it so it only shows once!
}

// Default Contract Text
$defaults = [
    'objective' => '<p>The objective of this Agreement is to appoint Flyburj Travels & Tourism Company as a facilitator and consultant to assist the Client in obtaining a MISA Service License in the Kingdom of Saudi Arabia, in accordance with the regulations of the Ministry of Investment of Saudi Arabia (MISA).</p>',
    'permitted' => '<p>Service-based activities including consultancy, IT services, management support, marketing, training, professional advisory services, and other non-trading activities as approved by MISA</p>',
    'docs' => "<ul><li>Original Passport Copy</li><li>Passport Size Photograph</li></ul><p><em>The Client confirms that all documents provided are valid, accurate, and genuine.</em></p>",
    'payment' => "<ul><li>The Client shall pay 25% of the total service fees upon signing this Agreement, 25% upon issuance of the Investment License in Saudi Arabia, and the remaining 50% upon issuance of the Commercial Register.</li><li>If the client fails to fulfill the payment obligations, the company reserves the right to retain the official documents and papers until the full payment is settled and the final settlement is completed.</li><li>Should there be any changes to the government license fee, the agreement amount will be revised accordingly.</li><li>The contractual relationship with our company ends once the commercial register and investment license have been obtained and the agreed-upon services have been completed.</li></ul>",
    'obligations' => "<p>The Client agrees to:</p><ul><li>Provide required documents promptly</li><li>Pay government fees on time</li><li>Cooperate fully during the application process</li><li>Comply with all Saudi laws, regulations, and MISA requirements</li></ul><p>Any delay caused by incomplete documents or late payments shall not be the responsibility of the Service Provider.</p>",
    'timeline_days' => 40,
    'timeline_text' => "<p>The Service Provider shall not be held responsible for any delay caused by:</p><ul><li>Government system or server issues</li><li>Portal downtime or technical errors</li><li>Scheduled or unscheduled system maintenance</li></ul><p>Any delays arising from external or governmental processes shall not be considered a breach of this Agreement and will not affect the agreed service charges.</p>",
    'bank_name' => 'SAUDI NATIONAL BANK',
    'account_number' => '38300000264001',
    'iban_number' => 'SA5010000038300000264001',
    'account_name' => 'Flyburj Travel and Tourism Company'
];

// Fetch Custom Text
$stmtCheck = $db->prepare("SELECT * FROM client_contracts WHERE client_id = ?");
$stmtCheck->execute([$client_id]);
$custom = $stmtCheck->fetch(PDO::FETCH_ASSOC);

$v_obj = $custom['objective'] ?? $defaults['objective'];
$v_per = $custom['permitted_activities'] ?? $defaults['permitted'];
$v_doc = $custom['documentation'] ?? $defaults['docs'];
$v_pay = $custom['payment_terms'] ?? $defaults['payment'];
$v_obl = $custom['obligations'] ?? $defaults['obligations'];
$v_tdy = $custom['timeline_days'] ?? $defaults['timeline_days'];
$v_ttx = $custom['timeline_text'] ?? $defaults['timeline_text'];

$v_bnk = $custom['bank_name'] ?? $defaults['bank_name'];
$v_acc = $custom['account_number'] ?? $defaults['account_number'];
$v_ibn = $custom['iban_number'] ?? $defaults['iban_number'];
$v_acn = $custom['account_name'] ?? $defaults['account_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/img/favicon-32x32.png" type="image/x-icon" />
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/img/favicon-32x32.png" type="image/x-icon" />
    <title>Edit Contract - <?php echo htmlspecialchars($clientName); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap');

        :root {
            --theme-primary: #800020;
            --theme-accent: #D4AF37;
            --text-dark: #111111;
            --text-muted: #555555;
            --bg-offwhite: #ffffff;
        }

        body { 
            background-color: #2b2b2b; /* Match contract.php dark background */
            color: var(--text-dark); 
            padding: 40px 0; 
            font-family: 'Segoe UI', Roboto, sans-serif; 
        }

        /* Top Control Bar */
        .control-bar {
            width: 210mm;
            max-width: 100%;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .control-bar h2 {
            color: white;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            margin: 0;
            font-size: 24px;
        }

        .control-bar .client-name {
            color: var(--theme-accent);
        }

        /* The A4 Document Container */
        .document-page {
            width: 210mm;
            max-width: 100%;
            background-color: white;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            margin: 0 auto;
            padding: 25mm 20mm; /* Realistic document padding */
            box-sizing: border-box;
            border-top: 5px solid var(--theme-primary);
        }

        /* Mimicking the Contract Headers EXACTLY */
        h2.contract-heading {
            color: var(--theme-primary);
            font-size: 13pt;
            border-bottom: 1px dashed var(--theme-accent);
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        /* Summernote Overrides to blend into the document */
        .note-editor.note-frame {
            border: 1px solid #e0e0e0 !important;
            box-shadow: none !important;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .note-editor .note-editing-area {
            font-family: 'Segoe UI', Roboto, sans-serif !important;
            font-size: 11pt !important;
            line-height: 1.6 !important;
        }

        /* Bank Inputs matching Contract Table vibe */
        .bank-details-box {
            background-color: #f8f9fa;
            border-left: 3px solid var(--theme-accent);
            padding: 20px;
            margin: 20px 0;
        }
        .bank-details-box label {
            color: var(--theme-primary);
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
            border-color: var(--theme-primary);
            box-shadow: none;
        }

        /* Floating Save Button */
        .floating-save-btn {
            position: fixed;
            bottom: 40px;
            right: 40px;
            z-index: 1050;
            background-color: var(--theme-primary);
            color: white;
            border: 2px solid var(--theme-accent);
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
</head>
<body>

    <div class="control-bar">
        <div>
            <h2>Edit Contract Terms</h2>
            <div class="text-white-50 mt-1">Client: <span class="client-name"><?php echo htmlspecialchars($clientName); ?></span></div>
        </div>
        <div>
            <a href="../portal/clients.php" class="btn btn-outline-light me-2 fw-bold">Cancel</a>
            <a href="contract.php?id=<?php echo $client_id; ?>" target="_blank" class="btn fw-bold" style="background-color: var(--theme-accent); color: #111;">Preview PDF</a>
        </div>
    </div>

    <form method="POST" id="editContractForm">
        
        <div class="document-page">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
      $('.rich-editor').summernote({
        tabsize: 2,
        height: 140,
        toolbar: [
          ['style', ['bold', 'italic', 'underline', 'clear']],
          ['para', ['ul', 'ol', 'paragraph']],
        ]
      });
    </script>
</body>
</html>