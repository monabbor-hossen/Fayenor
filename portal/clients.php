<?php
// portal/clients.php
require_once 'includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

$db = (new Database())->getConnection();
// 1. Fetch Clients AND Account Info
$query = "SELECT c.*, c.is_active as license_status, w.*, a.account_id as master_account_id, a.username as master_username,
          COALESCE((SELECT SUM(amount) FROM payments WHERE client_id = c.client_id AND payment_status = 'Completed'), 0) as total_paid
          FROM clients c 
          LEFT JOIN workflow_tracking w ON c.client_id = w.client_id
          LEFT JOIN client_accounts a ON c.account_id = a.account_id
          ORDER BY c.client_id ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Count total licenses per account to know which ones are shared
$account_totals = [];
$account_serials = [];
foreach ($clients as $client) {
    $acc_id = $client['master_account_id'];
    if ($acc_id) {
        if (!isset($account_totals[$acc_id])) $account_totals[$acc_id] = 0;
        $account_totals[$acc_id]++;
    }
}

// 3. Pre-calculate Data & Assign Serials
foreach ($clients as &$client) {
    // Assign Serials
    $acc_id = $client['master_account_id'];
    if ($acc_id) {
        if (!isset($account_serials[$acc_id])) $account_serials[$acc_id] = 0;
        $account_serials[$acc_id]++;
        
        $client['account_serial'] = $account_serials[$acc_id];
        $client['linked_licenses_count'] = $account_totals[$acc_id];
    } else {
        $client['account_serial'] = 0;
        $client['linked_licenses_count'] = 0;
    }

    // Progress calculations
    $steps_to_check = [
        $client['hire_foreign_company'] ?? '', $client['misa_application'] ?? '',
        $client['sbc_application'] ?? '',      $client['article_association'] ?? '',
        $client['qiwa'] ?? '',                 $client['muqeem'] ?? '',
        $client['gosi'] ?? '',                 $client['chamber_commerce'] ?? ''
    ];
    
    $approved_count = 0;
    $total_active_steps = 0; 
    
    foreach($steps_to_check as $status) { 
        if ($status !== 'Not Required') {
            $total_active_steps++; 
            if ($status === 'Approved') {
                $approved_count++; 
            }
        }
    }
    
    $client['progress_val'] = ($total_active_steps > 0) ? ($approved_count / $total_active_steps) * 100 : 0;
    $client['approved_count'] = $approved_count;
    $client['total_active_steps'] = $total_active_steps; 
    $client['due_val'] = $client['contract_value'] - $client['total_paid'];
}
unset($client);

// ... (Keep the sorting logic $sort, $dir as it is) ...

// 3. Sort Logic
$sort = $_GET['sort'] ?? 'id';
$dir  = $_GET['dir'] ?? 'desc';
$next_dir = ($dir === 'asc') ? 'desc' : 'asc';

usort($clients, function($a, $b) use ($sort, $dir) {
    $valA = $valB = 0;
    switch ($sort) {
        case 'company': $valA = strtolower($a['company_name']); $valB = strtolower($b['company_name']); return ($dir === 'asc') ? strcmp($valA, $valB) : strcmp($valB, $valA);
        case 'payment': $valA = $a['due_val']; $valB = $b['due_val']; break;
        case 'progress': $valA = $a['progress_val']; $valB = $b['progress_val']; break;
        case 'id': default: $valA = $a['client_id']; $valB = $b['client_id']; break;
    }
    if ($valA == $valB) return 0;
    return ($dir === 'asc') ? (($valA < $valB) ? -1 : 1) : (($valA > $valB) ? -1 : 1);
});

