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
$client_id  = isset($_GET['client_id']) ? $_GET['client_id'] : 'all';

$is_client         = in_array($_SESSION['role'], ['client', '3']);
$logged_in_account = intval($_SESSION['account_id'] ?? $_SESSION['user_id']);

$payments     = [];
$total_amount = 0;
$mode         = 'single';

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

        $payments[]      = $payment;
        $billed_to_name  = $payment['company_name'];
        $billed_to_contact = !empty($payment['client_name']) ? $payment['client_name'] : '';
        $billed_to_email = $payment['email']        ?? '';
        $billed_to_phone = $payment['phone_number'] ?? '';
        $invoice_date    = date('F d, Y', strtotime($payment['payment_date']));
        $invoice_no      = "INV-" . str_pad($payment['payment_id'], 6, "0", STR_PAD_LEFT);
        $title           = "PAYMENT RECEIPT";

    } else {
        // --- FULL STATEMENT MODE ---
        $mode         = 'statement';
        $title        = "STATEMENT OF ACCOUNT";
        $invoice_date = date('F d, Y');
        $invoice_no   = "STM-" . date('Ymd-Hi');

        $query  = "
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
            $query   .= " AND c.account_id = ?";
            $params[] = $logged_in_account;
        }
        if ($client_id !== 'all' && intval($client_id) > 0) {
            $query   .= " AND p.client_id = ?";
            $params[] = intval($client_id);
        }
        if (!empty($_GET['start_date'])) {
            $query   .= " AND p.payment_date >= ?";
            $params[] = $_GET['start_date'];
        }
        if (!empty($_GET['end_date'])) {
            $query   .= " AND p.payment_date <= ?";
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
        $billed_to_name    = $payments[0]['company_name'];
        $billed_to_contact = $payments[0]['client_name'] ?? '';
        $billed_to_email   = $payments[0]['email']        ?? '';
        $billed_to_phone   = $payments[0]['phone_number'] ?? '';

        // If multiple different clients are present, show a generic header
        $client_ids = array_unique(array_column($payments, 'client_id'));
        if (count($client_ids) > 1) {
            $billed_to_name    = "Multiple Clients";
            $billed_to_contact = "";
            $billed_to_email   = "";
            $billed_to_phone   = "";
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
function statusBadge($status) {
    if ($status === 'Completed') return '<span class="status-badge status-completed">✓ Completed</span>';
    if ($status === 'Pending')   return '<span class="status-badge status-pending">⏳ Pending</span>';
    return '<span class="status-badge status-other">' . htmlspecialchars($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $invoice_no; ?> | Fayenor Invoice</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #eef0f4;
            color: #1a1a2e;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Toolbar (screen only) ──────────────────────────────── */
        .toolbar {
            background: #023020;
            color: #fff;
            padding: 13px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 16px rgba(0,0,0,.30);
        }
        .toolbar-brand   { font-size: .95rem; font-weight: 700; letter-spacing: .4px; }
        .toolbar-actions { display: flex; gap: 10px; }
        .btn-print {
            background: #fff; color: #023020; border: none;
            padding: 8px 22px; border-radius: 50px;
            font-weight: 700; font-size: .85rem; cursor: pointer;
            transition: background .2s, transform .1s;
        }
        .btn-print:hover { background: #d0ead8; transform: translateY(-1px); }
        .btn-close-tab {
            background: transparent; color: rgba(255,255,255,.7);
            border: 1px solid rgba(255,255,255,.3);
            padding: 8px 18px; border-radius: 50px;
            font-size: .85rem; cursor: pointer; transition: background .2s;
        }
        .btn-close-tab:hover { background: rgba(255,255,255,.12); color: #fff; }

        /* ── Page wrapper ───────────────────────────────────────── */
        .page-wrap {
            max-width: 860px;
            margin: 36px auto 60px;
            padding: 0 16px;
        }

        /* ── Invoice card ───────────────────────────────────────── */
        .invoice { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,.10); }

        /* Header */
        .invoice-header {
            background: #023020;
            padding: 34px 48px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .logo-text { color: #fff; font-size: 1.75rem; font-weight: 800; letter-spacing: 1px; }
        .logo-sub  { color: rgba(255,255,255,.45); font-size: .72rem; margin-top: 5px; letter-spacing: 2px; text-transform: uppercase; }
        .invoice-meta    { text-align: right; }
        .invoice-type    { font-size: 1.3rem; font-weight: 700; color: #fff; letter-spacing: .8px; }
        .invoice-number  { font-size: .85rem; color: rgba(255,255,255,.6); margin-top: 6px; font-weight: 500; }
        .invoice-date-lbl{ font-size: .78rem; color: rgba(255,255,255,.45); margin-top: 3px; }

        /* Body */
        .invoice-body { padding: 42px 48px; }

        /* Parties */
        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 36px;
        }
        .party-label   { font-size: .68rem; text-transform: uppercase; letter-spacing: 2px; color: #999; font-weight: 700; margin-bottom: 9px; }
        .party-name    { font-size: 1.05rem; font-weight: 700; color: #023020; margin-bottom: 5px; }
        .party-contact { font-size: .8rem; color: #444; font-weight: 600; margin-bottom: 3px; }
        .party-detail  { font-size: .8rem; color: #777; line-height: 1.7; }
        .party-from    { text-align: right; }

        .divider { height: 1px; background: linear-gradient(to right, #d8eadc, #e0e8e2, #d8eadc); margin: 0 0 34px; }

        /* ── Single-payment highlight box ───────────────────────── */
        .single-highlight {
            background: linear-gradient(135deg, #f0faf3, #e8f4ec);
            border: 1px solid #b7dfc3;
            border-radius: 10px;
            padding: 26px 30px;
            margin-bottom: 34px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 22px;
        }
        .hf-label { font-size: .67rem; text-transform: uppercase; letter-spacing: 1.5px; color: #888; font-weight: 700; margin-bottom: 6px; }
        .hf-value { font-size: .95rem; font-weight: 600; color: #023020; }
        .hf-value.amount-big { font-size: 1.55rem; font-weight: 800; }

        /* ── Table ──────────────────────────────────────────────── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .items-table th {
            background: #023020; color: #fff;
            padding: 12px 15px;
            font-size: .7rem; text-transform: uppercase; letter-spacing: 1px;
            font-weight: 600; text-align: left;
        }
        .items-table th:last-child { text-align: right; }
        .items-table tbody tr { border-bottom: 1px solid #f0f0f0; }
        .items-table tbody tr:last-child { border-bottom: none; }
        .items-table td { padding: 13px 15px; font-size: .85rem; color: #333; vertical-align: middle; }
        .items-table td.text-right { text-align: right; font-weight: 700; color: #023020; }
        .ref-sub { font-size: .7rem; color: #bbb; margin-top: 2px; }

        /* ── Total bar ──────────────────────────────────────────── */
        .total-bar {
            background: #023020; border-radius: 8px;
            padding: 15px 22px;
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 34px;
        }
        .total-bar .total-label  { font-size: .75rem; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,.55); font-weight: 600; }
        .total-bar .total-amount { font-size: 1.5rem; font-weight: 800; color: #fff; }

        /* ── Status badges ──────────────────────────────────────── */
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 50px; font-size: .7rem; font-weight: 700; }
        .status-completed { background: #d4edda; color: #135a23; }
        .status-pending   { background: #fff3cd; color: #856404; }
        .status-other     { background: #f8d7da; color: #721c24; }

        /* ── Notes box ──────────────────────────────────────────── */
        .notes-box {
            background: #f9fafb; border-left: 4px solid #023020;
            border-radius: 0 6px 6px 0; padding: 14px 18px; margin-bottom: 30px;
        }
        .notes-label { font-size: .67rem; text-transform: uppercase; letter-spacing: 1.5px; color: #999; font-weight: 700; margin-bottom: 6px; }
        .notes-text  { font-size: .875rem; color: #444; line-height: 1.6; }

        /* ── Footer ─────────────────────────────────────────────── */
        .invoice-footer {
            border-top: 1px solid #eee;
            padding: 22px 48px;
            text-align: center;
            background: #fafafa;
        }
        .invoice-footer p { font-size: .76rem; color: #aaa; margin: 2px 0; }
        .invoice-footer strong { color: #555; }

        /* ── PRINT — A4 ─────────────────────────────────────────── */
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 14mm;
            }
            html, body { background: #fff !important; }
            .toolbar   { display: none !important; }
            .page-wrap { max-width: 100%; margin: 0; padding: 0; }
            .invoice   { box-shadow: none; border-radius: 0; }
            .invoice-header { padding: 22px 34px; background: #023020 !important; -webkit-print-color-adjust: exact; }
            .invoice-body   { padding: 26px 34px; }
            .invoice-footer { padding: 15px 34px; }
            .items-table th { background: #023020 !important; -webkit-print-color-adjust: exact; }
            .total-bar      { background: #023020 !important; -webkit-print-color-adjust: exact; }
            .single-highlight { background: #f0faf3 !important; -webkit-print-color-adjust: exact; }
            .items-table tbody tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<!-- Toolbar -->
<div class="toolbar">
    <div class="toolbar-brand">🧾 Fayenor — Invoice Viewer</div>
    <div class="toolbar-actions">
        <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
        <button class="btn-close-tab" onclick="window.close()">✕ Close</button>
    </div>
</div>

<div class="page-wrap">
<div class="invoice">

    <!-- HEADER ─────────────────────────────────────────────────── -->
    <div class="invoice-header">
        <div>
            <div class="logo-text">Fayenor</div>
            <div class="logo-sub">Company Limited · KSA</div>
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

        <div class="divider"></div>

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
                <div class="hf-label">Status</div>
                <div class="hf-value"><?php echo statusBadge($pay['payment_status']); ?></div>
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
                    <th style="width:20%">Date</th>
                    <th style="width:34%">Project / Company</th>
                    <th style="width:18%">Method</th>
                    <th style="width:13%">Status</th>
                    <th style="width:15%">Amount (SAR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                    <td>
                        <?php echo htmlspecialchars($pay['company_name']); ?>
                        <div class="ref-sub">Ref: #<?php echo str_pad($pay['payment_id'], 6, "0", STR_PAD_LEFT); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                    <td><?php echo statusBadge($pay['payment_status']); ?></td>
                    <td class="text-right"><?php echo number_format($pay['amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <?php else: ?>

        <!-- Multi-row statement table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:18%">Date</th>
                    <th style="width:30%">Project / Company</th>
                    <th style="width:20%">Method</th>
                    <th style="width:15%">Status</th>
                    <th style="width:17%">Amount (SAR)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pay): ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                    <td>
                        <?php echo htmlspecialchars($pay['company_name']); ?>
                        <div class="ref-sub">Ref: #<?php echo str_pad($pay['payment_id'], 6, "0", STR_PAD_LEFT); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                    <td><?php echo statusBadge($pay['payment_status']); ?></td>
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
        <p style="margin-top:8px;">Fayenor Company Limited · Unaizah, Al-Qassim, KSA · info@fayenor.com</p>
    </div>

</div><!-- /.invoice -->
</div><!-- /.page-wrap -->

</body>
</html>