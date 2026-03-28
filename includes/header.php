<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../app/Auth/AutoLogin.php';
checkAutoLogin();
$root = dirname(__DIR__);

// Load Core Dependencies
require_once $root . '/app/Config/Config.php';
require_once $root . '/app/Helpers/Translator.php';

// Language Switching Logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'en';
$isRTL = ($lang === 'ar');

// Initialize Translation Helper
$translator = new Translator();
$text = $translator->getTranslation($lang);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAYENOR | <?php echo $text['hero_title']; ?></title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/img/favicon-32x32.png" type="image/x-icon" />
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/img/favicon-32x32.png" type="image/x-icon" />
    
    <?php if ($isRTL): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.rtl.min.css">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/rtl.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?php echo BASE_URL;?>assets/css/bootstrap.min.css">
    <?php endif; ?>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/theme.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/mobile.css">
</head>
<body>
<div id="global-loader" class="global-loader">
    <div class="rooq-spinner"></div>
</div>

<nav class="navbar py-3 shadow-sm" style="background-color: var(--rooq-primary); border-bottom: 3px solid var(--rooq-secondary); z-index: 1050; position: relative;">
    <div class="container d-flex justify-content-between align-items-center flex-nowrap">
        
        <a class="navbar-brand m-0 p-0 d-flex align-items-center" href="<?php echo BASE_URL; ?>" style="width: 35%; max-width: 160px; min-width: 100px;">
            <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="FAYENOR" style="width: 100%; height: auto; filter: brightness(0) invert(1);">
        </a>
        
        <div class="d-flex align-items-center gap-2 gap-md-3">
            
            <a href="?lang=<?php echo ($lang == 'en' ? 'ar' : 'en'); ?>" 
               class="btn btn-outline-light rounded-pill px-3 px-md-4 py-1 py-md-2" 
               style="font-size: clamp(0.75rem, 2vw, 1rem); white-space: nowrap;">
                <?php echo ($lang == 'en' ? 'العربية' : 'English'); ?>
            </a>
            
            <a href="<?php echo BASE_URL; ?>public/login" 
               class="btn btn-rooq-primary rounded-pill px-3 px-md-4 py-1 py-md-2 fw-bold shadow-sm" 
               style="font-size: clamp(0.75rem, 2vw, 1rem); white-space: nowrap;">
                <?php echo $text['login']; ?>
            </a>
            
        </div>
    </div>
</nav>