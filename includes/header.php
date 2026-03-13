
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <title>Basmat Rooq | <?php echo $text['hero_title']; ?></title>
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
    <style>
        
        /* * ==========================================
         * REQUIRED CSS FOR CANVAS EXTRACTION
         * ==========================================
         * If you move this to another app, you need 
         * this CSS to position the canvas behind your content.
         */
        #hero-section {
            position: relative;
            height: 100vh;
            min-height: 700px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: radial-gradient(circle at center, #800020 0%, #3d000f 100%);
        }

        #hero-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0; /* Behind the text */
        }

        .hero-content {
            position: relative;
            z-index: 10; /* Above the canvas */
            pointer-events: none; /* Let clicks pass through to canvas if needed */
        }
        
        .hero-content > * {
            pointer-events: auto; /* Re-enable clicks on buttons */
        }
    </style>
</head>
<body>
<div id="global-loader" class="global-loader">
    <div class="rooq-spinner"></div>
</div>
<nav class="navbar navbar-expand-lg" style="background-color: var(--rooq-burgundy); border-bottom: 3px solid var(--rooq-gold);">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="<?php echo BASE_URL; ?>">
            <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="Basmat Rooq" width="150" style="filter: brightness(0) invert(1);">
        </a>
        
        <div class="d-flex align-items-center ms-auto">
            <a href="?lang=<?php echo ($lang == 'en' ? 'ar' : 'en'); ?>" class="btn btn-outline-light rounded-pill px-4 me-2 ">
                <?php echo ($lang == 'en' ? 'العربية' : 'English'); ?>
            </a>
            <a href="<?php echo BASE_URL; ?>public/login.php" class="btn btn-rooq-primary ">
                <?php echo $text['login']; ?>
            </a>
        </div>
    </div>
</nav>