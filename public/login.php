<?php
// public/login.php

if (session_status() === PHP_SESSION_NONE) session_start();
$root = dirname(__DIR__);
require_once __DIR__ . '/../app/Auth/AutoLogin.php';
checkAutoLogin();
// Load configuration and the Translator class
require_once $root . '/app/Config/Config.php';
require_once $root . '/app/Helpers/Translator.php';
require_once __DIR__ . '/../app/Helpers/Security.php'; // Load Security Helper

// Logic to catch the button click (?lang=ar)
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
// --- ALREADY LOGGED IN CHECK ---
if (isset($_SESSION['user_id'])) {
    // If Admin or Staff, go to portal
    if (in_array($_SESSION['role'], ['1', '2'])) {
        header("Location: ../portal/dashboard");
        exit();
    } 
    // If Client, go to client dashboard
    elseif ($_SESSION['role'] === 'client') {
        header("Location: ../management/dashboard");
        exit();
    }
}
// Initialize variables to prevent warnings
$lang = $_SESSION['lang'] ?? 'en'; 
$translator = new Translator();
$text = $translator->getTranslation($lang);

// Determine Direction (RTL for Arabic)
$dir = ($lang == 'ar') ? 'rtl' : 'ltr';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | FAYENOR</title> 
    <!-- <link rel="shortcut icon" href="<php echo BASE_URL; ?>assets/img/favicon.png" type="image/png" />
    <link rel="icon" href="<hp echo BASE_URL; ?>assets/img/favicon.png" type="image/png" /> -->

    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/img/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Segoe+UI:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        body { margin: 0; padding: 0; font-family: <?php echo ($lang == 'ar' ? "'Cairo', sans-serif" : "'Segoe UI', sans-serif"); ?>; }
        /* Default (Light Theme) Styles */


        /* Dark Theme Styles */
        @media (prefers-color-scheme: dark) {
            
        }
        /* Subtle hover effect for the back button */
        .btn-back-subtle {
            color: #555;
            transition: all 0.3s ease;
        }
        .btn-back-subtle:hover {
            color: var(--rooq-primary);
            transform: translateX(<?php echo ($lang == 'ar' ? '5px' : '-5px'); ?>);
        }
    </style>
</head>
<body>

<div id="global-loader" class="global-loader">
    <div class="rooq-spinner"></div>
</div>

<div class="login-wrapper">
    
    <div class="login-brand-side">
        <div class="text-center position-relative" style="z-index: 2;">
            <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="FAYENOR" class="brand-logo-img mb-4" style="max-width: 180px; filter: brightness(0) invert(1);">
            <h2 class="fw-bold mb-2 text-white">FAYENOR</h2>
            <p class="text-white opacity-75 mb-4">Contracting Co. Ltd.</p>
            <div style="width: 50px; height: 3px; background: var(--rooq-secondary); margin: 0 auto;"></div>
            <p class="mt-4 small text-white opacity-75 d-none d-md-block">
                <?php echo ($lang == 'ar' ? 'بوابة العملاء الآمنة لخدمات الاستثمار' : 'Secure Client Portal for Investment Services'); ?>
            </p>
        </div>
    </div>

    <div class="login-form-side position-relative">
        
        <div class="position-absolute top-0 start-0 p-4">
            <a href="<?php echo BASE_URL; ?>" class="text-decoration-none btn-back-subtle d-flex align-items-center gap-2 fw-bold">
                <i class="bi bi-arrow-<?php echo ($lang == 'ar' ? 'right' : 'left'); ?> fs-4"></i>
                <span class="d-none d-sm-inline"><?php echo ($lang == 'ar' ? 'العودة للموقع' : 'Back to Home'); ?></span>
            </a>
        </div>
        
        <div class="position-absolute top-0 end-0 p-4">
            <a href="?lang=<?php echo ($lang == 'en' ? 'ar' : 'en'); ?>" class="btn btn-sm btn-rooq-outline rounded-pill  px-4 me-2 fw-bold">
                <i class="bi bi-globe me-1"></i> <?php echo ($lang == 'en' ? 'العربية' : 'English'); ?>
            </a>
        </div>

        <div class="login-form-container" style="width: 100%; max-width: 400px; margin-top: 40px;">
            <div class="mb-5 text-center text-sm-start">
                <h2 class="fw-bold text-dark"><?php echo ($lang == 'ar' ? 'تسجيل الدخول' : 'Welcome Back'); ?></h2>
                <p class="text-muted"><?php echo ($lang == 'ar' ? 'يرجى إدخال بيانات الاعتماد الخاصة بك' : 'Please enter your credentials to access your dashboard.'); ?></p>
            </div>

            <form action="auth_process" method="POST">
                
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger small p-3 text-center border-0 bg-danger bg-opacity-10 text-danger rounded-3 fw-bold mb-4">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']); // Clear error after showing
                        ?>
                    </div>
                <?php endif; ?>

                <div class="form-floating mb-3 shadow-sm rounded-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required style="border: 1px solid rgba(0,0,0,0.1);">
                    <label for="username" class="text-muted"><i class="bi bi-person me-2"></i><?php echo ($lang == 'ar' ? 'اسم المستخدم' : 'Username'); ?></label>
                </div>

                <div class="form-floating mb-3 shadow-sm rounded-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required style="border: 1px solid rgba(0,0,0,0.1);">
                    <label for="password" class="text-muted"><i class="bi bi-key me-2"></i><?php echo ($lang == 'ar' ? 'كلمة المرور' : 'Password'); ?></label>
                </div>

                <div class="form-check mb-4 d-flex align-items-center">
                    <input class="form-check-input mt-0 me-2 shadow-sm" type="checkbox" value="1" id="rememberMe" name="remember_me" style="cursor: pointer;">
                    <label class="form-check-label text-muted small" for="rememberMe" style="cursor: pointer; padding-top: 2px;">
                        <?php echo ($lang == 'ar' ? 'تذكرني' : 'Remember me'); ?>
                    </label>
                </div>
                <button type="submit" class="btn btn-rooq-primary w-100 py-3 fw-bold shadow">
                    <?php echo ($lang == 'ar' ? 'دخول' : 'Sign In'); ?> <i class="bi bi-box-arrow-in-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script>
    const favicon = document.getElementById("favicon");
    const darkModeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)");

    // Function to swap the favicon
    function updateFavicon(e) {
        if (e.matches) {
            favicon.href = "assets/img/favicon-white.png"; // Dark theme
        } else {
            favicon.href = "assets/img/favicon.png"; // Light theme
        }
    }

    // 1. Check the theme immediately when the page loads
    updateFavicon(darkModeMediaQuery);

    // 2. Listen for theme changes (if the user changes their PC theme while the site is open)
    darkModeMediaQuery.addEventListener("change", updateFavicon);
</script>
</body>
</html>