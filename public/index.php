<?php require_once '../includes/header.php'; ?>

<header id="hero-section" class="min-vh-100 d-flex align-items-center text-center position-relative overflow-hidden" style="background: radial-gradient(circle at center, #800020 0%, #3d000f 100%);">
    
    <canvas id="hero-canvas"></canvas>
    
    <div class="container hero-content">
        <div class="glass-panel p-5 reveal mx-auto interactive-layer">
            
            <div class="mb-4">
                <img src="<?php echo BASE_URL; ?>assets/img/Saudi_Vision_2030_logo.svg" alt="Saudi Vision 2030" style="height: 90px; object-fit: contain; filter: brightness(0) invert(1) drop-shadow(0 0 10px rgba(255,255,255,0.2));">
            </div>

            <h1 class="display-1 fw-bold text-uppercase text-white text-shadow-glow"><?php echo $text['hero_title']; ?></h1>
            <p class="lead mb-4 text-white-50"><?php echo $text['hero_desc']; ?></p>
            <div class="nav-buttons">
                <a href="#about" class="btn btn-outline-light rounded-pill px-4 py-2 me-2"><?php echo $text['explore_us']; ?></a>
                <a href="login.php" class="btn btn-rooq-primary rounded-pill px-4 py-2"><?php echo $text['login']; ?></a>
            </div>
        </div>
    </div>
</header>

<section id="about" class="section-padding">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 reveal">
                <h2 class="text-gold display-5 fw-bold mb-4" ><?php echo $text['about_us']; ?></h2>
                <p class="fs-5 opacity-75"><?php echo $text['about_desc']; ?></p>
            </div>
            <div class="col-lg-6 reveal">
                <div class="glass-panel text-center p-4">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/Ministry_of_Investment_Logo-Dark.svg" style="height: 100px;" alt="MISA Logo">
                    <h4 class="mt-3 fw-bold">MISA Strategic Partner</h4>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" style="background: rgba(255,255,255,0.02);">
    <div class="container">
        <h2 class="text-center display-5 fw-bold mb-5 text-gold"><?php echo $text['what_we_do']; ?></h2>
        <div class="row g-4">
            <div class="col-md-4 reveal">
                <div class="glass-panel h-100 p-4">
                    <h4 class="text-gold mb-3"><?php echo $text['service_1']; ?></h4>
                    <p class="small opacity-75"><?php echo $text['service_1_desc']; ?></p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="glass-panel h-100 p-4">
                    <h4 class="text-gold mb-3"><?php echo $text['service_2']; ?></h4>
                    <p class="small opacity-75"><?php echo $text['service_2_desc']; ?></p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="glass-panel h-100 p-4">
                    <h4 class="text-gold mb-3"><?php echo $text['service_3']; ?></h4>
                    <p class="small opacity-75"><?php echo $text['service_3_desc']; ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding text-center">
    <div class="container">
        <div class="glass-panel p-5 reveal mx-auto" style="max-width: 800px;">
            <h2 class="text-gold mb-4 fw-bold"><?php echo $text['contact_us']; ?></h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <p class="mb-1 text-gold fw-bold"><?php echo $text['email_label']; ?></p>
                    <p>Kh70007980@gmail.com</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 text-gold fw-bold"><?php echo $text['location_label']; ?></p>
                    <p><?php echo $text['location_val']; ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>