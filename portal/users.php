<?php
// portal/users.php
require_once 'includes/header.php';
require_once __DIR__ . '/../app/Config/Database.php';

$db = (new Database())->getConnection();

// ADDED joining_date, resigning_date, is_active to the query
$query = "SELECT id, full_name, role, is_active, created_at, joining_date, resigning_date 
          FROM users 
          WHERE role IN ('1', '2') 
          ORDER BY role DESC, created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getRoleName($roleId) {
    return match($roleId) {
        '2' => '<span class="badge bg-gold text-dark">Admin</span>',
        '1' => '<span class="badge bg-light text-dark opacity-75">Staff</span>',
        default => '<span class="badge bg-secondary">Unknown</span>'
    };
}
?>

<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-white fw-bold">System Users</h3>
        <a href="user-add.php" class="btn btn-rooq-primary btn-sm px-4 rounded-pill shadow-lg">
            <i class="bi bi-plus-lg me-2"></i> Add New User
        </a>
    </div>

    <div class="card-box p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle" style="background: transparent;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.05);">
                        <th class="py-3 ps-4 text-gold text-uppercase small">ID</th>
                        <th class="py-3 text-gold text-uppercase small">User Identity</th>
                        <th class="py-3 text-gold text-uppercase small">Access Level</th>
                        <th class="py-3 text-center text-gold text-uppercase small">Login Status</th>
                        <th class="py-3 text-gold text-uppercase small">Dates</th>
                        <th class="py-3 text-end pe-4 text-gold text-uppercase small">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): 
                            // LOGIC: Check if user has resigned
                            $is_resigned = (!empty($user['resigning_date']) && strtotime($user['resigning_date']) <= time());
                            // Dim row if resigned
                            $row_style = $is_resigned ? 'opacity: 0.4; filter: grayscale(100%); transition: all 0.3s ease;' : '';
                            // Highlight on hover anyway
                            $hover_class = $is_resigned ? 'onmouseover="this.style.opacity=\'0.8\';" onmouseout="this.style.opacity=\'0.4\';"' : '';
                        ?>
                        <tr style="<?php echo $row_style; ?>" <?php echo $hover_class; ?>>
                            <td class="ps-4 text-white-50">#<?php echo $user['id']; ?></td>
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-small me-3">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-white d-block"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                        <?php if ($is_resigned): ?>
                                            <span class="badge bg-danger mt-1 opacity-75" style="font-size: 0.65rem;">Resigned: <?php echo date('M d, Y', strtotime($user['resigning_date'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td><?php echo getRoleName($user['role']); ?></td>
                            
                            <td class="text-center">
                                <div class="form-check form-switch m-0 d-flex justify-content-center" title="Toggle Login Access">
                                    <input class="form-check-input form-check-input-gold cursor-pointer" type="checkbox" 
                                           onchange="toggleLoginStatus('user', <?php echo $user['id']; ?>, this)" 
                                           <?php echo (isset($user['is_active']) && $user['is_active'] == 1) ? 'checked' : ''; ?>
                                           <?php echo ($_SESSION['user_id'] == $user['id']) ? 'disabled' : ''; ?>>
                                </div>
                            </td>

                            <td class="text-white-50 small">
                                <div title="Joined Date">
                                    <i class="bi bi-calendar-check me-2 text-success opacity-75"></i>
                                    <?php echo !empty($user['joining_date']) ? date('M d, Y', strtotime($user['joining_date'])) : date('M d, Y', strtotime($user['created_at'])); ?>
                                </div>
                            </td>

                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    
                                    <a href="user-payroll.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-outline-success border-0 opacity-75 hover-opacity-100" 
                                       title="Manage Payroll">
                                        <i class="bi bi-wallet2"></i>
                                    </a>

                                    <a href="user-edit.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-outline-light border-0 opacity-50 hover-opacity-100" 
                                       title="Edit User">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <?php if($_SESSION['user_id'] != $user['id']): ?>
                                        <form action="user-delete.php" method="POST" onsubmit="confirmFormSubmit(event, this, 'Are you sure you want to completely delete this user? This action cannot be undone.');" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">
                                            <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                            
                                            <?php if ($_SESSION['role'] == '2'): ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 opacity-50 hover-opacity-100" title="Delete User">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif;?>
                                        </form>
                                    <?php endif; ?>
                                    
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-white-50">No admin or staff users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>

<?php require_once 'includes/footer.php'; ?>