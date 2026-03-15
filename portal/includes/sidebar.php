<?php
// Get the current page name (e.g., 'dashboard.php')
$current_page = basename($_SERVER['PHP_SELF']);
// Default to true so Admins/Staff always see it
$can_see_expenses = true; 

if ($_SESSION['role'] === 'client') {
    // 1. Get the account ID
    $account_id = $_SESSION['account_id'] ?? $_SESSION['user_id'];
    
    // CRITICAL FIX: Ensure $db exists before querying!
    require_once __DIR__ . '/../../app/Config/Database.php';
    if (!isset($db)) {
        $db = (new Database())->getConnection();
    }
    
    // 2. Fetch the permission
    $stmtExp = $db->prepare("SELECT show_expenses FROM clients WHERE account_id = ? LIMIT 1");
    $stmtExp->execute([$account_id]);
    $resExp = $stmtExp->fetch(PDO::FETCH_ASSOC);
    
    // 3. Set the boolean based on the database value
    $can_see_expenses = ($resExp && $resExp['show_expenses'] == 1);
}
?>

<aside class="portal-sidebar" id="portalSidebar">
    <div class="sidebar-content h-100 py-4">
        <p class="px-4 text-white-50 small text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">
            <?php echo $text['main_menu']; ?>
        </p>
        
        <?php if ($_SESSION['role'] === 'client') :?>
        <ul class="nav flex-column mb-auto mt-3 w-100">
            <li class="nav-item mb-2">
                <a href="<?php echo BASE_URL; ?>management/dashboard"
                    class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-grid-1x2 fs-5 me-1 text-gold"></i>
                    <span class="fw-bold"><?php echo $text['dashboard']; ?></span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo BASE_URL; ?>management/billing"
                    class="nav-link <?= in_array($current_page, ['billing.php','project-details.php']) ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-receipt fs-5 me-1 text-gold"></i>
                    <span class="fw-bold"><?php echo $text['billing']; ?></span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo BASE_URL; ?>management/chat"
                    class="nav-link <?php echo ($current_page == 'chat.php') ? 'active-glass ' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-chat-dots fs-5 me-1 text-gold"></i>
                    <span class="fw-bold flex-grow-1"><?php echo $text['support_chat']; ?></span>
                    <?php if (($unread_count ?? 0) > 0): ?>
                    <span class="badge bg-danger rounded-pill shadow-sm"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if ($can_see_expenses): ?>
            <li class="nav-item mb-2">
                <a href="<?php echo BASE_URL; ?>management/expenses"
                    class="nav-link <?php echo ($current_page == 'expenses.php') ? 'active-glass' : ''; ?>  rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-wallet2 fs-5 me-1 text-gold"></i>
                    <span class="flex-grow-1"><?php echo $text['expenses']; ?></span>
                </a>
            </li>
            <?php endif;?>
        </ul>
        
        <?php else: ?>

        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active-glass' : ''; ?> rounded"
                    href="<?php echo BASE_URL; ?>portal/dashboard">
                    <i class="bi bi-grid-fill me-1"></i> <?php echo $text['dashboard']; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (in_array($current_page, ['clients.php', 'client-add.php', 'client-edit.php'])) ? 'active-glass' : ''; ?> rounded"
                    href="<?php echo BASE_URL; ?>portal/clients/clients">
                    <i class="bi bi-people-fill me-1"></i> <?php echo $text['clients']; ?>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo BASE_URL; ?>portal/communication/chat"
                    class="nav-link <?php echo ($current_page == 'chat.php') ? 'active-glass active bg-rooq-primary text-white shadow-sm' : 'text-white-50 hover-white'; ?> d-flex align-items-center rounded px-3 py-2"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-chat-dots fs-5 me-1 text-gold"></i>
                    <span class="fw-bold flex-grow-1"><?php echo $text['client_messages']; ?></span>
                    <?php if (($unread_count ?? 0) > 0): ?>
                    <span class="badge bg-danger rounded-pill shadow-sm"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <p class="px-4 text-white-50 small text-uppercase fw-bold mb-3 mt-4" style="letter-spacing: 1px;">
            <?php echo $text['system']; ?>
        </p>

        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link <?php echo (in_array($current_page, ['users.php', 'user-add.php', 'user-edit.php'])) ? 'active-glass' : ''; ?> rounded"
                    href="<?php echo BASE_URL; ?>portal/users/users">
                    <i class="bi bi-shield-lock-fill me-1"></i> <?php echo $text['user_access']; ?>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo BASE_URL; ?>portal/finance/payroll"
                    class="nav-link <?php echo ($current_page == 'payroll.php' || $current_page == 'user-payroll.php') ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-cash-coin me-1"></i>
                    <span class="fw-bold"><?php echo $text['payroll']; ?></span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo BASE_URL; ?>portal/finance/expenses"
                    class="nav-link <?php echo ($current_page == 'expenses.php') ? 'active-glass' : ''; ?> rounded">
                    <i class="bi bi-wallet2 me-1"></i>
                    <span class="flex-grow-1"><?php echo $text['expenses']; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'settings.php') ? 'active-glass' : ''; ?> rounded"
                    href="<?php echo BASE_URL; ?>portal/admin/settings">
                    <i class="bi bi-gear-fill me-1"></i> <?php echo $text['settings']; ?>
                </a>
            </li>
            
            <?php if ($_SESSION['role'] == '2'): ?>
            <div class="text-uppercase text-white-50 small fw-bold px-3 mb-2 mt-3" style="font-size: 0.7rem; letter-spacing: 1px;">
                <?php echo $text['security']; ?>
            </div>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo ($current_page == 'default-contract.php') ? 'active-glass' : ''; ?> rounded"
                    href="<?php echo BASE_URL; ?>portal/contracts/default-contract">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <span><?php echo $text['contract_template']; ?></span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo BASE_URL; ?>portal/admin/activity-logs"
                    class="nav-link <?php echo ($current_page == 'activity-logs.php') ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-activity me-1"></i>
                    <span class="fw-bold"><?php echo $text['activity_logs']; ?></span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo BASE_URL; ?>portal/finance/audit-finance"
                    class="nav-link <?php echo ($current_page == 'audit-finance.php') ? 'active-glass' : ''; ?> rounded"
                    style="transition: all 0.3s ease;">
                    <i class="bi bi-bank me-1"></i>
                    <span class="fw-bold"><?php echo $text['financial_audit']; ?></span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <?php endif; ?>
    </div>
</aside>