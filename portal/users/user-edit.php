<?php
// portal/user-edit.php
require_once '../includes/header.php';
require_once __DIR__ . '/../../app/Config/Database.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$user_id) {
    header("Location: users");
    exit();
}

$db = (new Database())->getConnection();

// --- 1. GRAB SESSION MESSAGES (PRG PATTERN) ---
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

// --- 2. FETCH CURRENT USER DATA SECURELY FIRST ---
try {
    $stmt = $db->prepare("SELECT id, username, role, full_name, email, phone, job_title, basic_salary, joining_date, resigning_date FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<div class='alert alert-warning m-4'>User not found.</div>";
        require_once '../includes/footer.php';
        exit();
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-4 fw-bold'>Database Error: <br><small>" . $e->getMessage() . "</small></div>";
    require_once '../includes/footer.php';
    exit();
}

// --- 3. HANDLE UPDATE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    Security::checkCSRF($_POST['csrf_token']);

    $username   = Security::clean($_POST['username']);
    $password   = $_POST['password']; 
    
    $full_name = Security::clean($_POST['full_name']);
    $email     = Security::clean($_POST['email']);
    $phone     = Security::clean($_POST['phone']);
    $job_title = Security::clean($_POST['job_title']);
    $basic_salary = floatval($_POST['basic_salary']);
    
    $joining_date = !empty($_POST['joining_date']) ? Security::clean($_POST['joining_date']) : null;
    $resigning_date = !empty($_POST['resigning_date']) ? Security::clean($_POST['resigning_date']) : null;

    // --- STRICT ROLE SECURITY CHECK ---
    if ($_SESSION['role'] == '2') {
        $role = Security::clean($_POST['role']);
    } else {
        $role = $user['role']; 
    }

    try {
        $sql = "UPDATE users SET 
                username = :user, role = :role, 
                full_name = :full_name, email = :email, 
                phone = :phone, job_title = :job_title, 
                basic_salary = :basic_salary, 
                joining_date = :joining_date, 
                resigning_date = :resigning_date";
        
        $params = [
            ':user'       => $username,
            ':role'       => $role,
            ':full_name'  => $full_name,
            ':email'      => $email,
            ':phone'      => $phone,
            ':job_title'  => $job_title,
            ':basic_salary' => $basic_salary,
            ':joining_date' => $joining_date,
            ':resigning_date' => $resigning_date,
            ':id'         => $user_id
        ];

        // Handle Optional Password Update
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $_SESSION['error_msg'] = "Password must be at least 6 characters.";
                header("Location: user-edit?id=" . $user_id);
                exit();
            } else {
                $sql .= ", password = :pass";
                $params[':pass'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }
        
        $sql .= " WHERE id = :id";

        // EXECUTE FIRST, THEN REDIRECT
        $stmtUpdate = $db->prepare($sql);
        if ($stmtUpdate->execute($params)) {
            Security::logActivity("Updated user profile: " . $username);
            
            $_SESSION['success_msg'] = "User profile updated successfully!";
            header("Location: user-edit?id=" . $user_id);
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
        header("Location: user-edit?id=" . $user_id);
        exit();
    }
}
?>

<div class="container-fluid py-4">
    <a href="javascript:void(0);" onclick="history.length > 1 ? history.back() : window.location.href='./';" class="text-white-50 text-decoration-none mb-3 d-inline-block hover-white">
        <i class="bi bi-arrow-left me-2"></i> Back to Users
    </a>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <?php echo $message; ?>

            <div class="card-box">
                <div class="d-flex align-items-center mb-4 border-bottom border-light border-opacity-10 pb-3">
                    <div class="avatar-icon me-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-0">Edit User Profile</h4>
                        <p class="text-white-50 small mb-0">ID: #<?php echo htmlspecialchars($user['id']); ?></p>
                    </div>
                </div>

                <form method="POST" action="user-edit?id=<?php echo $user_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">

                    <h6 class="text-secondary mb-3 text-uppercase fw-bold" style="font-size: 0.8rem;"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Full Name</label>
                            <input type="text" name="full_name" class="form-control glass-input" required value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Job Title / Designation</label>
                            <input type="text" name="job_title" class="form-control glass-input" value="<?php echo htmlspecialchars($user['job_title'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control glass-input" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Phone Number</label>
                            <input type="tel" name="phone" class="form-control glass-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-12 border-top border-light border-opacity-10 pt-3 mt-3">
                            <label class="form-label text-secondary small fw-bold"><i class="bi bi-cash-stack me-2"></i>Basic Salary (Monthly)</label>
                            <div class="input-group">
                                <span class="input-group-text glass-input border-end-0 text-white-50">SAR</span>
                                <input type="number" step="0.01" name="basic_salary" class="form-control glass-input border-start-0 ps-0" value="<?php echo htmlspecialchars($user['basic_salary'] ?? '0.00'); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6 mt-3">
                            <label class="form-label text-secondary small fw-bold"><i class="bi bi-calendar-check me-2"></i>Joining Date</label>
                            <input type="text" name="joining_date" class="form-control glass-input rooq-date" data-hide-buttons="true" value="<?php echo htmlspecialchars($user['joining_date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label class="form-label text-danger small fw-bold"><i class="bi bi-calendar-x me-2"></i>Resigning Date (If left)</label>
                            <input type="text" name="resigning_date" class="form-control glass-input rooq-date border-danger text-danger" data-hide-buttons="true" placeholder="Leave blank if active" value="<?php echo htmlspecialchars($user['resigning_date'] ?? ''); ?>">
                        </div>
                    </div>

                    <h6 class="text-secondary mb-3 text-uppercase fw-bold mt-4" style="font-size: 0.8rem;"><i class="bi bi-shield-lock me-2"></i>Account Security</h6>
                    <div class="row g-3 mb-4 p-3 rounded" style="background: rgba(0,0,0,0.2);">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">System Username</label>
                            <div class="input-group">
                                <span class="input-group-text glass-input border-end-0 text-white-50"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control glass-input border-start-0 ps-0" required value="<?php echo htmlspecialchars($user['username']); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Access Level</label>
                            <select name="role" class="form-select glass-input" <?php echo ($_SESSION['role'] != '2') ? 'disabled' : ''; ?>>
                                <option value="1" <?php echo ($user['role'] == '1') ? 'selected' : ''; ?>>Staff (Standard Access)</option>
                                <option value="2" <?php echo ($user['role'] == '2') ? 'selected' : ''; ?>>Admin (Full Access)</option>
                            </select>
                            
                            <?php if ($_SESSION['role'] != '2'): ?>
                                <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
                                <div class="form-text text-warning small mt-1"><i class="bi bi-shield-lock me-1"></i>Only Admins can change roles.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-bold">Reset Password (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text glass-input border-end-0 text-white-50"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" class="form-control glass-input border-start-0 ps-0" placeholder="Leave blank to keep current password">
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="update_user" class="btn btn-rooq-primary w-100 py-3 fw-bold mt-2">Update User Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>