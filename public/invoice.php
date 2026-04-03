<?php
// public/invoice.php
require_once __DIR__ . '/../app/Config/Config.php';
require_once __DIR__ . '/../app/Config/Database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Security Check
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access. Please log in.");
}

$db = (new Database())->getConnection();

// Get Parameters
$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : 'all';

$is_client = in_array($_SESSION['role'], ['client', '3']);
$logged_in_account = intval($_SESSION['account_id'] ?? $_SESSION['user_id']);

$payments = [];
$total_amount = 0;

try {
    if ($payment_id > 0) {
        // --- SINGLE INVOICE MODE ---
        $title = "PAYMENT RECEIPT";
        $stmt = $db->prepare("SELECT p.*, c.company_name, c.email, c.phone_number, c.account_id FROM payments p JOIN clients c ON p.client_id = c.client_id WHERE p.id = ? OR p.payment_id = ?");
        $stmt->execute([$payment_id, $payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment || ($is_client && $payment['account_id'] != $logged_in_account)) {
            die("Invoice not found or access denied.");
        }
        $payments[] = $payment;
        $billed_to_name = $payment['company_name'];
        $billed_to_details = $payment['email'] . "<br>" . $payment['phone_number'];
        $invoice_date = date('F d, Y', strtotime($payment['payment_date']));
        $invoice_no = "INV-" . str_pad($payment['id'] ?? $payment['payment_id'], 6, "0", STR_PAD_LEFT);
        
    } else {
        // --- FULL STATEMENT MODE ---
        $title = "STATEMENT OF ACCOUNT";
        $invoice_date = date('F d, Y');
        $invoice_no = "STM-" . date('Ymd-Hi');
        
        $query = "SELECT p.*, c.company_name, c.email, c.phone_number, c.account_id FROM payments p JOIN clients c ON p.client_id = c.client_id WHERE 1=1";
        $params = [];
        
        // Security Filter
        if ($is_client) {
            $query .= " AND c.account_id = ?";
            $params[] = $logged_in_account;
        }
        
        // Dynamic Filters (Matches your billing page filters)
        if ($client_id !== 'all' && intval($client_id) > 0) {
            $query .= " AND p.client_id = ?";
            $params[] = intval($client_id);
        }
        if (!empty($_GET['start_date'])) {
            $query .= " AND p.payment_date >= ?";
            $params[] = $_GET['start_date'];
        }
        if (!empty($_GET['end_date'])) {
            $query .= " AND p.payment_date <= ?";
            $params[] = $_GET['end_date'];
        }
        
        $query .= " ORDER BY p.payment_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($payments) > 0) {
            $billed_to_name = ($client_id !== 'all') ? $payments[0]['company_name'] : "Multiple Projects";
            $billed_to_details = ($client_id !== 'all') ? $payments[0]['email'] . "<br>" . $payments[0]['phone_number'] : "Comprehensive Overview";
        } else {
            die("No transactions found for the selected filters.");
        }
    }
    
    // Calculate Total
    foreach($payments as $p) {
        if ($p['payment_status'] === 'Completed') {
            $total_amount += $p['amount'];
        }
    }
} catch (Exception $e) {
    die("Error generating invoice.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> | Fayenor</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap-icons.min.css">
    <style>
        body { background: #f4f6f9; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: 'Segoe UI', sans-serif; }
        .invoice-box { max-width: 850px; margin: 40px auto; background: #fff; padding: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-radius: 8px; border-top: 10px solid #023020; }
        .brand-color { color: #023020; }
        .text-secondary-brand { color: #B0C4DE; }
        .table-invoice th { background-color: #023020; color: #fff; padding: 12px; font-size: 0.9rem; text-transform: uppercase; border: none; }
        .table-invoice td { padding: 15px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .total-row { background-color: rgba(176, 196, 222, 0.15); font-weight: bold; }
        @media print {
            body { background: #fff; margin: 0; }
            .invoice-box { box-shadow: none; margin: 0; padding: 20px; border-top: 10px solid #023020 !important; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="text-end mt-4 no-print">
        <button onclick="window.print()" class="btn btn-lg text-white" style="background-color: #023020; border-radius: 50px; padding: 10px 30px;">
            <i class="bi bi-printer me-2"></i> Print / Save as PDF
        </button>
        <button onclick="window.close()" class="btn btn-lg btn-outline-secondary ms-2" style="border-radius: 50px; padding: 10px 30px;">Close</button>
    </div>

    <div class="invoice-box">
        <div class="row mb-5 d-flex align-items-center">
            <div class="col-6">
                <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Fayenor Logo" style="max-height: 80px; filter: invert(10%) sepia(85%) saturate(30%) hue-rotate(150deg) brightness(90%) contrast(90%);">
            </div>
            <div class="col-6 text-end">
                <h2 class="brand-color fw-bold mb-1"><?php echo $title; ?></h2>
                <div class="text-muted fw-bold"># <?php echo $invoice_no; ?></div>
                <div class="text-muted small">Date: <?php echo $invoice_date; ?></div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-6">
                <h6 class="text-muted small text-uppercase fw-bold mb-2">Billed To:</h6>
                <h5 class="brand-color fw-bold mb-1"><?php echo htmlspecialchars($billed_to_name); ?></h5>
                <div class="text-muted small lh-lg"><?php echo $billed_to_details; ?></div>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-muted small text-uppercase fw-bold mb-2">From:</h6>
                <h5 class="brand-color fw-bold mb-1">Fayenor Company Limited</h5>
                <div class="text-muted small lh-lg">Unaizah, Al-Qassim, KSA<br>info@fayenor.com</div>
            </div>
        </div>

        <table class="table table-invoice mb-5 w-100">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Project / Reference</th>
                    <th>Payment Method</th>
                    <th class="text-end">Amount (SAR)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pay): ?>
                <tr>
                    <td>
                        <div class="fw-bold brand-color"><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($pay['payment_status']); ?></div>
                    </td>
                    <td>
                        <div class="fw-bold"><?php echo htmlspecialchars($pay['company_name'] ?? 'Project Transaction'); ?></div>
                        <div class="small text-muted">Trans ID: <?php echo $pay['id'] ?? $pay['payment_id']; ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                    <td class="text-end fw-bold"><?php echo number_format($pay['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr class="total-row">
                    <td colspan="3" class="text-end py-4 brand-color fs-5">TOTAL PAID:</td>
                    <td class="text-end py-4 brand-color fs-4">SAR <?php echo number_format($total_amount, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="text-center text-muted small mt-5 pt-4 border-top">
            <p class="mb-1 fw-bold">Thank you for your business!</p>
            <p class="mb-0">This is a computer-generated document. No signature is required.</p>
        </div>
    </div>
</div>

<script>
    // Auto open print dialog when page loads
    window.onload = function() {
        setTimeout(function() { window.print(); }, 500);
    }
</script>
</body>
</html>