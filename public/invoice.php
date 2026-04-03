<?php
// public/invoice.php
require_once __DIR__ . '/../app/Config/Config.php';
require_once __DIR__ . '/../app/Config/Database.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();

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
$mode = 'single';

try {
    if ($payment_id > 0) {
        // --- SINGLE INVOICE MODE ---
        // NOTE: payments PK is `payment_id`, there is no `id` column.
        $mode = 'single';
        $stmt = $db->prepare("
            SELECT p.payment_id, p.client_id, p.amount, p.payment_method,
                   p.payment_status, p.payment_date, p.notes,
                   c.company_name, c.client_name, c.email, c.phone_number, c.account_id
            FROM payments p
            JOIN clients c ON p.client_id = c.client_id
            WHERE p.payment_id = ?
        ");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            die("<div style='font-family:sans-serif;padding:40px;text-align:center;'><h3>Invoice Not Found</h3><p>No payment record found for ID #" . $payment_id . ".</p></div>");
        }
        if ($is_client && $payment['account_id'] != $logged_in_account) {
            die("<div style='font-family:sans-serif;padding:40px;text-align:center;'><h3>Access Denied</h3><p>You do not have permission to view this invoice.</p></div>");
        }

        $payments[] = $payment;
        $billed_to_name = $payment['company_name'];
        $billed_to_contact = !empty($payment['client_name']) ? $payment['client_name'] : '';
        $billed_to_email = $payment['email'] ?? '';
        $billed_to_phone = $payment['phone_number'] ?? '';
        $invoice_date = date('F d, Y', strtotime($payment['payment_date']));
        $invoice_no = "INV-" . str_pad($payment['payment_id'], 6, "0", STR_PAD_LEFT);
        $title = "PAYMENT RECEIPT";

    } else {
        // --- FULL STATEMENT MODE ---
        $mode = 'statement';
        $title = "STATEMENT OF ACCOUNT";
        $invoice_date = date('F d, Y');
        $invoice_no = "STM-" . date('Ymd-Hi');

        $query = "
            SELECT p.payment_id, p.client_id, p.amount, p.payment_method,
                   p.payment_status, p.payment_date, p.notes,
                   c.company_name, c.client_name, c.email, c.phone_number, c.account_id
            FROM payments p
            JOIN clients c ON p.client_id = c.client_id
            WHERE 1=1
        ";
        $params = [];

        // Security: clients only see their own data
        if ($is_client) {
            $query .= " AND c.account_id = ?";
            $params[] = $logged_in_account;
        }
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

        if (count($payments) === 0) {
            die("<div style='font-family:sans-serif;padding:40px;text-align:center;'><h3>No Transactions Found</h3><p>No records match the selected filters.</p></div>");
        }

        // Always use the first (or only) client's real data for the Billed To section
        $billed_to_name = $payments[0]['company_name'];
        $billed_to_contact = $payments[0]['client_name'] ?? '';
        $billed_to_email = $payments[0]['email'] ?? '';
        $billed_to_phone = $payments[0]['phone_number'] ?? '';

        // If multiple different clients are present, show a generic header
        $client_ids = array_unique(array_column($payments, 'client_id'));
        if (count($client_ids) > 1) {
            $billed_to_name = "Multiple Clients";
            $billed_to_contact = "";
            $billed_to_email = "";
            $billed_to_phone = "";
        }
    }

    // Calculate total (completed payments only)
    foreach ($payments as $p) {
        if ($p['payment_status'] === 'Completed') {
            $total_amount += floatval($p['amount']);
        }
    }

} catch (Exception $e) {
    die("<div style='font-family:sans-serif;padding:40px;text-align:center;'><h3>Error</h3><p>Could not generate invoice. Please try again.</p><small style='color:#999'>" . htmlspecialchars($e->getMessage()) . "</small></div>");
}

