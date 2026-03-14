<?php
// portal/settings.php
require_once 'includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];
$message = '';
// 1. Grab the message from the session
$success_msg = $_SESSION['success_msg'] ?? '';

// 2. Delete it instantly so it only shows once!
unset($_SESSION['success_msg']);
// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::checkCSRF($_POST['csrf_token'] ?? '');

    // 1. UPDATE PROFILE
    if (isset($_POST['update_profile'])) {
        $full_name = Security::clean($_POST['full_name']);
        $email = Security::clean($_POST['email']);
        $phone = Security::clean($_POST['phone']);

        try {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $email, $phone, $user_id])) {
                $message = "<div class='alert alert-success bg-success bg-opacity-25 text-white border-success'><i class='bi bi-check-circle me-2'></i>Profile updated successfully!</div>";
                Security::logActivity("Updated personal profile information");
                
                // Update Session variables so the top right name changes instantly
                $_SESSION['full_name'] = $full_name;
                 // Save message and safely redirect using JavaScript
                $_SESSION['success_msg'] = "Expense added successfully!";
                echo "<script>window.location.href='settings';</script>";
                exit();
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger bg-danger bg-opacity-25 text-white border-danger'>Error updating profile.</div>";
        }
    }

    // 2. UPDATE PASSWORD
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Fetch current password hash to verify
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($current_password, $user_data['password'])) {
            $message = "<div class='alert alert-danger bg-danger bg-opacity-25 text-white border-danger'><i class='bi bi-shield-x me-2'></i>Current password is incorrect!</div>";
        } elseif (strlen($new_password) < 6) {
            $message = "<div class='alert alert-warning bg-warning bg-opacity-25 text-white border-warning'><i class='bi bi-exclamation-triangle me-2'></i>New password must be at least 6 characters.</div>";
        } elseif ($new_password !== $confirm_password) {
            $message = "<div class='alert alert-warning bg-warning bg-opacity-25 text-white border-warning'><i class='bi bi-exclamation-triangle me-2'></i>New passwords do not match.</div>";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $message = "<div class='alert alert-success bg-success bg-opacity-25 text-white border-success'><i class='bi bi-shield-check me-2'></i>Password changed successfully!</div>";
                Security::logActivity("Changed account password");
                // Save message and safely redirect using JavaScript
                $_SESSION['success_msg'] = "Expense added successfully!";
                echo "<script>window.location.href='settings';</script>";
                exit();
            }
        }
    }
}

// --- FETCH CURRENT USER DATA ---
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h3 class="text-white fw-bold mb-0"><i class="bi bi-gear text-gold me-2"></i>Account Settings</h3>
        <p class="text-white-50 small mb-0">Manage your personal profile and security preferences.</p>
    </div>

    <?php echo $message; ?>

    <div class="row g-4">
        <div class="col-md-4 col-lg-3">
            <div class="card-box p-3 h-100">
                <div class="text-center mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                    <div class="avatar-icon bg-rooq-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <h5 class="text-white fw-bold mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <span class="badge bg-secondary bg-opacity-50 text-white-50"><?php echo htmlspecialchars($user['job_title'] ?? 'Staff Member'); ?></span>
                </div>

                <div class="nav flex-column nav-pills" id="settings-tabs" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active text-start text-white-50 hover-white py-3 mb-2 rounded" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab" style="transition: all 0.3s;">
                        <i class="bi bi-person-lines-fill me-2 text-gold"></i> Personal Profile
                    </button>
                    <button class="nav-link text-start text-white-50 hover-white py-3 rounded" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab" style="transition: all 0.3s;">
                        <i class="bi bi-shield-lock me-2 text-gold"></i> Security & Password
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-lg-9">
            <div class="card-box p-4 h-100">
                <div class="tab-content" id="settings-tabContent">
                    
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <h5 class="text-gold fw-bold mb-4 border-bottom border-secondary border-opacity-25 pb-2">Profile Information</h5>
                        <form method="POST" action="settings">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">
                            
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-white-50 small fw-bold">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control glass-input text-white-50" value="<?php echo htmlspecialchars($user['username']); ?>" disabled readonly title="Username cannot be changed">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white-50 small fw-bold">Full Name</label>
                                    <input type="text" name="full_name" class="form-control glass-input" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white-50 small fw-bold">Email Address</label>
                                    <input type="email" name="email" class="form-control glass-input" value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white-50 small fw-bold">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control glass-input" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-rooq-primary px-4 fw-bold">Save Changes</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <h5 class="text-gold fw-bold mb-4 border-bottom border-secondary border-opacity-25 pb-2">Change Password</h5>
                        <form method="POST" action="settings">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">
                            
                            <div class="row g-4 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label text-white-50 small fw-bold">Current Password <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password" class="form-control glass-input" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white-50 small fw-bold">New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="new_password" class="form-control glass-input" required minlength="6">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white-50 small fw-bold">Confirm New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control glass-input" required minlength="6">
                                </div>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-danger px-4 fw-bold"><i class="bi bi-shield-lock me-2"></i>Update Password</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Style for active tab to match your theme */
.nav-pills .nav-link.active {
    background-color: rgba(212, 175, 55, 0.1);
    color: #D4AF37 !important;
    border-left: 3px solid #D4AF37;
}
</style>

<?php require_once 'includes/footer.php'; ?>