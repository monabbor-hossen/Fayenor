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

    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/img/favicon.svg">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/theme.css?v=2.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/mobile.css?v=2.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-body <?php echo ($lang == 'ar' ? 'lang-ar' : ''); ?>">

<!-- Global page loader -->
<div id="global-loader" class="global-loader">
    <div class="rooq-spinner"></div>
</div>

<!-- Ambient background orbs -->
<div class="login-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>

<div class="login-wrapper">

    <!-- ================================
         BRAND PANEL (Left / Desktop)
         ================================ -->
    <div class="login-brand-side">
        <div class="brand-content">
            <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="FAYENOR" class="brand-logo-img">
            <h1 class="brand-title">FAYENOR</h1>
            <p class="brand-subtitle">Contracting Co. Ltd.</p>
            <div class="brand-divider"></div>
            <p class="brand-tagline">
                <?php echo ($lang == 'ar'
                    ? 'بوابة العملاء الآمنة لخدمات الاستثمار والعقود'
                    : 'Your secure portal for investment services, contracts & client management.'); ?>
            </p>

            <div class="brand-features">
                <div class="brand-feature-item">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span><?php echo ($lang == 'ar' ? 'اتصال مشفر وآمن' : 'End-to-end encrypted access'); ?></span>
                </div>
                <div class="brand-feature-item">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span><?php echo ($lang == 'ar' ? 'تتبع الصفقات في الوقت الفعلي' : 'Real-time deal tracking'); ?></span>
                </div>
                <div class="brand-feature-item">
                    <i class="bi bi-file-earmark-text-fill"></i>
                    <span><?php echo ($lang == 'ar' ? 'إدارة العقود والمستندات' : 'Contract & document management'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================
         FORM PANEL (Right)
         ================================ -->
    <div class="login-form-side">

        <!-- Utility bar: Back + Lang -->
        <div class="login-utility-bar">
            <a href="<?php echo BASE_URL; ?>" class="btn-back-subtle">
                <i class="bi bi-arrow-<?php echo ($lang == 'ar' ? 'right' : 'left'); ?>"></i>
                <span><?php echo ($lang == 'ar' ? 'العودة للموقع' : 'Back to Home'); ?></span>
            </a>
            <a href="?lang=<?php echo ($lang == 'en' ? 'ar' : 'en'); ?>" class="btn-lang-switch">
                <i class="bi bi-globe2"></i>
                <?php echo ($lang == 'en' ? 'العربية' : 'English'); ?>
            </a>
        </div>

        <!-- Mobile-only brand strip -->
        <div class="mobile-brand-strip">
            <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="FAYENOR">
            <div class="m-title">FAYENOR</div>
            <div class="m-sub">Contracting Co. Ltd.</div>
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <div class="login-card-header">
                <div class="card-icon">
                    <i class="bi bi-person-lock"></i>
                </div>
                <h2><?php echo ($lang == 'ar' ? 'تسجيل الدخول' : 'Welcome Back'); ?></h2>
                <p><?php echo ($lang == 'ar' ? 'يرجى إدخال بيانات الاعتماد الخاصة بك' : 'Sign in to your account to continue.'); ?></p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="login-alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="auth_process" method="POST">

                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">

                <!-- Username -->
                <div class="login-input-group">
                    <label for="username">
                        <i class="bi bi-person-fill"></i>
                        <?php echo ($lang == 'ar' ? 'اسم المستخدم' : 'Username'); ?>
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="login-input-field"
                        placeholder="<?php echo ($lang == 'ar' ? 'أدخل اسم المستخدم' : 'Enter your username'); ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <!-- Password -->
                <div class="login-input-group">
                    <label for="password">
                        <i class="bi bi-key-fill"></i>
                        <?php echo ($lang == 'ar' ? 'كلمة المرور' : 'Password'); ?>
                    </label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input-field"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            style="padding-<?php echo ($dir === 'rtl' ? 'left' : 'right'); ?>: 46px;"
                            required
                        >
                        <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                            <i class="bi bi-eye-slash" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember me -->
                <div class="login-check-row mb-4">
                    <input type="checkbox" value="1" id="rememberMe" name="remember_me">
                    <label for="rememberMe">
                        <?php echo ($lang == 'ar' ? 'تذكرني' : 'Keep me signed in'); ?>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login">
                    <?php echo ($lang == 'ar' ? 'دخول' : 'Sign In'); ?>
                    <i class="bi bi-box-arrow-in-right"></i>
                </button>

            </form>
        </div>
        <!-- end .login-card -->

    </div>
    <!-- end .login-form-side -->

</div>
<!-- end .login-wrapper -->

<script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>

<script>
    // Password show/hide toggle
    (function () {
        const toggle = document.getElementById('togglePassword');
        const pwdInput = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (!toggle || !pwdInput) return;

        toggle.addEventListener('click', function () {
            const isHidden = pwdInput.type === 'password';
            pwdInput.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    })();
</script>
</body>
</html>