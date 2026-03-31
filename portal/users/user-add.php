<?php
// portal/user-add.php
require_once '../includes/header.php';
require_once __DIR__ . '/../../app/Config/Database.php';

$message = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::checkCSRF($_POST['csrf_token']);

    $username  = Security::clean($_POST['username']);
    $password  = $_POST['password']; 
    // 1. Grab the message from the session
    $success_msg = $_SESSION['success_msg'] ?? '';

    // 2. Delete it instantly so it only shows once!
    unset($_SESSION['success_msg']);
    // --- STRICT ROLE SECURITY CHECK ---
    if ($_SESSION['role'] == '2') {
        $role = Security::clean($_POST['role']);
    } else {
        $role = '1'; // Force new users created by staff to just be staff
    }
    $full_name = Security::clean($_POST['full_name']);
    $email     = Security::clean($_POST['email']);
    $phone     = Security::clean($_POST['phone']);
    $job_title = Security::clean($_POST['job_title']);
    $basic_salary = floatval($_POST['basic_salary']);
    
    // NEW: Joining Date
    $joining_date = !empty($_POST['joining_date']) ? Security::clean($_POST['joining_date']) : date('Y-m-d');

    if (strlen($password) < 6) {
        $message = "<div class='alert alert-danger bg-danger bg-opacity-25 text-white border-danger'>Password must be at least 6 characters.</div>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $db = (new Database())->getConnection();
            $sql = "INSERT INTO users (username, password, role, full_name, email, phone, job_title, basic_salary, joining_date) 
                    VALUES (:username, :password, :role, :full_name, :email, :phone, :job_title, :basic_salary, :joining_date)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':username'  => $username,
                ':password'  => $hashed_password,
                ':role'      => $role,
                ':full_name' => $full_name,
                ':email'     => $email,
                ':phone'     => $phone,
                ':job_title' => $job_title,
                ':basic_salary' => $basic_salary,
                ':joining_date' => $joining_date
            ]);
             // Save message and safely redirect using JavaScript
            $_SESSION['success_msg'] = "Expense added successfully!";
            header("Location: user-add");
            exit();

            $message = "<div class='alert alert-success bg-success bg-opacity-25 text-white border-success'>User account created successfully!</div>";
            Security::logActivity("Created new user account: " . $username);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                 $message = "<div class='alert alert-danger bg-danger bg-opacity-25 text-white border-danger'>Error: Username already exists.</div>";
            } else {
                 $message = "<div class='alert alert-danger bg-danger bg-opacity-25 text-white border-danger'>Database Error: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<div class="container-fluid">
    <a href="./" class="text-white-50 text-decoration-none mb-3 d-inline-block hover-white">
        <i class="bi bi-arrow-left me-2"></i> Back to Users
    </a>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-box">
                <div class="text-center mb-4 border-bottom border-light border-opacity-10 pb-3">
                    <div class="avatar-icon mx-auto mb-3">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h4 class="text-white fw-bold mb-0">Create New User</h4>
                    <p class="text-white-50 small">Grant system access to staff or admins</p>
                </div>
                
                <?php echo $message; ?>

                <form method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">

                    <h6 class="text-secondary mb-3 text-uppercase fw-bold" style="font-size: 0.8rem;"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Full Name</label>
                            <input type="text" name="full_name" class="form-control glass-input" required placeholder="John Doe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Job Title / Designation</label>
                            <input type="text" name="job_title" class="form-control glass-input" placeholder="e.g. Account Manager">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control glass-input" placeholder="john@basmatrooq.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Phone Number</label>
                            <input type="tel" name="phone" class="form-control glass-input" placeholder="+966 5X XXX XXXX">
                        </div>
                        <div class="col-md-6 border-top border-secondary border-opacity-25 pt-3 mt-3">
                            <label class="form-label text-secondary small fw-bold"><i class="bi bi-cash-stack me-2"></i>Basic Salary (Monthly)</label>
                            <div class="input-group">
                                <span class="input-group-text glass-input border-end-0 text-white-50">SAR</span>
                                <input type="number" step="0.01" name="basic_salary" class="form-control glass-input border-start-0 ps-2" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6 border-top border-secondary border-opacity-25 pt-3 mt-3">
                            <label class="form-label text-secondary small fw-bold"><i class="bi bi-calendar-check me-2"></i>Joining Date</label>
                            <div class="input-group">
                                <input type="text" name="joining_date" class="form-control glass-input rooq-date" data-hide-buttons="true" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-secondary mb-3 text-uppercase fw-bold mt-4" style="font-size: 0.8rem;"><i class="bi bi-shield-lock me-2"></i>Account Security</h6>
                    <div class="row g-3 mb-4 p-3 rounded" style="background: rgba(0,0,0,0.2);">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">System Username</label>
                            <div class="input-group">
                                <span class="input-group-text glass-input border-end-0 text-white-50"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control glass-input border-start-0 ps-2" required placeholder="Username">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small fw-bold">Access Level</label>
                            <select name="role" class="form-select glass-input" <?php echo ($_SESSION['role'] != '2') ? 'disabled' : 'required'; ?>>
                                <option value="" <?php echo ($_SESSION['role'] == '2') ? 'selected disabled' : ''; ?>>Select Role...</option>
                                <option value="1" <?php echo ($_SESSION['role'] != '2') ? 'selected' : ''; ?>>Staff (Standard Access)</option>
                                
                                <?php if ($_SESSION['role'] == '2'): ?>
                                    <option value="2">Admin (Full Access)</option>
                                <?php endif; ?>
                            </select>
                            
                            <?php if ($_SESSION['role'] != '2'): ?>
                                <input type="hidden" name="role" value="1">
                                <div class="form-text text-warning small mt-1"><i class="bi bi-shield-lock me-1"></i>Only Admins can assign Admin roles.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-bold">Initial Password</label>
                            <div class="input-group">
                                <span class="input-group-text glass-input border-end-0 text-white-50"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" class="form-control glass-input border-start-0 ps-2" required placeholder="••••••••">
                            </div>
                            <div class="form-text text-white-50 small mt-1">Minimum 6 characters required.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-rooq-primary w-100 py-3 fw-bold mt-2">Create User Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>