// Status badge helper
function statusBadge($status)
{
    if ($status === 'Completed')
        return '<span class="status-badge status-completed">✓ Completed</span>';
    if ($status === 'Pending')
        return '<span class="status-badge status-pending">⏳ Pending</span>';
    return '<span class="status-badge status-other">' . htmlspecialchars($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $invoice_no; ?> | Fayenor Invoice</title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/img/favicon.svg" type="image/svg+xml" />
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/img/favicon.svg" type="image/svg+xml" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/theme.css">
</head>


<body class="invoice-page-body">

    <!-- Toolbar -->
    <div class="inv-toolbar">
        <div class="inv-toolbar-brand">
            <img src="<?php echo BASE_URL; ?>assets/img/logo.png" height="50" alt="Logo"
                 style="filter: brightness(0) invert(1);">
        </div>
        <div class="inv-toolbar-actions">
            <button class="inv-btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
            <button class="inv-btn-close" onclick="window.close()">✕ Close</button>
        </div>
    </div>

    <div class="inv-page-wrap">
        <div class="invoice">

            <!-- HEADER ─────────────────────────────────────────────────── -->
            <div class="invoice-header">
                <div>
                    <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Fayenor"
                        style="max-height:60px; filter: brightness(0) invert(1);">
                </div>
                <div class="invoice-meta">
                    <div class="invoice-type"><?php echo $title; ?></div>
                    <div class="invoice-number"><?php echo $invoice_no; ?></div>
                    <div class="invoice-date-lbl"><?php echo $invoice_date; ?></div>
                </div>
            </div>

            <!-- BODY ───────────────────────────────────────────────────── -->
            <div class="invoice-body">

                <!-- Parties -->
                <div class="parties">
                    <!-- LEFT: Billed To (client details) -->
                    <div>
                        <div class="party-label">Billed To</div>
                        <div class="party-name"><?php echo htmlspecialchars($billed_to_name); ?></div>
                        <?php if (!empty($billed_to_contact)): ?>
                            <div class="party-contact"><?php echo htmlspecialchars($billed_to_contact); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($billed_to_email) && $billed_to_email !== 'N/A'): ?>
                            <div class="party-detail"><?php echo htmlspecialchars($billed_to_email); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($billed_to_phone) && $billed_to_phone !== 'N/A'): ?>
                            <div class="party-detail"><?php echo htmlspecialchars($billed_to_phone); ?></div>
                        <?php endif; ?>
                    </div>
                    <!-- RIGHT: Issued By -->
                    <div class="party-from">
                        <div class="party-label">Issued By</div>
                        <div class="party-name">Fayenor Company Limited</div>
                        <div class="party-detail">
                            Unaizah, Al-Qassim<br>
                            Kingdom of Saudi Arabia<br>
                            info@fayenor.com
                        </div>
                    </div>
                </div>

                <div class="inv-divider"></div>

                <?php if ($mode === 'single' && count($payments) === 1):
                    $pay = $payments[0];
                    ?>

                    <!-- Single-payment highlight ──── -->
                    <div class="single-highlight">
                        <div>
                            <div class="hf-label">Transaction ID</div>
                            <div class="hf-value">#<?php echo str_pad($pay['payment_id'], 6, "0", STR_PAD_LEFT); ?></div>
                        </div>
                        <div>
                            <div class="hf-label">Payment Date</div>
                            <div class="hf-value"><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></div>
                        </div>
                        <div>
                            <div class="hf-label">Payment Method</div>
                            <div class="hf-value"><?php echo htmlspecialchars($pay['payment_method']); ?></div>
                        </div>
                        <div>
                            <div class="hf-label">Amount Paid</div>
                            <div class="hf-value amount-big">SAR <?php echo number_format($pay['amount'], 2); ?></div>
                        </div>
                    </div>

                    <?php if (!empty($pay['notes'])): ?>
                        <div class="notes-box">
                            <div class="notes-label">Notes / Description</div>
                            <div class="notes-text"><?php echo htmlspecialchars($pay['notes']); ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Line item table (single) -->
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width:22%">Date</th>
                                <th style="width:40%">Project / Company</th>
                                <th style="width:20%">Method</th>
                                <th style="width:18%">Amount (SAR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($pay['company_name']); ?>
                                    <div class="ref-sub">Ref:
                                        #<?php echo str_pad($pay['payment_id'], 6, "0", STR_PAD_LEFT); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                <td class="text-right"><?php echo number_format($pay['amount'], 2); ?></td>
                            </tr>
                        </tbody>
                    </table>

                <?php else: ?>

                    <!-- Multi-row statement table -->
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width:20%">Date</th>
                                <th style="width:36%">Project / Company</th>
                                <th style="width:24%">Method</th>
                                <th style="width:20%">Amount (SAR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($pay['company_name']); ?>
                                        <div class="ref-sub">Ref:
                                            #<?php echo str_pad($pay['payment_id'], 6, "0", STR_PAD_LEFT); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                    <td class="text-right"><?php echo number_format($pay['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

                <!-- Total -->
                <div class="total-bar">
                    <div class="total-label">Total Amount Paid (Completed)</div>
                    <div class="total-amount">SAR <?php echo number_format($total_amount, 2); ?></div>
                </div>

            </div><!-- /.invoice-body -->

            <!-- FOOTER ─────────────────────────────────────────────────── -->
            <div class="invoice-footer">
                <p><strong>Thank you for your business!</strong></p>
                <p>This is a computer-generated document. No signature is required.</p>
                <p>Fayenor Company Limited · Unaizah, Al-Qassim, KSA · info@fayenor.com</p>
            </div>

        </div><!-- /.invoice -->
    </div><!-- /.inv-page-wrap -->

<?php if (!empty($_GET['download'])): ?>
<script>
    // Auto-open print/save-PDF dialog when download=1 is set
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 400);
    });
</script>
<?php endif; ?>

</body>

</html>