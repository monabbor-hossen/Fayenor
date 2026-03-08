<?php
// Contract Variables (Map these to your Basmat-Rooq database)
$clientName = "Mr. Bablu Ahmed";
$date = "February 18, 2026";
$iqamaNo = "2497876264";
$serviceProvider = "Flyburj Travels and Tourism Company";
$serviceFee = "15,000";
$timelineDays = "40";
$companyLocation = "BURAYDAH, AL QASSIM-SAUDI ARABIA";
$year = "2026";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service License Agreement - <?php echo $clientName; ?></title>
    <style>
        /* Basmat-Rooq Core Theme Variables */
        :root {
            --rooq-burgundy: #800020;
            --rooq-gold: #D4AF37;
            --rooq-dark: #2D2D2D;
            --text-color: #333333;
        }
         body {
            background-color: #e9ecef;
            color: var(--text-color);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column; /* Stack pages vertically */
            align-items: center;
            gap: 40px; /* Space between pages in web view */
        }

        /* A4 Document Container */
        .document-page:first-child {
            background-image: none;
        }
        .document-page {
            width: 210mm;
            min-height: 297mm;
            height: 297mm;
            background-image: url('rooq.webp'); 
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-color: white;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            position: relative;
            box-sizing: border-box;
            padding: 45mm 20mm 35mm 20mm; 
        }

        /* Content Formatting */
        .content {
            position: relative;
            z-index: 1;
            line-height: 1.6;
            font-size: 11pt;
            border-top: 3px solid var(--rooq-burgundy);
            margin-top: 15px;
        }

        /* Cover Page Specific Styling */
        .cover-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100%;
            min-height: 200mm;
            padding-top: 50mm;
        }

        .cover-logo {
            width: 250px;
            margin-bottom: 40px;
            /* Invert or adjust if the logo has a dark background like the uploaded JPG */
            mix-blend-mode: multiply; 
        }

        .doc-title {
            text-align: left;
            color: var(--rooq-burgundy);
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .cover-title {
            font-size: 28px;
            margin-bottom: 50px;
        }

        .doc-subtitle {
            text-align: center;
            font-size: 14px;
            margin-bottom: 0px;
            font-weight: bold;
            color: var(--rooq-dark);
        }
        p{
            margin-top:10px;
            margin-bottom: 5px;
        }
        h2 {
            color: var(--rooq-burgundy);
            font-size: 13pt;
            border-bottom: 1px dashed var(--rooq-gold);
            padding-bottom: 5px;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .parties-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 5px;
            background: rgba(255, 255, 255, 0.7);
            /* padding: 15px; */
            /* border-left: 4px solid var(--rooq-gold); */
            margin-bottom: 5px;
        }

        ul {
            padding-left: 20px;
        }

        .bank-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10pt;
            background: rgba(255, 255, 255, 0.8);
        }

        .bank-table th, .bank-table td {
            border: 1px solid var(--rooq-gold);
            padding: 8px 12px;
            text-align: left;
        }

        .bank-table th {
            background-color: var(--rooq-burgundy);
            color: white;
            width: 40%;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 40px;
            padding-top: 20px;
        }

        .signature-box {
            padding: 10px;
        }

        .signature-line {
            border-bottom: 1px solid var(--rooq-dark);
            margin-top: 40px;
            margin-bottom: 10px;
            width: 100%;
        }

        /* Print Specific Styles */
        @media print {
            body {
                background: none;
                padding: 0;
                gap: 0;
                display: block;
            }
            .document-page {
                box-shadow: none;
                width: 100%;
                min-height: 297mm;
                padding: 45mm 20mm 35mm 20mm;
                page-break-after: always;
                margin: 0;
            }
        }
    </style>
</head>
<body>

