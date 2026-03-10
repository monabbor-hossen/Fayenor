<?php
session_start();
require_once __DIR__ . '/../app/Config/Config.php';
require_once __DIR__ . '/../app/Config/Database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'client') {
    die("<h2 style='color:white; text-align:center;'>Unauthorized access.</h2>");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<h2 style='color:white; text-align:center;'>Error: No Client Selected.</h2>");
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
    $sql = "INSERT INTO client_contracts (client_id, objective, permitted_activities, documentation, payment_terms, obligations, timeline_days, timeline_text) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            objective=VALUES(objective), permitted_activities=VALUES(permitted_activities), documentation=VALUES(documentation), 
            payment_terms=VALUES(payment_terms), obligations=VALUES(obligations), timeline_days=VALUES(timeline_days), timeline_text=VALUES(timeline_text)";
    
    $stmtUpdate = $db->prepare($sql);
    $stmtUpdate->execute([
        $client_id, $_POST['objective'], $_POST['permitted'], $_POST['docs'], 
        $_POST['payment'], $_POST['obligations'], intval($_POST['timeline_days']), $_POST['timeline_text']
    ]);
    
    $success_msg = "Contract Terms Saved Successfully!";
}

// Default Contract Text
$defaults = [
    'objective' => '<p>The objective of this Agreement is to appoint Flyburj Travels & Tourism Company as a facilitator and consultant to assist the Client in obtaining a MISA Service License in the Kingdom of Saudi Arabia, in accordance with the regulations of the Ministry of Investment of Saudi Arabia (MISA).</p>',
    'permitted' => '<p>Service-based activities including consultancy, IT services, management support, marketing, training, professional advisory services, and other non-trading activities as approved by MISA</p>',
    'docs' => "<ul><li>Original Passport Copy</li><li>Passport Size Photograph</li></ul><p><em>The Client confirms that all documents provided are valid, accurate, and genuine.</em></p>",
    'payment' => "<ul><li>The Client shall pay 25% of the total service fees upon signing this Agreement, 25% upon issuance of the Investment License in Saudi Arabia, and the remaining 50% upon issuance of the Commercial Register.</li><li>If the client fails to fulfill the payment obligations, the company reserves the right to retain the official documents and papers until the full payment is settled and the final settlement is completed.</li><li>Should there be any changes to the government license fee, the agreement amount will be revised accordingly.</li><li>The contractual relationship with our company ends once the commercial register and investment license have been obtained and the agreed-upon services have been completed.</li></ul>",
    'obligations' => "<p>The Client agrees to:</p><ul><li>Provide required documents promptly</li><li>Pay government fees on time</li><li>Cooperate fully during the application process</li><li>Comply with all Saudi laws, regulations, and MISA requirements</li></ul><p>Any delay caused by incomplete documents or late payments shall not be the responsibility of the Service Provider.</p>",
    'timeline_days' => 40,
    'timeline_text' => "<p>The Service Provider shall not be held responsible for any delay caused by:</p><ul><li>Government system or server issues</li><li>Portal downtime or technical errors</li><li>Scheduled or unscheduled system maintenance</li></ul><p>Any delays arising from external or governmental processes shall not be considered a breach of this Agreement and will not affect the agreed service charges.</p>"
];

// Fetch Custom Text (if they edited it before)
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Contract - <?php echo htmlspecialchars($clientName); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        body { background-color: #1a1a1a; color: white; padding: 40px 0; font-family: 'Segoe UI', sans-serif; }
        .glass-panel { background: rgba(30, 10, 15, 0.95); border: 1px solid #D4AF37; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .note-editor { background: white; color: black; border-radius: 8px; overflow: hidden; }
        .text-gold { color: #D4AF37 !important; }
        .btn-gold { background-color: #D4AF37; color: #1a1a1a; font-weight: bold; border: none; }
        .btn-gold:hover { background-color: #b8962e; color: black; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editing Contract: <span class="text-gold"><?php echo htmlspecialchars($clientName); ?></span></h2>
            <div>
                <a href="../portal/clients.php" class="btn btn-outline-light me-2">Back to Portal</a>
                <a href="contract.php?id=<?php echo $client_id; ?>" target="_blank" class="btn btn-info">View PDF</a>
            </div>
        </div>

        <?php if(isset($success_msg)): ?>
            <div class="alert alert-success fw-bold"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <form method="POST" class="glass-panel">
            <h5 class="text-gold mb-3 border-bottom border-secondary pb-2">1. Objective of the Agreement</h5>
            <div class="mb-4"><textarea name="objective" class="rich-editor"><?php echo htmlspecialchars($v_obj); ?></textarea></div>

            <h5 class="text-gold mb-3 border-bottom border-secondary pb-2">2. Permitted Activities</h5>
            <div class="mb-4"><textarea name="permitted" class="rich-editor"><?php echo htmlspecialchars($v_per); ?></textarea></div>

            <h5 class="text-gold mb-3 border-bottom border-secondary pb-2">4. Client Documentation Requirements</h5>
            <div class="mb-4"><textarea name="docs" class="rich-editor"><?php echo htmlspecialchars($v_doc); ?></textarea></div>

            <h5 class="text-gold mb-3 border-bottom border-secondary pb-2">6. Payment Terms</h5>
            <div class="mb-4"><textarea name="payment" class="rich-editor"><?php echo htmlspecialchars($v_pay); ?></textarea></div>

            <h5 class="text-gold mb-3 border-bottom border-secondary pb-2">7. Client Obligations</h5>
            <div class="mb-4"><textarea name="obligations" class="rich-editor"><?php echo htmlspecialchars($v_obl); ?></textarea></div>

            <h5 class="text-gold mb-3 border-bottom border-secondary pb-2">8. Timeline & Delays</h5>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label text-white-50">Timeline Days:</label>
                    <input type="number" name="timeline_days" class="form-control" value="<?php echo htmlspecialchars($v_tdy); ?>">
                </div>
            </div>
            <div class="mb-4"><textarea name="timeline_text" class="rich-editor"><?php echo htmlspecialchars($v_ttx); ?></textarea></div>

            <button type="submit" class="btn btn-gold btn-lg w-100 mt-3 shadow-lg">Save Contract Terms</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
      $('.rich-editor').summernote({
        tabsize: 2,
        height: 150,
        toolbar: [
          ['style', ['bold', 'italic', 'underline', 'clear']],
          ['font', ['strikethrough', 'superscript', 'subscript']],
          ['para', ['ul', 'ol', 'paragraph']],
        ]
      });
    </script>
</body>
</html>