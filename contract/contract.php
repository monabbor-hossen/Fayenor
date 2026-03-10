
<?php
session_start();

// 1. MUST LOAD CONFIG FIRST (This holds DB_HOST, DB_USER, etc)
require_once __DIR__ . '/../app/Config/Config.php';

// 2. THEN LOAD DATABASE
require_once __DIR__ . '/../app/Config/Database.php';

// 3. Check if a Client ID was passed in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>Error: No Client Selected.</h2><p>Please select a client from the portal to view their contract.</p></div>");
}

$client_id = intval($_GET['id']);

// 4. Fetch the client data from the database
try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT * FROM clients WHERE client_id = ? LIMIT 1");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>Error: Client Not Found.</h2></div>");
    }
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// 5. Map Database fields to the Contract Variables
$clientName      = $client['client_name'] ?? $client['company_name'];
$date            = date('F j, Y'); 
$iqamaNo         = "To Be Provided"; 
$serviceProvider = "Flyburj Travels and Tourism Company";
$serviceFee      = number_format($client['contract_value'] ?? 0, 2); 
$timelineDays    = "40"; 
$companyLocation = "BURAYDAH, AL QASSIM-SAUDI ARABIA";
$year            = date('Y'); 
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service License Agreement - <?php echo $clientName; ?></title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap');

        /* Core Theme Variables */
        :root {
            --rooq-burgundy: #800020;
            --rooq-gold: #D4AF37;
            --rooq-dark: #2D2D2D;
            --text-color: #333333;
            --doc-gray-text: #777777;
        }

        body {
            background-color: #e9ecef;
            color: var(--text-color);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 40px 0;
        }

        /* Container wrapper */
        #contract-content {
            display: block;
            width: 210mm;
            margin: 0 auto;
        }

        /* Floating Download Button */
        .download-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: var(--rooq-burgundy);
            color: white;
            border: 2px solid var(--rooq-gold);
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        /* EXACT A4 Document Pages */
        .document-page {
            width: 210mm;
            height: 297mm;
            background-image: url('rooq.webp');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-color: white;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            box-sizing: border-box;
            padding: 45mm 20mm 35mm 20mm;
            margin-bottom: 40px;
            overflow: hidden;
        }

        /* --------------------------------------
           NEW COVER PAGE STYLES 
           -------------------------------------- */
        .document-page.cover-page {
            padding: 0;
            /* Remove padding for edge-to-edge design */
            background-image: none;
        }

        /* --- BULLETPROOF PDF CURVE --- */
        .doc-bg-container {
            position: relative;
            width: 100%;
            height: 80%;
            overflow: hidden;
            /* This clips the giant circles to look like a bottom curve */
            background-color: white;
        }

        .doc-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 85%;
            z-index: 10;
            padding: 50px;
            color: white;
            box-sizing: border-box;
        }

        .doc-tab {
            position: absolute;
            top: 0;
            right: 40px;
            width: 60px;
            height: 100px;
            background-color: var(--rooq-gold);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 15px;
            font-weight: 700;
            font-size: 16px;
            color: white;
            box-shadow: -2px 5px 10px rgba(0, 0, 0, 0.15);
            z-index: 20;
        }

        .doc-logo img{
            width: 30%;
            margin-bottom: 30px;
            filter: brightness(0) invert(1);
        }

        

        .cover-main-title {
            font-size: 36px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            margin-top: 10px;
            max-width: 85%;
            line-height: 1.2;
            color: white;
        }

        .doc-prepared-for {
            position: absolute;
            bottom: 120px;
            left: 50px;
            width: calc(100% - 100px);
        }

        .doc-prepared-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        /* Background Gold Curve */
        .curve-gold {
            position: absolute;
            top: -50%;
            left: -25%;
            width: 140%;
            height: 140%;
            background-color: #D4AF37;
            /* Gold */
            border-radius: 50%;
            z-index: 1;
        }

        /* Foreground Burgundy Curve */
        .curve-burgundy {
            position: absolute;
            top: -50%;
            left: -30%;
            width: 140%;
            height: 135%;
            /* Slightly shorter to show the gold underneath */
            background-color: #800020;
            /* Burgundy */
            border-radius: 50%;
            z-index: 2;
        }

        /* --- BULLETPROOF PDF LINES --- */
        .doc-field-row {
            margin-bottom: 20px;
            font-size: 14px;
            color: white;
            position: relative;
            z-index: 10;
        }

        .field-label {
            display: inline-block;
            width: 85px;
            font-weight: normal;
        }

        /* Wrapper to hold text and the physical line */
        .field-wrapper {
            display: inline-block;
            position: relative;
            min-width: 280px;
        }

        .field-text {
            font-weight: 600;
            padding-bottom: 5px;
            display: block;
        }

        /* A physical 1px box instead of a CSS border */
        .field-line {
            width: 100%;
            height: 1px;
            background-color: white;
            position: absolute;
            bottom: 0;
            left: 0;
        }

        .doc-footer {
            position: absolute;
            bottom: 45px;
            left: 45px;
            z-index: 10;
        }

        .doc-footer-company {
            color: var(--rooq-dark);
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .doc-footer-address {
            color: var(--doc-gray-text);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* --------------------------------------
           CONTRACT PAGES STYLES 
           -------------------------------------- */
        .content {
            line-height: 1.6;
            font-size: 11pt;
            border-top: 3px solid var(--rooq-burgundy);
            margin-top: 15px;
            padding-top: 15px;
        }

        .doc-title {
            color: var(--rooq-burgundy);
            font-size: 14px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .doc-subtitle {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: var(--rooq-dark);
            margin-bottom: 20px;
        }

        h2 {
            color: var(--rooq-burgundy);
            font-size: 13pt;
            border-bottom: 1px dashed var(--rooq-gold);
            padding-bottom: 5px;
            margin-top: 5px;
            text-transform: uppercase;
        }

        p {
            margin: 0px;
        }

        ul,
        ol {
            padding-left: 20px;
            margin: 0px;
        }

        .layout-table {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .bank-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10pt;
            background: rgba(255, 255, 255, 0.8);
        }

        .bank-table th,
        .bank-table td {
            border: 1px solid var(--rooq-gold);
            padding: 8px 12px;
            text-align: left;
        }

        .bank-table th {
            background-color: var(--rooq-burgundy);
            color: white;
            width: 40%;
        }

        .signature-line {
            border-bottom: 1px solid var(--rooq-dark);
            margin-top: 20px;
            margin-bottom: 10px;
            width: 30%;
        }
    </style>
</head>

<body>

    <button class="download-btn" onclick="generatePDF()" data-html2canvas-ignore="true">⬇ Download PDF</button>

    <div id="contract-content">

        <div class="document-page cover-page">
            <div class="doc-bg-container">
                <div class="curve-gold"></div>
                <div class="curve-burgundy"></div>

                <div class="doc-tab"><?php echo $year; ?></div>

                <div class="doc-content">

                    <div class="doc-logo">
                        <img src="../assets/img/logo.png" alt="">
                    </div>

                    <h1 class="cover-main-title">SERVICE LICENSE AGREEMENT</h1>

                    <div class="doc-prepared-for">
                        <div class="doc-prepared-title">Prepared For</div>

                        <div class="doc-field-row">
                            <span class="field-label">Client Name:</span>
                            <div class="field-wrapper">
                                <span class="field-text"><?php echo $clientName; ?></span>
                                <div class="field-line"></div>
                            </div>
                        </div>

                        <div class="doc-field-row">
                            <span class="field-label">Date:</span>
                            <div class="field-wrapper">
                                <span class="field-text"><?php echo $date; ?></span>
                                <div class="field-line"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="doc-footer">
                <div class="doc-footer-company"><?php echo strtoupper(explode(" ", $serviceProvider)[0]); ?> COMPANY
                </div>
                <div class="doc-footer-address"><?php echo strtoupper($companyLocation); ?></div>
            </div>
        </div>
        <div class="document-page">
            <div class="content">
                <div class="doc-title">SERVICE LICENSE AGREEMENT</div>
                <div class="doc-subtitle">(MISA Service License Facilitation)</div>

                <p>This Service Agreement ("Agreement") is made between:</p>

                <div class="layout-table bg-gray">
                    <strong>Service Provider:</strong><br>
                    <p><?php echo $serviceProvider; ?></p>
                    <p>Email: info@flyburjco.com </p>

                    <p><strong>Client Name: </strong> <?php echo $clientName; ?></p>
                    <p> Iqama No: <?php echo $iqamaNo; ?></p>
                </div>

                <h2>1. OBJECTIVE OF THE AGREEMENT</h2>
                <p>The objective of this Agreement is to appoint Flyburj Travels & Tourism Company as a facilitator and
                    consultant to assist the Client in obtaining a MISA Service License in the Kingdom of Saudi Arabia,
                    in accordance with the regulations of the Ministry of Investment of Saudi Arabia (MISA).</p>

                <h2>2. PERMITTED ACTIVITIES UNDER SERVICE LICENSE</h2>
                <p>Service-based activities including consultancy, IT services, management support, marketing, training,
                    professional advisory services, and other non-trading activities as approved by MISA</p>

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
                <p>The Total professional service fee for this Agreement is <strong>SAR <?php echo $serviceFee; ?>
                        (Saudi Riyals Fifteen Thousand only)</strong>.</p>
                <p><em style="color:red;">Note: All kind of Service provide by Flyburj Travels & Tourism Co. & All kind
                        of Govt. Payments are to be borne by the Client. </em></p>

                <h2>6. PAYMENT TERMS</h2>
                <ul>
                    <li>The Client shall pay 25% of the total service fees upon signing this Agreement, 25% upon
                        issuance of the Investment License in Saudi Arabia, and the remaining 50% upon issuance of the
                        Commercial Register.</li>
                    <li>If the client fails to fulfill the payment obligations, the company reserves the right to retain
                        the official documents and papers until the full payment is settled and the final settlement is
                        completed.</li>
                    <li>Should there be any changes to the government license fee, the agreement amount will be revised
                        accordingly.</li>
                    <li>The contractual relationship with our company ends once the commercial register and investment
                        license have been obtained and the agreed-upon services have been completed.</li>
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
                <h2>7. CLIENT OBLIGATIONS</h2>
                <p>The Client agrees to:</p>
                <ul>
                    <li>Provide required documents promptly</li>
                    <li>Pay government fees on time</li>
                    <li>Cooperate fully during the application process</li>
                    <li>Comply with all Saudi laws, regulations, and MISA requirements</li>
                </ul>
                <p>Any delay caused by incomplete documents or late payments shall not be the responsibility of the
                    Service Provider.</p>

                <h2>8. TIMELINE & DELAYS</h2>
                <p>The estimated timeline to complete the MISA Service License and related registrations is
                    approximately forty <strong><?php echo $timelineDays; ?> working days</strong>, subject to timely
                    submission of documents and payments by the Client.</p>

                <p>The Service Provider shall not be held responsible for any delay caused by:</p>
                <ul>
                    <li>Government system or server issues</li>
                    <li>Portal downtime or technical errors</li>
                    <li>Scheduled or unscheduled system maintenance</li>
                </ul>
                <p>Any delays arising from external or governmental processes shall not be considered a breach of this
                    Agreement and will not affect the agreed service charges.</p>

                <h2>9. ACCEPTANCE & SIGNATURES</h2>
                <p>By signing below, both Parties agree to the terms and conditions of this Agreement.</p>

                <div class="layout-table">
                    <strong>For Flyburj Travels And Tourism Company</strong><br>
                    <p>Name: <strong>Saifullah</strong></p>

                    <div style="display:flex; margin-top: 10px;">Signature: <div class="signature-line"></div>
                    </div>
                    <strong>For the Client</strong>
                    <p>Name: <strong><?php echo $clientName; ?></strong></p>
                    <div style="display:flex; margin-top: 20px;">Signature: <div class="signature-line"></div>
                    </div>
                    <p><strong>Date:</strong> _____________________</p>
                </div>

            </div>
        </div>

    </div>

    <script>
        function generatePDF() {
            const element = document.getElementById('contract-content');
            const pages = document.querySelectorAll('.document-page');

            // 1. Prepare for PDF
            pages.forEach(p => {
                p.style.marginBottom = '0px';
                p.style.boxShadow = 'none';
                p.style.height = '296.9mm';
            });

            const clientName = "<?php echo str_replace(' ', '_', $clientName); ?>";
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
                    windowWidth: 1522
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
            });
        }
    </script>

</body>

</html>