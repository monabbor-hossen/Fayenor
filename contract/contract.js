function generatePDF() {
    const element = document.getElementById('contract-content');
    const pages = document.querySelectorAll('.document-page');
    const logoImg = document.querySelector('.brand-logo');

    // Fetch the client name safely from the HTML data attribute
    const clientName = element.getAttribute('data-client-name') || 'Client';
    const filename = `Service_License_Agreement_${clientName}.pdf`;

    // --- STEP 1: CONVERT LOGO TO WHITE USING CANVAS ---
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    canvas.width = logoImg.naturalWidth;
    canvas.height = logoImg.naturalHeight;
    ctx.drawImage(logoImg, 0, 0);

    ctx.globalCompositeOperation = 'source-in';
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    const originalSrc = logoImg.src;
    logoImg.src = canvas.toDataURL('image/png');

    // --- BAKE THE FILTER INTO THE WATERMARK ---
    const watermarkImg = document.querySelector('.watermark');
    const originalWatermarkSrc = watermarkImg.src;

    if (watermarkImg.naturalWidth > 0) {
        canvas.width = watermarkImg.naturalWidth;
        canvas.height = watermarkImg.naturalHeight;

        ctx.filter = 'brightness(0) drop-shadow(2px 0 0 white) drop-shadow(-2px 0 0 white) drop-shadow(0 2px 0 white) drop-shadow(0 -2px 0 white) invert(1)';
        ctx.drawImage(watermarkImg, 0, 0, canvas.width, canvas.height);

        watermarkImg.src = canvas.toDataURL('image/png');
        watermarkImg.style.filter = 'none';
    }

    // Prepare for PDF
    pages.forEach(p => {
        p.style.marginBottom = '0px';
        p.style.boxShadow = 'none';
        p.style.height = '296.9mm';
        p.style.overflow = 'hidden';
    });
    element.style.overflow = 'hidden';

    const opt = {
        margin: 0,
        filename: filename,
        image: { type: 'jpeg', quality: 1 },
        html2canvas: { scale: 2, useCORS: true, scrollY: 0, windowWidth: 1218 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    // Generate and restore normal web view styling
    html2pdf().set(opt).from(element).save().then(() => {
        pages.forEach(p => {
            p.style.marginBottom = '40px';
            p.style.boxShadow = '0 15px 30px rgba(0,0,0,0.2)';
            p.style.height = '297mm';
        });
        element.style.overflow = 'visible';
        
        // Put the original images back
        logoImg.src = originalSrc; 
        watermarkImg.src = originalWatermarkSrc;
        watermarkImg.style.filter = '';
    });
}