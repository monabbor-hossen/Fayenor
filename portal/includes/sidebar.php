<?php
// Get the current page name (e.g., 'dashboard.php')
$current_page = basename($_SERVER['PHP_SELF']);
// Default to true so Admins/Staff always see it
$can_see_expenses = true; 

if ($_SESSION['role'] === 'client') {
    // 1. Get the account ID
    $account_id = $_SESSION['account_id'] ?? $_SESSION['user_id'];
    
    // 2. Fetch the permission (CRITICAL FIX: Added ->fetch())
    $stmtExp = $db->prepare("SELECT show_expenses FROM clients WHERE account_id = ? LIMIT 1");
    $stmtExp->execute([$account_id]);
    $resExp = $stmtExp->fetch(PDO::FETCH_ASSOC);
    
    // 3. Set the boolean based on the database value
    $can_see_expenses = ($resExp && $resExp['show_expenses'] == 1);
}
?>

<aside class="portal-sidebar" id="portalSidebar">
    <div class="sidebar-content h-100 py-4">
        <p class="px-4 text-white-50 small text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">Main Menu</p>
        <?php if ($_SESSION['role'] === 'client') :?>
        <ul class="nav flex-column mb-auto mt-3 w-100">

            <li class="nav-item mb-2">
                <a href="dashboard.php"
                    class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-grid-1x2 fs-5 me-3 text-gold"></i>
                    <span class="fw-bold">My Dashboard</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="billing.php"
                    class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['billing.php','project-details.php']) ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-receipt fs-5 me-3 text-gold"></i>
                    <span class="fw-bold">Billing & Invoices</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="chat.php"
                    class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'chat.php') ? 'active-glass active bg-rooq-primary text-white shadow-sm' : 'text-white-50 hover-white'; ?> d-flex align-items-center rounded px-3 py-2"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-chat-dots fs-5 me-3 text-gold"></i>
                    <span class="fw-bold flex-grow-1">Support Messages</span>
                    <?php if (($unread_count ?? 0) > 0): ?>
                    <span class="badge bg-danger rounded-pill shadow-sm"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if ($can_see_expenses): ?>
            <li class="nav-item mb-2">
                <a href="expenses.php"
                    class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'expenses.php') ? 'active-glass fw-bold' : ''; ?> d-flex align-items-center rounded px-3 py-2"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-wallet2 fs-5 me-3 text-gold"></i>
                    <span class="flex-grow-1">My Expenses</span>
                </a>
            </li>
            <?php endif;?>
        </ul>
        <?php else: ?>

        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active-glass' : ''; ?> rounded"
                    href="dashboard.php">
                    <i class="bi bi-grid-fill me-3"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo (in_array($current_page, ['clients.php', 'client-add.php', 'client-edit.php'])) ? 'active-glass' : ''; ?> rounded"
                    href="clients.php">
                    <i class="bi bi-people-fill me-3"></i> Clients
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="chat.php"
                    class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'chat.php') ? 'active-glass active bg-rooq-primary text-white shadow-sm' : 'text-white-50 hover-white'; ?> d-flex align-items-center rounded px-3 py-2"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-chat-dots fs-5 me-3 text-gold"></i>
                    <span class="fw-bold flex-grow-1">Client Messages</span>
                    <?php if (($unread_count ?? 0) > 0): ?>
                    <span class="badge bg-danger rounded-pill shadow-sm"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <p class="px-4 text-white-50 small text-uppercase fw-bold mb-3 mt-4" style="letter-spacing: 1px;">System</p>

        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link <?php echo (in_array($current_page, ['users.php', 'user-add.php', 'user-edit.php'])) ? 'active-glass' : ''; ?> rounded"
                    href="users.php">
                    <i class="bi bi-shield-lock-fill me-3"></i> User Access
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="payroll.php"
                    class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'payroll.php' || basename($_SERVER['PHP_SELF']) == 'user-payroll.php') ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-cash-coin me-3"></i>
                    <span class="fw-bold">Payroll</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="expenses.php"
                    class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'expenses.php') ? 'active-glass' : ''; ?> rounded">
                    <i class="bi bi-wallet2 me-3"></i>
                    <span class="flex-grow-1">Expenses</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'settings.php') ? 'active-glass' : ''; ?> rounded"
                    href="settings.php">
                    <i class="bi bi-gear-fill me-3"></i> Settings
                </a>
            </li>
            <?php if ($_SESSION['role'] == '2'): ?>
            <div class="text-uppercase text-white-50 small fw-bold px-3 mb-2"
                style="font-size: 0.7rem; letter-spacing: 1px;">Security</div>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo ($current_page == 'default-contract.php') ? 'active-glass' : ''; ?> rounded"
                    href="default-contract.php">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Contract Template</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="activity-logs.php"
                    class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'activity-logs.php') ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-activity me-3"></i>
                    <span class="fw-bold">Activity Logs</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="audit-finance.php"
                    class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'audit-finance.php') ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-bank me-3"></i>
                    <span class="fw-bold">Financial Audit</span>
                </a>
            </li>
            <?php endif; ?>

        </ul>

        <?php endif; ?>
    </div>
</aside>