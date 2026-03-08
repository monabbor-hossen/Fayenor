<?php
// Contract Variables
$clientName = "Mr. Bablu Ahmed";
$date = "February 18, 2026";
$iqamaNo = "2497876264";
$serviceProvider = "Flyburj Travels and Tourism Company";
$serviceFee = "15,000";
$timelineDays = "40";
$companyLocation = "BURAYDAH, AL QASSIM-SAUDI ARABIA";
$year = "2026";

// Base64 converter so DomPDF never loses the images
function imageToBase64($path) {
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return ''; 
}

// ⚠️ Ensure these JPG/PNG files exist in your folder! Do NOT use .webp!
$bgImage = imageToBase64(__DIR__ . '/fullPage.jpg'); 
$logoImage = imageToBase64(__DIR__ . '../assets/img/logo.png'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service License Agreement</title>
    <style>
        /* 1. Define margins to perfectly fit inside the letterhead */
        @page {
            margin: 45mm 20mm 40mm 20mm; /* Top Right Bottom Left */
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333333;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            /* REMOVED buggy CSS background-image! */
        }

        /* 2. THE MAGIC DOMPDF WATERMARK FIX */
        #bg-watermark {
            position: fixed;
            top: -45mm;    /* Negative offset perfectly counteracts the @page top margin */
            left: -20mm;   /* Negative offset counteracts the @page left margin */
            width: 210mm;  /* Exact width of A4 */
            height: 297mm; /* Exact height of A4 */
            z-index: -1000; /* Pushes it firmly behind all text */
        }

        /* 3. White block to hide the letterhead watermark on the Cover Page */
        .cover-blocker {
            position: absolute;
            top: -45mm;
            left: -20mm;
            width: 210mm;
            height: 297mm;
            background-color: white;
            z-index: -999;
        }

        /* 4. Formatting */
        .page-break { page-break-after: always; }

        h2 {
            color: #800020;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        
        p { margin-top: 5px; margin-bottom: 5px; }
        ul, ol { padding-left: 20px; margin-top: 5px; margin-bottom: 10px; }
        li { margin-bottom: 4px; }

        .doc-title {
            color: #800020;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        /* Tables for side-by-side layout */
        .layout-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            margin-top: 10px;
        }
        .layout-table td {
            vertical-align: top;
            width: 50%;
            font-size: 12px;
        }

        .bank-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11px;
        }
        .bank-table th, .bank-table td {
            border: 1px solid #D4AF37;
            padding: 6px 10px;
            text-align: left;
        }
        .bank-table th {
            background-color: #800020;
            color: white;
            width: 35%;
        }

        /* Stop tables and lists from splitting in half */
        table, tr, td, ul, ol, li {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <img src="<?php echo $bgImage; ?>" id="bg-watermark" alt="Background Letterhead" />

    <div class="page-break">
        <div class="cover-blocker"></div>
        
        <div style="text-align: center; padding-top: 50px; position: relative; z-index: 10;">
            <div style="border-top: 2px solid #800020; width: 85%; margin: 0 auto 50px auto;"></div>

            <img src="<?php echo $logoImage; ?>" style="width: 220px; margin-bottom: 40px;" alt="Flyburj Logo" />
            
            <div style="color: #800020; font-size: 20px; font-weight: bold; text-transform: uppercase; margin-bottom: 60px;">SERVICE LICENSE AGREEMENT</div>
            
            <div style="font-size: 13px; color: #333; margin-bottom: 5px;">Prepared For</div>
            <div style="font-size: 16px; font-weight: bold; color: #800020; margin-bottom: 10px;">Client Name: <?php echo $clientName; ?></div>
            <div style="font-size: 13px; color: #333; margin-bottom: 120px;">Date: <?php echo $date; ?></div>
            
            <div style="font-size: 15px; font-weight: bold; color: #800020; margin-bottom: 5px;"><?php echo strtoupper(explode(" ", $serviceProvider)[0]); ?> COMPANY</div>
            <div style="font-size: 12px; color: #333; margin-bottom: 2px;"><?php echo $companyLocation; ?></div>
            <div style="font-size: 12px; color: #333;"><?php echo $year; ?></div>
        </div>
    </div>

    <div class="page-break">
        <div class="doc-title">SERVICE LICENSE AGREEMENT</div>
        <div style="font-size: 12px; font-weight: bold; color: #555; margin-bottom: 20px;">(MISA Service License Facilitation)</div>

        <p>This Service Agreement ("Agreement") is made between:</p>
        
        <table class="layout-table">
            <tr>
                <td>
                    <strong>Service Provider:</strong><br>
                    <?php echo $serviceProvider; ?><br>
                    Email: info@flyburjco.com
                </td>
                <td>
                    <strong>Client Name:</strong><br>
                    <?php echo $clientName; ?><br>
                    Iqama No: <?php echo $iqamaNo; ?>
                </td>
            </tr>
        </table>

        <h2>1. OBJECTIVE OF THE AGREEMENT</h2>
        <p>The objective of this Agreement is to appoint Flyburj Travels & Tourism Company as a facilitator and consultant to assist the Client in obtaining a MISA Service License in the Kingdom of Saudi Arabia, in accordance with the regulations of the Ministry of Investment of Saudi Arabia (MISA).</p>
        
        <h2>2. PERMITTED ACTIVITIES UNDER SERVICE LICENSE</h2>
        <p>Service-based activities including consultancy, IT services, management support, marketing, training, professional advisory services, and other non-trading activities as approved by MISA.</p>
        
        <h2>3. SCOPE OF SERVICES</h2>
        <p>The Service Provider shall be responsible for completing the following services for the Client:</p>
        <ol>
            <li>Arrangement of a Foreign Company (as required by MISA)</li>
            <li>Application and approval of MISA Service License</li>
            <li>Preparation of Articles of Association</li>
            <li>Trade Name Reservation</li>
            <li>Issuance of Commercial Registration (CR)</li>
            <li>Muqeem Registration</li>
            <li>Qiwa Registration</li>
            <li>Saudi Post (National Address) Registration</li>
            <li>Zakat & VAT Registration</li>
            <li>Chamber of Commerce Registration</li>
        </ol>
    </div>

    <div class="page-break">
        <h2>4. CLIENT DOCUMENTATION REQUIREMENTS</h2>
        <p>To proceed with the services, the Client is required to provide only the following:</p>
        <ul>
            <li>Original Passport Copy</li>
            <li>Passport Size Photograph</li>
        </ul>
        <p><em>The Client confirms that all documents provided are valid, accurate, and genuine.</em></p>

        <h2>5. SERVICE CHARGES</h2>
        <p>The Total professional service fee for this Agreement is <strong>SAR <?php echo $serviceFee; ?> (Saudi Riyals Fifteen Thousand only)</strong>.</p>
        <p style="color:red; font-size: 11px;"><strong>Note:</strong> All kind of Service provide by Flyburj Travels & Tourism Co. & All kind of Govt. Payments are to be borne by the Client.</p>

        <h2>6. PAYMENT TERMS</h2>
        <ul>
            <li>The Client shall pay 25% of the total service fees upon signing this Agreement, 25% upon issuance of the Investment License in Saudi Arabia, and the remaining 50% upon issuance of the Commercial Register.</li>
            <li>If the client fails to fulfill the payment obligations, the company reserves the right to retain the official documents and papers until the full payment is settled and the final settlement is completed.</li>
            <li>Should there be any changes to the government license fee, the agreement amount will be revised accordingly.</li>
            <li>The contractual relationship with our company ends once the commercial register and investment license have been obtained and the agreed-upon services have been completed.</li>
        </ul>
        
        <table class="bank-table">
            <tr>
                <th>NAME OF BANK</th>
                <td>SAUDI NATIONAL BANK</td>
            </tr>
            <tr>
                <th>ACCOUNT NUMBER</th>
                <td>38300000264001</td>
            </tr>
            <tr>
                <th>ACCOUNT IBAN NUMBER</th>
                <td>SA5010000038300000264001</td>
            </tr>
            <tr>
                <th>AC NAME</th>
                <td>Flyburj Travel and Tourism Company</td>
            </tr>
        </table>
    </div>

    <div>
        <h2>8. TIMELINE & DELAYS</h2>
        <p>The estimated timeline to complete the MISA Service License and related registrations is approximately <strong><?php echo $timelineDays; ?> working days</strong>, subject to timely submission of documents and payments by the Client.</p>
        
        <p>The Service Provider shall not be held responsible for any delay caused by:</p>
        <ul>
            <li>Government system or server issues</li>
            <li>Portal downtime or technical errors</li>
            <li>Scheduled or unscheduled system maintenance</li>
        </ul>
        <p>Any delays arising from external or governmental processes shall not be considered a breach of this Agreement and will not affect the agreed service charges.</p>

        <h2>9. ACCEPTANCE & SIGNATURES</h2>
        <p>By signing below, both Parties agree to the terms and conditions of this Agreement.</p>

        <table class="layout-table" style="margin-top: 50px;">
            <tr>
                <td>
                    <strong>For Flyburj Travels And Tourism Company</strong><br><br><br>
                    Name: Saifullah<br>
                    <div style="border-bottom: 1px solid #2D2D2D; width: 85%; margin: 30px 0 10px 0;"></div>
                    Signature
                </td>
                <td>
                    <strong>For the Client</strong><br><br><br>
                    Name: <?php echo $clientName; ?><br>
                    <div style="border-bottom: 1px solid #2D2D2D; width: 85%; margin: 30px 0 10px 0;"></div>
                    Signature<br><br><br>
                    Date: ________________________
                </td>
            </tr>
        </table>
    </div>

</body>
</html>