function sortLink($key, $label, $currentSort, $nextDir) {
    $active = ($currentSort === $key) ? 'text-white fw-bold' : 'text-gold';
    $icon = ($currentSort === $key) ? (($nextDir === 'asc') ? '<i class="bi bi-arrow-down-short"></i>' : '<i class="bi bi-arrow-up-short"></i>') : '';
    return "<a href='?sort=$key&dir=$nextDir' class='text-decoration-none text-uppercase small $active'>$label $icon</a>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-white fw-bold mb-0">Client Portfolios</h3>
            <p class="text-white-50 small mb-0">Manage active MISA licenses and investments</p>
        </div>
        <a href="client-add.php" class="btn btn-rooq-primary px-4 rounded-pill"><i class="bi bi-plus-lg me-2"></i> Add New Client</a>
    </div>

    <div class="card-box p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle" style="background: transparent;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.05);">
                        <th class="py-3 ps-2 text-end"><?php echo sortLink('id', 'SL', $sort, $next_dir); ?></th>
                        <th class="py-3"><?php echo sortLink('company', 'Company Info', $sort, $next_dir); ?></th>
                        <th class="py-3"><?php echo sortLink('progress', 'Progress', $sort, $next_dir); ?></th>
                        <th class="py-3 text-gold text-uppercase small">Contact Details</th>
                        <th class="py-3 text-center text-gold text-uppercase small">Login Access</th>
                        <th class="py-3"><?php echo sortLink('payment', 'Payment', $sort, $next_dir); ?></th>
                        <th class="py-3 text-center pe-4 text-gold text-uppercase small">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($clients) > 0): ?>
                    <?php foreach ($clients as $client): 
                        $due = $client['due_val'];
                        $prog = round($client['progress_val']);
                        $prog_color = ($prog == 100) ? 'bg-success' : (($prog > 30) ? 'bg-warning' : 'bg-danger');
                        
                        if ($client['contract_value'] == 0) $status_badge = '<span class="badge bg-secondary">No Contract</span>';
                        elseif ($due <= 0) $status_badge = '<span class="badge bg-success text-dark">Paid</span>';
                        elseif ($client['total_paid'] > 0) $status_badge = '<span class="badge bg-warning text-dark">Partial</span>';
                        else $status_badge = '<span class="badge bg-danger">Unpaid</span>';

                        $clientJson = htmlspecialchars(json_encode($client), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td class="ps-4 text-white-50 fw-bold">#<?php echo $client['client_id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-icon me-2 flex-shrink-0" style="width:40px;height: 40px;border-radius:10px;font-size:1.2rem;"><i class="bi bi-building"></i></div>
                                <div>
                                    <div class="fw-bold text-white"><?php echo htmlspecialchars($client['company_name']); ?></div>
                                    <div class="small text-white-50"><?php echo htmlspecialchars($client['client_name']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 6px; background: rgba(255,255,255,0.1); width: 80px;">
                                    <div class="progress-bar <?php echo $prog_color; ?>" role="progressbar" style="width: <?php echo $prog; ?>%"></div>
                                </div>
                                <span class="small text-white fw-bold"><?php echo $prog; ?>%</span>
                            </div>
                            <div class="text-white-50" style="font-size: 0.7rem;">
                                <?php echo $client['approved_count']; ?>/<?php echo $client['total_active_steps']; ?> Approved
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex align-items-center text-nowrap"><i class="bi bi-envelope text-gold me-2"></i><span class="text-white-50 small"><?php echo htmlspecialchars($client['email']); ?></span></div>
                                <div class="d-flex align-items-center text-nowrap"><i class="bi bi-telephone text-gold me-2"></i><span class="text-white-50 small"><?php echo htmlspecialchars($client['phone_number']); ?></span></div>
                            </div>
                        </td>
                        
                        <td class="text-center">
                            <?php if (!empty($client['master_account_id'])): ?>
                                <div class="form-check form-switch m-0 d-flex justify-content-center" title="Toggle Application Visibility & Access">
                                    <input class="form-check-input form-check-input-gold cursor-pointer" type="checkbox" 
                                           onchange="toggleLoginStatus('license', <?php echo $client['client_id']; ?>, this)" 
                                           <?php echo (!isset($client['license_status']) || $client['license_status'] == 1) ? 'checked' : ''; ?>>
                                </div>
                                
                                <?php if ($client['linked_licenses_count'] > 1): ?>
                                    <div class="small text-info mt-1 text-nowrap fw-bold" style="font-size: 0.65rem;" title="License <?php echo $client['account_serial']; ?> out of <?php echo $client['linked_licenses_count']; ?> for this account">
                                        <i class="bi bi-link-45deg me-1"></i><?php echo htmlspecialchars($client['master_username']); ?> (<?php echo $client['account_serial']; ?>)
                                    </div>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <span class="badge bg-secondary small" style="font-size: 0.7rem;">No Account</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php echo $status_badge; ?>
                            <div class="small text-white-50 mt-1">Due: <?php echo number_format(max(0, $due)); ?> SAR</div>
                        </td>

                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <a href="client-finance.php?id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-outline-warning border-0 opacity-75 hover-opacity-100"><i class="bi bi-cash-stack"></i></a>
                                <a href="client-edit.php?id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-outline-light border-0 opacity-50 hover-opacity-100"><i class="bi bi-pencil-square"></i></a>
                                <button class="btn btn-sm btn-outline-light border-0 opacity-50 hover-opacity-100" title="View Details" data-client='<?php echo $clientJson; ?>' onclick="openViewModal(this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-white-50">No clients found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>



<?php require_once "includes/footer.php" ?>