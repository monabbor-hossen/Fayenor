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
                <h2 class="text-gold display-5 fw-bold mb-4" style="color: #D4AF37;"><?php echo $text['about_us']; ?></h2>
                <p class="fs-5 opacity-75"><?php echo $text['about_desc']; ?></p>
            </div>
            <div class="col-lg-6 reveal">
                <div class="glass-panel text-center p-4">
                    <img src="<?php echo BASE_URL; ?>assets/img/icons/Ministry_of_Investment_Logo-Dark.svg" style="height: 100px; filter: brightness(0) invert(1);" alt="MISA Logo">
                    <h4 class="mt-3 fw-bold">MISA Strategic Partner</h4>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding" style="background: rgba(255,255,255,0.02);">
    <div class="container">
        <h2 class="text-center display-5 fw-bold mb-5 text-gold" style="color: #D4AF37;"><?php echo $text['what_we_do']; ?></h2>
        <div class="row g-4">
            <div class="col-md-4 reveal">
                <div class="glass-panel h-100 p-4">
                    <h4 class="text-gold mb-3" style="color: #D4AF37;"><?php echo $text['service_1']; ?></h4>
                    <p class="small opacity-75"><?php echo $text['service_1_desc']; ?></p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="glass-panel h-100 p-4">
                    <h4 class="text-gold mb-3" style="color: #D4AF37;"><?php echo $text['service_2']; ?></h4>
                    <p class="small opacity-75"><?php echo $text['service_2_desc']; ?></p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="glass-panel h-100 p-4">
                    <h4 class="text-gold mb-3" style="color: #D4AF37;"><?php echo $text['service_3']; ?></h4>
                    <p class="small opacity-75"><?php echo $text['service_3_desc']; ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-padding text-center">
    <div class="container">
        <div class="glass-panel p-5 reveal mx-auto" style="max-width: 800px;">
            <h2 class="text-gold mb-4 fw-bold" style="color: #D4AF37;"><?php echo $text['contact_us']; ?></h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <p class="mb-1 text-gold fw-bold" style="color: #D4AF37;"><?php echo $text['email_label']; ?></p>
                    <p>Kh70007980@gmail.com</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 text-gold fw-bold" style="color: #D4AF37;"><?php echo $text['location_label']; ?></p>
                    <p><?php echo $text['location_val']; ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    #hero-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0; 
    }

    #hero-section::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 200px; 
        background: linear-gradient(to bottom, transparent 0%, #3d000f 100%);
        z-index: 5; 
        pointer-events: none; 
    }

    .hero-content {
        position: relative;
        z-index: 10; 
        pointer-events: none; 
    }
    
    .hero-content .interactive-layer {
        pointer-events: auto; 
    }

    .glass-panel {
        background: rgba(61, 0, 15, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 15px;
    }

    .text-shadow-glow {
        text-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
    }

    .reveal { 
        opacity: 0; 
        transform: translateY(30px); 
        transition: 0.8s ease-out; 
    }
    .reveal.visible { 
        opacity: 1; 
        transform: translateY(0); 
    }
    .section-padding {
        padding-top: 5rem;
        padding-bottom: 5rem;
    }
</style>

<script>
    // 1. SCROLL REVEAL ANIMATION
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));

    // 2. 3D TORUS KNOT CANVAS ANIMATION
    window.addEventListener('load', () => {
        const canvas = document.getElementById('hero-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        let width, height, centerX, centerY;
        
        function resizeCanvas() {
            const heroSection = document.getElementById('hero-section');
            if (!heroSection) return;
            width = canvas.width = window.innerWidth;
            height = canvas.height = heroSection.offsetHeight;
            centerX = width / 2;
            centerY = height / 2;
        }
        
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        let time = 0;
        const fov = 400; 
        const p = 3; 
        const q = 7; 
        const resolution = 600; 

        function animate() {
            time += 0.004;

            ctx.fillStyle = 'rgba(61, 0, 15, 0.2)'; 
            ctx.fillRect(0, 0, width, height);

            ctx.globalCompositeOperation = 'lighter';

            const strands = 5; 

            for (let s = 0; s < strands; s++) {
                ctx.beginPath();
                
                let opacity = 0.3 + (s * 0.1);
                ctx.strokeStyle = s % 2 === 0 ? `rgba(212, 175, 55, ${opacity})` : `rgba(243, 213, 106, ${opacity})`;
                ctx.lineWidth = 1.5;

                for (let i = 0; i <= resolution; i++) {
                    let t = (i / resolution) * Math.PI * 2;
                    let angle = t + time; 
                    let strandOffset = (s * 0.2); 

                    let radius = (width < 768 ? 100 : 200) + Math.sin(time * 2) * 20;
                    let r = radius * (2 + Math.cos(q * angle + strandOffset));
                    
                    let x3d = r * Math.cos(p * angle);
                    let y3d = r * Math.sin(p * angle);
                    let z3d = radius * Math.sin(q * angle + strandOffset);

                    let rotX = time * 0.5;
                    let rotY = time * 0.3;

                    let xRotY = x3d * Math.cos(rotY) - z3d * Math.sin(rotY);
                    let zRotY = x3d * Math.sin(rotY) + z3d * Math.cos(rotY);

                    let yRotX = y3d * Math.cos(rotX) - zRotY * Math.sin(rotX);
                    let zRotX = y3d * Math.sin(rotX) + zRotY * Math.cos(rotX);

                    let scale = fov / (fov + zRotX); 
                    let screenX = centerX + xRotY * scale;
                    let screenY = centerY + yRotX * scale;

                    if (i === 0) {
                        ctx.moveTo(screenX, screenY);
                    } else {
                        ctx.lineTo(screenX, screenY);
                    }
                }
                ctx.stroke();
            }

            ctx.globalCompositeOperation = 'source-over';
            requestAnimationFrame(animate);
        }

        animate();
    });
</script>

<?php require_once '../includes/footer.php'; ?>