<?php
// portal/client-add.php
require_once '../includes/header.php';
require_once __DIR__ . '/../../app/Config/Database.php';

// --- 1. SECURITY: STRICT ACCESS CONTROL ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'client') {
    header("Location: ../../public/login");
    exit();
}

// --- 2. GRAB SESSION MESSAGES (PRG PATTERN) ---
$message = "";
if (isset($_SESSION['success_msg'])) {
    $message = "<div class='alert bg-success bg-opacity-25 text-success border border-success border-opacity-25 alert-dismissible fade show rounded-3 mb-4'>
                    <i class='bi bi-check-circle-fill me-2'></i>" . $_SESSION['success_msg'] . "
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button>
                </div>";
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $message = "<div class='alert bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 alert-dismissible fade show rounded-3 mb-4'>
                    <i class='bi bi-exclamation-triangle-fill me-2'></i>" . $_SESSION['error_msg'] . "
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button>
                </div>";
    unset($_SESSION['error_msg']);
}

$db = (new Database())->getConnection();

// Fetch existing client accounts from the users table
// $stmt_accs = $db->query("SELECT u.id as account_id, u.username, c.client_name FROM users u LEFT JOIN clients c ON u.id = c.account_id WHERE u.role = 'client' GROUP BY u.id");
$stmt_accs = $db->query("SELECT u.id as account_id, u.username, c.client_name FROM users u LEFT JOIN clients c ON u.id = c.account_id WHERE u.role = 'client' GROUP BY u.id");
$existing_accounts = $stmt_accs->fetchAll(PDO::FETCH_ASSOC);

// --- 3. HANDLE FORM SUBMISSION (WITH PRG REDIRECT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::checkCSRF($_POST['csrf_token']);

    // Sanitize Basic Info
    $company = Security::clean($_POST['company_name']);
    $client  = Security::clean($_POST['client_name']);
    $phone   = Security::clean($_POST['phone_number']);
    $email   = Security::clean($_POST['email']);
    $trade   = Security::clean($_POST['trade_name_application']);
    $value   = floatval($_POST['contract_value']);

    try {
        $db->beginTransaction();

        // A. Insert Client Profile
        $sql = "INSERT INTO clients (company_name, client_name, phone_number, email, trade_name_application, contract_value) 
                VALUES (:company, :client, :phone, :email, :trade_app, :val)";
        $stmt = $db->prepare($sql);
        $stmt->execute([':company'=>$company, ':client'=>$client, ':phone'=>$phone, ':email'=>$email, ':trade_app'=>$trade, ':val'=>$value]);
        $new_client_id = $db->lastInsertId();

        // B. Handle Master Account (New or Existing in USERS table)
        $account_type = $_POST['account_type'] ?? 'new';
        $account_id = null;

        if ($account_type === 'existing') {
            $account_id = intval($_POST['existing_account_id']);
        } else {
            // Create New Account in USERS table
            $username = Security::clean($_POST['account_username']);
            $password = $_POST['account_password'];
            if (!empty($username) && !empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Insert into USERS table with role 'client'
                $sql_acc = "INSERT INTO users (username, password, role, full_name) VALUES (:user, :pass, 'client', :fname)";
                $stmt_acc = $db->prepare($sql_acc);
                $stmt_acc->execute([':user' => $username, ':pass' => $hashed_password, ':fname' => $client]);
                $account_id = $db->lastInsertId();
            }
        }

        // C. Link the License to the Account
        if ($account_id) {
            $db->prepare("UPDATE clients SET account_id = ? WHERE client_id = ?")->execute([$account_id, $new_client_id]);
        }

        // D. Insert Workflow
        $statuses = [':cid' => $new_client_id, ':update_at' => date('Y-m-d H:i:s')];
        
        $db_keys = [
            'scope' => 'scope', 'hire' => 'hire', 'misa' => 'misa', 'cr' => 'cr', 
            'tnr' => 'tnr',
            'article' => 'art', 'gosi' => 'gosi', 'qiwa' => 'qiwa', 'muqeem' => 'muqeem', 'coc' => 'coc'
        ];
        
        $required_steps = ['scope', 'qiwa', 'muqeem']; 
        
        foreach($db_keys as $post_key => $db_key) {
            if (in_array($post_key, $required_steps) || isset($_POST['enable_'.$post_key])) {
                $statuses[":{$db_key}_st"] = $_POST['status_'.$post_key];
                $statuses[":{$db_key}_nt"] = $_POST['note_'.$post_key];
            } else {
                $statuses[":{$db_key}_st"] = 'Not Required';
                $statuses[":{$db_key}_nt"] = '';
            }
        }

        $sql_wf = "INSERT INTO workflow_tracking 
                   (client_id, license_scope_status, license_scope_note, hire_foreign_company, hire_foreign_company_note,
                   misa_application, misa_application_note, cr_application, cr_application_note,
                   t_n_reservation, t_n_reservation_note,
                   article_association, article_association_note,
                   gosi, gosi_note, qiwa, qiwa_note, muqeem, muqeem_note, chamber_commerce, chamber_commerce_note, update_date_at) 
                   VALUES 
                   (:cid, :scope_st, :scope_nt, :hire_st, :hire_nt, :misa_st, :misa_nt, :cr_st, :cr_nt, :tnr_st, :tnr_nt,
                    :art_st, :art_nt, :gosi_st, :gosi_nt, :qiwa_st, :qiwa_nt, :muqeem_st, :muqeem_nt, :coc_st, :coc_nt, :update_at)";

        $stmt_wf = $db->prepare($sql_wf);
        $stmt_wf->execute($statuses);
        $db->commit();
        
        Security::logActivity("Created new client license for: " . $company);
        // REDIRECT ON SUCCESS
        $_SESSION['success_msg'] = "Client License created and linked successfully!";
        $_SESSION['close_tab'] = true;
        header("Location: ./"); // Redirecting back to the clients list is best UX here!
        exit();

    } catch (PDOException $e) {
        $db->rollBack();
        if(strpos($e->getMessage(), 'Duplicate entry') !== false) {
             $_SESSION['error_msg'] = "Error: Username already taken.";
        } else {
             $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
        }
        // REDIRECT ON ERROR
        header("Location: client-add");
        exit();
    }
}

