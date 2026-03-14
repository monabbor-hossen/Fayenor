<?php
// portal/client-edit.php
require_once __DIR__ . '/../../app/Config/Config.php';
require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Helpers/Security.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../public/login"); exit(); }

$message = "";
$client_id = $_GET['id'] ?? null;
if (!$client_id) { header("Location: clients"); exit(); }

$db = (new Database())->getConnection();

// --- UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::checkCSRF($_POST['csrf_token']);

    // 1. Basic Info
    $company = Security::clean($_POST['company_name']);
    $name    = Security::clean($_POST['client_name']);
    $phone   = Security::clean($_POST['phone_number']);
    $email   = Security::clean($_POST['email']);
    $trade   = Security::clean($_POST['trade_name_application'] ?? ''); 
    $val     = floatval($_POST['contract_value'] ?? 0);
    
    // 2. Account Info (Sanitize)
    $username = Security::clean($_POST['account_username']);
    $password = $_POST['account_password']; // Raw password

    try {
        $db->beginTransaction();

        // A. Update Client Profile
        $stmt = $db->prepare("UPDATE clients SET company_name=:c, client_name=:n, phone_number=:p, email=:e, trade_name_application=:t, contract_value=:v WHERE client_id=:id");
        $stmt->execute([':c'=>$company, ':n'=>$name, ':p'=>$phone, ':e'=>$email, ':t'=>$trade, ':v'=>$val, ':id'=>$client_id]);

        // B. Update/Create Client Account (Using Master account_id)
        if (!empty($username)) {
            // Find current account ID
            $stmt_chk = $db->prepare("SELECT account_id FROM clients WHERE client_id = ?");
            $stmt_chk->execute([$client_id]);
            $curr_account_id = $stmt_chk->fetchColumn();

            if ($curr_account_id) {
                // Update existing master account
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_acc = $db->prepare("UPDATE client_accounts SET username = ?, password_hash = ? WHERE account_id = ?");
                    $stmt_acc->execute([$username, $hash, $curr_account_id]);
                } else {
                    // Update Username only (Keep existing password)
                    $stmt_acc = $db->prepare("UPDATE client_accounts SET username = ? WHERE account_id = ?");
                    $stmt_acc->execute([$username, $curr_account_id]);
                }
            } else {
                // If they didn't have an account, create a new one and link it
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_acc = $db->prepare("INSERT INTO client_accounts (client_id, username, password_hash) VALUES (?, ?, ?)");
                    $stmt_acc->execute([$client_id, $username, $hash]);
                    $new_acc_id = $db->lastInsertId();
                    
                    // Link to client
                    $db->prepare("UPDATE clients SET account_id = ? WHERE client_id = ?")->execute([$new_acc_id, $client_id]);
                } else {
                    $message = "<div class='alert alert-danger bg-danger bg-opacity-25 text-white border-danger'>Password is required to create a new Master Account.</div>";
                }
            }
        }

        // C. Update Workflow
        if (empty($message)) { // Only proceed if no account creation errors
            $wf_data = ['client_id' => $client_id, 'update_at' => date('Y-m-d H:i:s')];
            
            $db_cols = [
                'scope' => 'license_scope_status', 'hire' => 'hire_foreign_company',
                'misa' => 'misa_application', 'sbc' => 'sbc_application',
                'article' => 'article_association', 'gosi' => 'gosi',
                'qiwa' => 'qiwa', 'muqeem' => 'muqeem', 'coc' => 'chamber_commerce'
            ];

            $required_steps = ['scope', 'qiwa', 'muqeem']; 

            foreach($db_cols as $post_key => $st_col) {
                $nt_col = ($post_key === 'scope') ? 'license_scope_note' : $st_col . '_note';
                
                if (in_array($post_key, $required_steps) || isset($_POST['enable_'.$post_key])) {
                    $wf_data[$st_col] = $_POST['status_'.$post_key];
                    $wf_data[$nt_col] = $_POST['note_'.$post_key];
                } else {
                    $wf_data[$st_col] = 'Not Required';
                    $wf_data[$nt_col] = '';
                }
            }

            // Check/Upsert Workflow
            $check = $db->prepare("SELECT id FROM workflow_tracking WHERE client_id = ?");
            $check->execute([$client_id]);
            
            if ($check->rowCount() > 0) {
                $sql_wf = "UPDATE workflow_tracking SET 
                            license_scope_status = :license_scope_status, license_scope_note = :license_scope_note,
                            hire_foreign_company = :hire_foreign_company, hire_foreign_company_note = :hire_foreign_company_note,
                            misa_application = :misa_application, misa_application_note = :misa_application_note,
                            sbc_application = :sbc_application, sbc_application_note = :sbc_application_note,
                            article_association = :article_association, article_association_note = :article_association_note,
                            gosi = :gosi, gosi_note = :gosi_note, qiwa = :qiwa, qiwa_note = :qiwa_note,
                            muqeem = :muqeem, muqeem_note = :muqeem_note, chamber_commerce = :chamber_commerce, chamber_commerce_note = :chamber_commerce_note,
                            update_date_at = :update_at WHERE client_id = :client_id";
            } else {
                $sql_wf = "INSERT INTO workflow_tracking 
                            (license_scope_status, license_scope_note, hire_foreign_company, hire_foreign_company_note, 
                             misa_application, misa_application_note, sbc_application, sbc_application_note,
                             article_association, article_association_note, gosi, gosi_note, qiwa, qiwa_note,
                             muqeem, muqeem_note, chamber_commerce, chamber_commerce_note, update_date_at, client_id)
                           VALUES 
                            (:license_scope_status, :license_scope_note, :hire_foreign_company, :hire_foreign_company_note,
                             :misa_application, :misa_application_note, :sbc_application, :sbc_application_note,
                             :article_association, :article_association_note, :gosi, :gosi_note, :qiwa, :qiwa_note,
                             :muqeem, :muqeem_note, :chamber_commerce, :chamber_commerce_note, :update_at, :client_id)";
            }
            $stmt_wf = $db->prepare($sql_wf);
            $stmt_wf->execute($wf_data);

            $db->commit();
            // NEW: Log the exact action
            Security::logActivity("Updated client license ID: #" . $client_id . " (" . $company . ")");
            header("Location: client-edit.php?id=" . $client_id . "&msg=updated");
            exit();
        }

    } catch (PDOException $e) {
        $db->rollBack();
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// --- FETCH DATA (Fixed JOIN to use account_id for proper Auto-fill) ---
require_once '../includes/header.php';
if (isset($_GET['msg']) && $_GET['msg'] == 'updated') $message = "<div class='alert alert-success bg-success bg-opacity-25 text-white border-success'>Updated successfully!</div>";

$sql_fetch = "SELECT c.*, w.*, a.username as acc_username 
              FROM clients c 
              LEFT JOIN workflow_tracking w ON c.client_id = w.client_id 
              LEFT JOIN client_accounts a ON c.account_id = a.account_id
              WHERE c.client_id = :id LIMIT 1";
$stmt = $db->prepare($sql_fetch);
$stmt->execute([':id' => $client_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) exit("<div class='p-5 text-white'>Client not found.</div>");
$last_update = $data['update_date_at'] ? date('M d, Y h:i A', strtotime($data['update_date_at'])) : 'Never';

$workflow_steps = [
    'scope'   => ['label' => 'License Processing Scope', 'db_st' => 'license_scope_status', 'db_nt' => 'license_scope_note'],
    'hire'    => ['label' => 'Hire Foreign Company',     'db_st' => 'hire_foreign_company', 'db_nt' => 'hire_foreign_company_note'],
    'misa'    => ['label' => 'MISA Application',         'db_st' => 'misa_application',     'db_nt' => 'misa_application_note'],
    'sbc'     => ['label' => 'SBC Application',          'db_st' => 'sbc_application',      'db_nt' => 'sbc_application_note'],
    'article' => ['label' => 'Article of Association',   'db_st' => 'article_association',  'db_nt' => 'article_association_note'],
    'qiwa'    => ['label' => 'QIWA',                     'db_st' => 'qiwa',                 'db_nt' => 'qiwa_note'],
    'muqeem'  => ['label' => 'MUQEEM',                   'db_st' => 'muqeem',               'db_nt' => 'muqeem_note'],
    'gosi'    => ['label' => 'GOSI',                     'db_st' => 'gosi',                 'db_nt' => 'gosi_note'],
    'coc'     => ['label' => 'Chamber of Commerce',      'db_st' => 'chamber_commerce',     'db_nt' => 'chamber_commerce_note']
];
?>

<div class="d-flex portal-wrapper">
    <?php require_once '../includes/sidebar.php'; ?>
    <main class="w-100 p-4">
        <div class="container-fluid">
            <a href="clients" class="text-white-50 text-decoration-none mb-3 d-inline-block hover-white">
                <i class="bi bi-arrow-left me-2"></i> Back to Clients
            </a>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card-box">
                        <div
                            class="d-flex justify-content-between align-items-center mb-4 border-bottom border-light border-opacity-10 pb-3">
                            <div>
                                <h4 class="text-white fw-bold mb-0">Edit Client</h4><small class="text-gold">Updated:
                                    <?php echo $last_update; ?></small>
                            </div>
                            <span class="badge bg-gold text-dark">ID: #<?php echo $data['client_id']; ?></span>
                        </div>
                        <?php echo $message; ?>

                        <form method="POST" id="mainForm">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">

                            <h5 class="text-gold mb-3"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
                            <div class="row g-3 mb-5">
                                <div class="col-md-6"><label class="form-label text-white-50 small fw-bold">Company
                                        Name</label><input type="text" name="company_name"
                                        class="form-control glass-input"
                                        value="<?php echo htmlspecialchars($data['company_name']); ?>" required></div>
                                <div class="col-md-6"><label class="form-label text-white-50 small fw-bold">Client Rep
                                        Name</label><input type="text" name="client_name"
                                        class="form-control glass-input"
                                        value="<?php echo htmlspecialchars($data['client_name']); ?>" required></div>
                                <div class="col-md-6"><label class="form-label text-white-50 small fw-bold">Phone
                                        Number</label><input type="tel" name="phone_number"
                                        class="form-control glass-input"
                                        value="<?php echo htmlspecialchars($data['phone_number']); ?>" required></div>
                                <div class="col-md-6"><label class="form-label text-white-50 small fw-bold">Email
                                        Address</label><input type="email" name="email" class="form-control glass-input"
                                        value="<?php echo htmlspecialchars($data['email']); ?>" required></div>
                                <div class="col-md-6"><label class="form-label text-white-50 small fw-bold">Trade
                                        Name</label><input type="text" name="trade_name_application"
                                        class="form-control glass-input"
                                        value="<?php echo htmlspecialchars($data['trade_name_application'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6"><label class="form-label text-gold small fw-bold">Contract
                                        Value</label><input type="number" step="0.01" name="contract_value"
                                        class="form-control glass-input"
                                        value="<?php echo htmlspecialchars($data['contract_value'] ?? 0); ?>"></div>
                            </div>

                            <h5 class="text-gold mb-3"><i class="bi bi-shield-lock me-2"></i>Master Portal Access</h5>
                            <div class="row g-3 mb-5 p-3 rounded" style="background: rgba(0,0,0,0.2);">
                                <div class="col-md-6">
                                    <label class="form-label text-white-50 small fw-bold">Username <span class="text-gold" style="font-size: 0.65rem;">(Updates all linked licenses)</span></label>
                                    <input type="text" name="account_username" class="form-control glass-input"
                                        value="<?php echo htmlspecialchars($data['acc_username'] ?? ''); ?>"
                                        placeholder="No account set" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white-50 small fw-bold">New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="account_password" id="acc_pass"
                                            class="form-control glass-input border-end-0"
                                            placeholder="Leave empty to keep current" autocomplete="new-password">
                                        <button class="btn glass-input border-start-0 text-white-50" type="button"
                                            onclick="togglePassword('acc_pass', 'pass_icon')">
                                            <i class="bi bi-eye" id="pass_icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <h5 class="text-gold mb-3"><i class="bi bi-kanban me-2"></i>Workflow Status</h5>
                            <div class="row g-3">
                                <?php 
                                    $required_steps = ['scope', 'qiwa', 'muqeem']; 

                                    foreach($workflow_steps as $key => $info): 
                                        $current_val = $data[$info['db_st']] ?? 'In Process';
                                        $current_note = $data[$info['db_nt']] ?? '';
                                        
                                        $is_required = in_array($key, $required_steps);
                                        $is_enabled = $is_required || ($current_val !== 'Not Required'); 
                                    ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="workflow-card p-3 h-100 d-flex flex-column justify-content-between position-relative"
                                        id="card_<?php echo $key; ?>"
                                        style="<?php echo !$is_enabled ? 'opacity: 0.5;' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="text-white fw-bold small text-uppercase mb-0">
                                                    <?php echo $info['label']; ?>
                                                </label>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="form-check form-switch m-0 p-0 d-flex align-items-center"
                                                    title="<?php echo $is_required ? 'This step is required' : 'Toggle optional step'; ?>">
                                                    <input class="form-check-input m-0 form-check-input-gold cursor-pointer <?php echo $is_required ? 'd-none' : ''; ?>" type="checkbox"
                                                        name="enable_<?php echo $key; ?>"
                                                        id="enable_<?php echo $key; ?>" value="1"
                                                        <?php echo $is_enabled ? 'checked' : ''; ?>
                                                        <?php echo $is_required ? 'disabled' : ''; ?>
                                                        onchange="toggleWorkflowCard('<?php echo $key; ?>')"
                                                        style="width: 2.2em; height: 1.1em;">
                                                </div>
                                            <button type="button" class="btn btn-sm btn-link text-gold p-0"
                                                id="btn_edit_<?php echo $key; ?>"
                                                onclick="openEditModal('<?php echo $key; ?>', '<?php echo $info['label']; ?>')"
                                                <?php echo !$is_enabled ? 'disabled' : ''; ?>><i
                                                    class="bi bi-pencil-square fs-6"></i></button>
                                            </div>
                                        </div>

                                        <select name="status_<?php echo $key; ?>" id="select_<?php echo $key; ?>"
                                            class="form-select glass-select-sm"
                                            <?php echo !$is_enabled ? 'disabled' : ''; ?>>
                                            <?php if ($key === 'scope'): ?>
                                            <option value="Trading License Processing" <?php echo ($current_val == 'Trading License Processing') ? 'selected' : ''; ?>>Trading License Processing</option>
                                            <option value="Service License Processing" <?php echo ($current_val == 'Service License Processing') ? 'selected' : ''; ?>>Service License Processing</option>
                                            <option value="Service License Upgrade to Trading License" <?php echo ($current_val == 'Service License Upgrade to Trading License') ? 'selected' : ''; ?>>Service License Upgrade</option>
                                            <?php else: ?>
                                            <option value="In Progress" <?php echo ($current_val == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Applied" <?php echo ($current_val == 'Applied') ? 'selected' : ''; ?>>Applied</option>
                                            <option value="Pending Application" <?php echo ($current_val == 'Pending Application') ? 'selected' : ''; ?>>Pending Application</option>
                                            <option value="Approved" <?php echo ($current_val == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                            <?php endif; ?>
                                        </select>

                                        <div id="note_indicator_<?php echo $key; ?>"
                                            class="mt-2 text-gold small fst-italic <?php echo empty($current_note) ? 'd-none' : ''; ?>">
                                            <i class="bi bi-sticky-fill me-1"></i> Note added</div>
                                        <input type="hidden" name="note_<?php echo $key; ?>"
                                            id="input_note_<?php echo $key; ?>"
                                            value="<?php echo htmlspecialchars($current_note); ?>">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="col-12 mt-5"><button type="submit" class="btn btn-rooq-primary w-100 py-3 fw-bold">Save Changes</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="workflowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header border-bottom border-white border-opacity-10">
                <h5 class="modal-title text-white fw-bold" id="modalTitle">Update Status</h5><button type="button"
                    class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="current_field_key">
                <div class="mb-3"><label class="form-label text-gold small fw-bold">Status</label><select
                        id="modal_status_select" class="form-select glass-input"></select></div>
                <div class="mb-3"><label class="form-label text-gold small fw-bold">Note / Remark</label><textarea
                        id="modal_note_text" class="form-control glass-input" rows="3"></textarea></div>
            </div>
            <div class="modal-footer border-top border-white border-opacity-10">
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-rooq-primary btn-sm px-4" onclick="saveModalChanges()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>