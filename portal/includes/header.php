<?php
// portal/includes/header.php
require_once __DIR__ . '/../../app/Config/Config.php';
require_once __DIR__ . '/../../app/Helpers/Security.php';
require_once __DIR__ . '/../../app/Helpers/Translator.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// --- SMART GLOBAL LOGIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "public/login.php");
    exit();
}

// Get the current folder the user is in
$current_path = $_SERVER['SCRIPT_NAME'];

// 1. If a Client tries to open a '/portal/' page, send them to their dashboard
if ($_SESSION['role'] === 'client' && strpos($current_path, '/portal/') !== false) {
    header("Location: ../management/dashboard.php");
    exit();
}

// 2. If Staff/Admin tries to open a '/management/' (client) page, send them to portal
if (in_array($_SESSION['role'], ['1', '2']) && strpos($current_path, '/management/') !== false) {
    header("Location: ../portal/dashboard.php");
    exit();
}

// ========================================================================
// LANGUAGE SWITCHING LOGIC
// ========================================================================
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    
    // Redirect back to the same page without the ?lang= parameter to keep the URL clean
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $redirect_url);
    exit();
}

$lang = $_SESSION['lang'] ?? 'en';
$dir = ($lang == 'ar') ? 'rtl' : 'ltr';

// Fetch Translator
$translator = new Translator();
$text = $translator->getTranslation($lang);
// ========================================================================

// Fetch names from session
$username = $_SESSION['username'] ?? 'User';
$full_name = $_SESSION['full_name'] ?? $username; 

// Determine role text
$role_text = match ((string) ($_SESSION['role'] ?? '')) {
    '2' => 'Admin',
    '1' => 'Staff',
    default => 'Client',
};

// Current URL for language toggle link
$current_url = strtok($_SERVER["REQUEST_URI"], '?');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta id="base_url_meta" content="<?php echo BASE_URL; ?>">
    <title>Dashboard | Basmat Rooq</title>
    
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/img/favicon-32x32.png" type="image/x-icon" />
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/img/favicon-32x32.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css">
    
    <?php if($dir == 'rtl'): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.rtl.min.css">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/rtl.css">
    <?php endif; ?>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/theme.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="portal-body">
<div id="global-loader" class="global-loader">
    <div class="rooq-spinner"></div>
