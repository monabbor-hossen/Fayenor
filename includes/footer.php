
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#800020', // Burgundy
                            dark: '#3d000f',    
                            light: '#a61c3c'    
                        },
                        gold: {
                            DEFAULT: '#D4AF37', // Gold
                            light: '#f3d56a',
                            dark: '#aa8c2c'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        serif: ['Playfair Display', 'Georgia', 'serif'],
                    }
                }
            }
        }
    </script>

    <script>
        window.addEventListener('load', () => {
            
            // --- START OF CANVAS LOGIC ---

            const canvas = document.getElementById('hero-canvas');
            const ctx = canvas.getContext('2d');
            
            let width, height, centerX, centerY;
            
            // Function to make canvas full screen and update on resize
            function resizeCanvas() {
                // Gets the parent container's dimensions
                width = canvas.width = window.innerWidth;
                height = canvas.height = document.getElementById('hero-section').offsetHeight;
                centerX = width / 2;
                centerY = height / 2;
            }
            
            // Initial resize and listen for window changes
            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);

            // Time variable drives the autonomous movement
            let time = 0;

            // 3D Projection configuration
            const fov = 400; // Field of view (higher = flatter)

            /**
             * The mathematical heart of the pattern: The Torus Knot
             * A torus knot is defined by two integers (p, q).
             * p = number of times it wraps around the center.
             * q = number of times it wraps around the tube.
             */
            const p = 3; 
            const q = 7; 
            
            // Number of points to draw the continuous ribbon
            const resolution = 600; 

            function animate() {
                // 1. Increment time automatically (No mouse needed)
                // This makes the knot continuously rotate and flow
                time += 0.004;

                // 2. Clear the canvas (Using a highly transparent black to create a "motion blur / trail" effect)
                // If you want solid sharp lines, change 0.1 to 1.
                ctx.fillStyle = 'rgba(61, 0, 15, 0.2)'; 
                ctx.fillRect(0, 0, width, height);

                // 3. Make the lines overlap with a bright, glowing blending mode
                ctx.globalCompositeOperation = 'lighter';

                // We draw multiple "strands" to make it look like a thick ribbon of data
                const strands = 5; 

                for (let s = 0; s < strands; s++) {
                    ctx.beginPath();
                    
                    // The color shifts slightly based on the strand, mixing Gold and White-Gold
                    let opacity = 0.3 + (s * 0.1);
                    ctx.strokeStyle = s % 2 === 0 ? `rgba(212, 175, 55, ${opacity})` : `rgba(243, 213, 106, ${opacity})`;
                    ctx.lineWidth = 1.5;

                    for (let i = 0; i <= resolution; i++) {
                        // 't' goes from 0 to 2*PI representing the full loop
                        let t = (i / resolution) * Math.PI * 2;
                        
                        // Apply autonomous rotation based on 'time'
                        let angle = t + time; 
                        
                        // Add an offset for each strand to make them parallel
                        let strandOffset = (s * 0.2); 

                        // ----------------------------------------------------
                        // Mathematical Torus Knot Equations (3D Space: x, y, z)
                        // radius scales the entire shape up based on screen size
                        let radius = (width < 768 ? 100 : 200) + Math.sin(time * 2) * 20; // It "breathes" in and out

                        let r = radius * (2 + Math.cos(q * angle + strandOffset));
                        
                        // Base 3D Coordinates
                        let x3d = r * Math.cos(p * angle);
                        let y3d = r * Math.sin(p * angle);
                        let z3d = radius * Math.sin(q * angle + strandOffset);

                        // ----------------------------------------------------
                        // 3D Matrix Rotation
                        // Rotate around X and Y axis automatically over time
                        let rotX = time * 0.5;
                        let rotY = time * 0.3;

                        // Apply Y rotation
                        let xRotY = x3d * Math.cos(rotY) - z3d * Math.sin(rotY);
                        let zRotY = x3d * Math.sin(rotY) + z3d * Math.cos(rotY);

                        // Apply X rotation
                        let yRotX = y3d * Math.cos(rotX) - zRotY * Math.sin(rotX);
                        let zRotX = y3d * Math.sin(rotX) + zRotY * Math.cos(rotX);

                        // ----------------------------------------------------
                        // Project 3D coordinates down to 2D Screen Canvas
                        let scale = fov / (fov + zRotX); 
                        let screenX = centerX + xRotY * scale;
                        let screenY = centerY + yRotX * scale;

                        // Draw the line segment
                        if (i === 0) {
                            ctx.moveTo(screenX, screenY);
                        } else {
                            ctx.lineTo(screenX, screenY);
                        }
                    }
                    ctx.stroke();
                }

                // Reset blending mode for the next frame's background clear
                ctx.globalCompositeOperation = 'source-over';

                // Request the next frame to loop infinitely
                requestAnimationFrame(animate);
            }

            // Start the infinite loop
            animate();

            // --- END OF CANVAS LOGIC ---

            // UI Logic: Navbar solid background on scroll
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('bg-brand-dark/90', 'backdrop-blur-md', 'shadow-xl', 'border-gold/20');
                    navbar.classList.remove('bg-transparent', 'border-transparent');
                } else {
                    navbar.classList.remove('bg-brand-dark/90', 'backdrop-blur-md', 'shadow-xl', 'border-gold/20');
                    navbar.classList.add('bg-transparent', 'border-transparent');
                }
            });
        });
    </script>
</body>
</html>