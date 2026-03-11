/* ==========================================================================
   1. EDIT PAGE: RICH TEXT EDITOR INITIALIZATION
   ========================================================================== */
// Only run this if jQuery is loaded (prevents errors on the viewing page)
if (typeof jQuery !== 'undefined') {
    $(document).ready(function () {
        if ($('.rich-editor').length) {
            $('.rich-editor').summernote({
                tabsize: 2,
                height: 140,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                ]
            });
        }
    });
}

function generatePDF() {
    const element = document.getElementById('contract-content');
    const pages = document.querySelectorAll('.document-page');
    const logoImg = document.querySelector('.brand-logo');

    // --- STEP 1: CONVERT LOGO TO WHITE USING CANVAS ---
    // Create an invisible canvas
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    // Match the canvas size to the original image
    canvas.width = logoImg.naturalWidth;
    canvas.height = logoImg.naturalHeight;

    // Draw the original image onto the canvas
    ctx.drawImage(logoImg, 0, 0);

    // Paint over the non-transparent pixels with pure white
    ctx.globalCompositeOperation = 'source-in';
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Swap the logo's source to this new white image data
    const originalSrc = logoImg.src;
    logoImg.src = canvas.toDataURL('image/png');
    // --- 1. BAKE THE FILTER INTO THE WATERMARK ---
    const watermarkImg = document.querySelector('.watermark');
    const originalWatermarkSrc = watermarkImg.src;

    // Only run if the image has successfully loaded
    if (watermarkImg.naturalWidth > 0) {
        canvas.width = watermarkImg.naturalWidth;
        canvas.height = watermarkImg.naturalHeight;

        // Apply the exact same CSS filter directly to the Canvas!
        ctx.filter =
            'brightness(0) drop-shadow(2px 0 0 white) drop-shadow(-2px 0 0 white) drop-shadow(0 2px 0 white) drop-shadow(0 -2px 0 white) invert(1)';

        // Draw the image onto the canvas with the filter permanently baked in
        ctx.drawImage(watermarkImg, 0, 0, canvas.width, canvas.height);

        // Swap the watermark's source to this new perfectly filtered image
        watermarkImg.src = canvas.toDataURL('image/png');

        // Temporarily disable the CSS filter so it doesn't double-apply
        watermarkImg.style.filter = 'none';
    }

    // 1. Prepare for PDF
    pages.forEach(p => {
        p.style.marginBottom = '0px';
        p.style.boxShadow = 'none';
        p.style.height = '296.9mm';
        p.style.overflow = 'hidden';
    });
    element.style.overflow = 'hidden';
    // Fetch the client name safely from the HTML data attribute
    const clientName = element.getAttribute('data-client-name') || 'Client';
    const filename = `Service_License_Agreement_${clientName}.pdf`;

    const opt = {
        margin: 0,
        filename: filename,
        image: {
            type: 'jpeg',
            quality: 1
        },
        html2canvas: {
            scale: 2,
            useCORS: true,
            scrollY: 0,
            windowWidth: 1218
        },
        jsPDF: {
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait'
        }
    };

    // 2. Generate and then return normal web view styling
    html2pdf().set(opt).from(element).save().then(() => {
        pages.forEach(p => {
            p.style.marginBottom = '40px';
            p.style.boxShadow = '0 15px 30px rgba(0,0,0,0.2)';
            p.style.height = '297mm';
        });
        element.style.overflow = 'visible';
        // Put the original image back so the website looks normal!
        logoImg.src =
            originalSrc; // Restore the original watermark image and CSS filter for the web view
        watermarkImg.src = originalWatermarkSrc;
        watermarkImg.style.filter = '';
    });
}