<div class="document-page">
    <div class="content cover-content">
        <img src="../assets/img/logo.png" alt="Flyburj Logo" class="cover-logo">
        
        <div class="doc-title cover-title">SERVICE LICENSE AGREEMENT</div>
        
        <div style="font-size: 18px; color: var(--rooq-dark); margin-bottom: 10px;">Prepared For</div>
        <div style="font-size: 22px; font-weight: bold; color: var(--rooq-burgundy); margin-bottom: 10px;">Client Name: <?php echo $clientName; ?></div>
        <div style="font-size: 18px; color: var(--rooq-dark); margin-bottom: 80px;">Date: <?php echo $date; ?></div>
        
        <div style="font-size: 20px; font-weight: bold; color: var(--rooq-burgundy);"><?php echo strtoupper(explode(" ", $serviceProvider)[0]); ?> COMPANY</div>
        <div style="font-size: 16px; color: var(--rooq-dark);"><?php echo $companyLocation; ?></div>
        <div style="font-size: 16px; color: var(--rooq-dark);"><?php echo $year; ?></div>
    </div>
</div>

<div class="document-page">
    <div class="content">
        
        <div class="doc-title">SERVICE LICENSE AGREEMENT</div>
        <div class="doc-subtitle">(MISA Service License Facilitation)</div>

        <p>This Service Agreement ("Agreement") is made between:</p>
        
        <div class="parties-grid">
            <div>
                <strong>Service Provider:</strong><br>
                <?php echo $serviceProvider; ?><br>
                Email: info@flyburjco.com
            </div>
            <div>
                <strong>Client Name:</strong><br>
                <?php echo $clientName; ?><br>
                Iqama No: <?php echo $iqamaNo; ?>
            </div>
        </div>

        <h2>1. OBJECTIVE OF THE AGREEMENT</h2>
        <p>The objective of this Agreement is to appoint Flyburj Travels & Tourism Company as a facilitator and consultant to assist the
Client in obtaining a MISA Service License in the Kingdom of Saudi Arabia, in accordance with the regulations of the Ministry of
Investment of Saudi Arabia (MISA).</p>
        <h2>2. PERMITTED ACTIVITIES UNDER SERVICE LICENSE</h2>
        <p>Service-based activities including consultancy, IT services, management support, marketing, training, professional advisory
services, and other non-trading activities as approved by MISA
</p>
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
</div>

<div class="document-page">
    <div class="content">
        <h2>4. CLIENT DOCUMENTATION REQUIREMENTS</h2>
        <p>To proceed with the services, the Client is required to provide only the following:</p>
        <ul>
            <li>Original Passport Copy</li>
            <li>Passport Size Photograph</li>
        </ul>
        <p><em>The Client confirms that all documents provided are valid, accurate, and genuine.</em></p>

        <h2>5. SERVICE CHARGES</h2>
        <p>The Total professional service fee for this Agreement is <strong>SAR <?php echo $serviceFee; ?> (Saudi Riyals Fifteen Thousand only)</strong>.</p>
        <p><em style="color:red;">Note:  All kind of Service provide by Flyburj Travels & Tourism Co. & All kind of Govt. Payments are to be borne by
the Client. </em></p>

        <h2>6. PAYMENT TERMS</h2>
        <ul>
            
            <li>The Client shall pay 25% of the total service fees upon signing this Agreement, 25% upon issuance of the Investment
License in Saudi Arabia, and the remaining 50% upon issuance of the Commercial Register.</li>
            <li>If the client fails to fulfill the payment obligations, the company reserves the right to retain the official documents and
papers until the full payment is settled and the final settlement is completed.</li>            
<li>Should there be any changes to the government license fee, the agreement amount will be revised accordingly.</li>
<li>The contractual relationship with our company ends once the commercial register
and investment license have been obtained and the agreed-upon services have been completed.</li>


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
</div>

<div class="document-page">
    <div class="content">
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

        <div class="signature-grid">
            <div class="signature-box">
                <strong>For Flyburj Travels And Tourism Company</strong><br>
                Name: Saifullah
                <div class="signature-line"></div>
                Signature
            </div>
            <div class="signature-box">
                <strong>For the Client</strong><br>
                Name: <?php echo $clientName; ?>
                <div class="signature-line"></div>
                Signature<br><br>
                <strong>Date:</strong> _____________________
            </div>
        </div>

    </div>
</div>

</body>
</html>