</div>
<header class="portal-header sticky-top">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-link text-white d-lg-none p-0" id="sidebarToggle">
                <i class="bi bi-list fs-2"></i>
            </button>
            <a href="dashboard.php" class="text-decoration-none d-flex align-items-center">
                <img src="<?php echo BASE_URL; ?>assets/img/logo.png" height="50" alt="Logo" style="filter: brightness(0) invert(1);">
            </a>
        </div>

        <?php if ($_SESSION['role'] !== 'client'): ?>
        <div class="search-container d-none d-md-block mx-auto position-relative">
            <form action="clients.php" method="GET" autocomplete="off">
                <div class="input-group glass-search">
                    <span class="input-group-text bg-transparent border-0 text-white-50"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="desktopSearchInput" class="form-control bg-transparent border-0 text-white" placeholder="Search licenses, clients..." aria-label="Search">
                </div>
            </form>
            <div id="desktopSearchResults" class="search-results-dropdown d-none"></div>
        </div>
        <?php endif;?>

        <div class="d-flex align-items-center gap-sm-4 gap-2 ">
            
            <?php if ($_SESSION['role'] !== 'client'): ?>
                <button class="btn btn-link text-white p-0 d-md-none opacity-75 hover-gold" onclick="toggleMobileSearch()">
                    <i class="bi bi-search fs-5"></i>
                </button>
            <?php endif;?>

            <!-- <a href="<?php echo $current_url; ?>?lang=<?php echo ($lang == 'en' ? 'ar' : 'en'); ?>" 
               class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold d-none d-sm-block">
                <i class="bi bi-globe me-1"></i> <?php echo ($lang == 'en' ? 'عربي' : 'English'); ?>
            </a>
             -->
            <!-- <a href="<?php echo $current_url; ?>?lang=<?php echo ($lang == 'en' ? 'ar' : 'en'); ?>" 
               class="text-white opacity-75 hover-gold d-block d-sm-none text-decoration-none">
                <i class="bi bi-globe fs-5"></i>
            </a> -->
            <div class="dropdown">
                <div class="position-relative d-block" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell text-white fs-5 opacity-75 hover-gold"></i>
                    
                    <span id="liveNotificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-dark <?php echo (($unread_count ?? 0) > 0) ? '' : 'd-none'; ?>" style="font-size: 0.6rem; transition: 0.3s;">
                        <?php echo ($unread_count ?? 0); ?>
                    </span>
                </div>
                
                <ul class="dropdown-menu dropdown-menu-end glass-dropdown mt-3 shadow-lg border-0" style="background: rgba(20, 20, 20, 0.95); width: 320px;">
                    <li class="px-3 py-2 border-bottom border-secondary border-opacity-25">
                        <span class="text-gold fw-bold small text-uppercase"><i class="bi bi-chat-dots me-2"></i>Recent Messages</span>
                    </li>
                    
                    <div id="liveNotificationList">
                        <?php if (($unread_count ?? 0) > 0): ?>
                            <?php foreach (($notifications ?? []) as $notif): ?>
                                <li>
                                    <a class="dropdown-item py-3 px-3 border-bottom border-light border-opacity-10 text-white hover-white" href="chat.php?client_id=<?php echo $notif['client_id']; ?>" style="white-space: normal;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold small"><?php echo htmlspecialchars($notif['sender_name']); ?></span>
                                            <span class="text-gold" style="font-size: 0.65rem;"><?php echo date('h:i A', strtotime($notif['created_at'])); ?></span>
                                        </div>
                                        <div class="text-white-50 small text-truncate" style="max-width: 270px;">
                                            "<?php echo htmlspecialchars($notif['message']); ?>"
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li><a class="dropdown-item text-center text-gold small py-2 fw-bold" href="chat.php">View All Messages</a></li>
                        <?php else: ?>
                            <li><div class="dropdown-item text-white-50 small py-4 text-center">No new messages</div></li>
                        <?php endif; ?>
                    </div>
                </ul>
            </div>

            <div class="dropdown">
                <div class="profile-trigger-refined d-flex align-items-center gap-1" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="text-end d-none d-lg-block">
                        <div class="user-name-text"><?php echo htmlspecialchars($full_name); ?></div>
                        <div class="user-role-text text-uppercase"><?php echo $role_text; ?></div>
                    </div>
                    <div class="avatar-circle-refined">
                        <?php echo strtoupper(substr($full_name, 0, 2)); ?>
                    </div>
                    <i class="bi bi-chevron-down dropdown-chevron" style=" margin-left: 5px;"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end glass-dropdown mt-3 shadow-lg border-0" style="background: rgba(20, 20, 20, 0.95);">
                    <li class="d-lg-none px-3 py-2 text-white fw-bold border-bottom border-secondary border-opacity-25 mb-2">
                        <?php echo htmlspecialchars($full_name); ?> <br>
                        <small class="text-gold"><?php echo $role_text; ?></small>
                    </li>
                    <?php if ($_SESSION['role'] !== 'client') :?>
                    <li><a class="dropdown-item text-white-50 hover-white" href="settings.php"><i class="bi bi-person-gear me-2 text-gold"></i> <?php echo $text['settings']; ?></a></li>
                    <li><a class="dropdown-item text-white-50 hover-white" href="activity-logs.php"><i class="bi bi-activity me-2 text-gold"></i> <?php echo $text['activity_logs']; ?></a></li>
                    <li><hr class="dropdown-divider bg-light opacity-10"></li>
                    <?php endif;?>
                    <li><a class="dropdown-item text-danger fw-bold" href="<?php echo BASE_URL; ?>public/logout.php"><i class="bi bi-box-arrow-right me-2"></i> <?php echo $text['logout']; ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
<?php if ($_SESSION['role'] !== 'client'): ?>
<div id="mobileSearchOverlay">
    <div class="glass-search-popup p-4 position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="text-white fw-bold m-0">Search</h5>
            <button type="button" class="btn-close btn-close-white" onclick="toggleMobileSearch()"></button>
        </div>
        <form action="clients.php" method="GET" autocomplete="off">
            <div class="input-group border-bottom border-secondary">
                <span class="input-group-text bg-transparent border-0 text-gold ps-0"><i class="bi bi-search fs-5"></i></span>
                <input type="text" name="search" id="mobileSearchInput" class="form-control bg-transparent border-0 text-white shadow-none fs-5" placeholder="Type name..." autofocus>
                <button class="btn btn-sm btn-gold rounded-pill px-3" type="submit">GO</button>
            </div>
        </form>
        <div id="mobileSearchResults" class="search-results-dropdown d-none" style="top: 140px; width: 88%; left: 6%;"></div>
    </div>
</div>
<?php endif;?>
<div class="d-flex portal-wrapper">
    <?php require_once 'sidebar.php'; ?>
    <main class="w-100 p-4">