$workflow_steps = [
    'scope'   => 'License Processing Scope',
    'hire'    => 'Hire Foreign Company',
    'misa'    => 'MISA Application',
    'cr'      => 'CR Application',
    'tnr'     => 'Trade Name Reservation',
    'article' => 'Article of Association',
    'qiwa'    => 'QIWA',
    'muqeem'  => 'MUQEEM',
    'gosi'    => 'GOSI',
    'coc'     => 'Chamber of Commerce'
];
?>

<div class="container-fluid py-4">
    <a href="./" onclick="rooqSmartBack('./'); return false;" class="text-white-50 text-decoration-none mb-3 d-inline-block hover-white">
        <i class="bi bi-arrow-left me-2"></i> Back to Clients
    </a>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card-box">
                <h4 class="text-white fw-bold mb-4 border-bottom border-light border-opacity-10 pb-3">Add New License / Client</h4>
                
                <?php echo $message; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">

                    <h5 class="text-secondary mb-3"><i class="bi bi-info-circle me-2"></i>Project / License Information</h5>
                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Company Name</label>
                            <input type="text" name="company_name" class="form-control glass-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Client Rep Name</label>
                            <input type="text" name="client_name" class="form-control glass-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Phone Number</label>
                            <input type="tel" name="phone_number" class="form-control glass-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control glass-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Trade Name Application</label>
                            <input type="text" name="trade_name_application" class="form-control glass-input">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Total Contract Value (SAR)</label>
                            <input type="number" step="0.01" name="contract_value" class="form-control glass-input" placeholder="0.00" required>
                        </div>
                    </div>

                    <h5 class="text-secondary mb-3"><i class="bi bi-shield-lock me-2"></i>Client Portal Access</h5>
                    <div class="row g-3 mb-5 p-3 rounded" style="background: rgba(0,0,0,0.2);">
                        
                        <div class="col-12 mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input form-check-input-secondary" type="radio" name="account_type" id="acc_new" value="new" checked onchange="toggleAccountFields()">
                                <label class="form-check-label text-white" for="acc_new">Create New Login Account</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input form-check-input-secondary" type="radio" name="account_type" id="acc_existing" value="existing" onchange="toggleAccountFields()">
                                <label class="form-check-label text-white" for="acc_existing">Link to Existing Account</label>
                            </div>
                        </div>

                        <div class="row g-3 m-0 p-0" id="new_account_fields">
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold">Username</label>
                                <input type="text" name="account_username" class="form-control glass-input" placeholder="Create a username" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold">Password</label>
                                <div class="input-group">
                                    <input type="password" name="account_password" id="acc_pass" class="form-control glass-input border-end-0" placeholder="Create a password" autocomplete="new-password">
                                    <button class="btn glass-input border-start-0 text-white-50" type="button" onclick="togglePassword('acc_pass', 'pass_icon')">
                                        <i class="bi bi-eye" id="pass_icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 m-0 p-0 d-none" id="existing_account_fields">
                            <div class="col-md-12">
                                <label class="form-label text-white-50 small fw-bold">Select Existing Master Account</label>
                                <select name="existing_account_id" class="form-select glass-input">
                                    <option value="" disabled selected>-- Choose Account --</option>
                                    <?php foreach($existing_accounts as $acc): ?>
                                        <option value="<?php echo $acc['account_id']; ?>">
                                            <?php echo htmlspecialchars($acc['username'] . ' (' . $acc['client_name'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>


                    <h5 class="text-secondary mb-3"><i class="bi bi-kanban me-2"></i>Initial Workflow Status</h5>
                    <div class="row g-3">
                        <?php 
                            $required_steps = ['scope', 'qiwa', 'muqeem']; 
                            foreach($workflow_steps as $key => $label): 
                                $is_required = in_array($key, $required_steps);
                        ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="workflow-card p-3 h-100 d-flex flex-column justify-content-between position-relative" id="card_<?php echo $key; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="text-white fw-bold small text-uppercase mb-0"><?php echo $label; ?></label>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-switch m-0 p-0 d-flex align-items-center">
                                            <input class="form-check-input m-0 form-check-input-secondary cursor-pointer <?php echo $is_required ? 'd-none' : ''; ?>" type="checkbox"
                                                name="enable_<?php echo $key; ?>" id="enable_<?php echo $key; ?>"
                                                value="1" checked onchange="toggleWorkflowCard('<?php echo $key; ?>')"
                                                style="width: 2.2em; height: 1.1em;"
                                                <?php echo $is_required ? 'disabled' : ''; ?>>
                                        </div>
                                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" onclick="openEditModal('<?php echo $key; ?>', '<?php echo htmlspecialchars($label, ENT_QUOTES); ?>')"><i class="bi bi-pencil-square fs-6"></i></button>
                                    </div>
                                </div>
                                <select name="status_<?php echo $key; ?>" id="select_<?php echo $key; ?>" class="form-select glass-input glass-select-sm">
                                    <?php if ($key === 'scope'): ?>
                                    <option value="Trading License Processing">Trading License Processing</option>
                                    <option value="Service License Processing">Service License Processing</option>
                                    <option value="Service License Upgrade to Trading License">Service License Upgrade</option>
                                    <?php else: ?>
                                    <option value="Pending Application">Pending Application</option>
                                    <option value="In Process">In Process</option>
                                    <option value="Applied">Applied</option>
                                    <option value="Approved">Approved</option>
                                    <?php endif; ?>
                                </select>
                                <div id="note_indicator_<?php echo $key; ?>" class="mt-2 text-secondary small fst-italic d-none"><i class="bi bi-sticky-fill me-1"></i> Note added</div>
                                <input type="hidden" name="note_<?php echo $key; ?>" id="input_note_<?php echo $key; ?>" value="">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="col-12 mt-5">
                        <button type="submit" class="btn btn-rooq-primary w-100 py-3 fw-bold">Create License & Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>