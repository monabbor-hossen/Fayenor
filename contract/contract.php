<?php
session_start();
require_once __DIR__ . '/../app/Config/Config.php';
require_once __DIR__ . '/../app/Config/Database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>Error: No Client Selected.</h2></div>");
}

$client_id = intval($_GET['id']);

// 1. Fetch Client AND Workflow Data
try {
    $db = (new Database())->getConnection();
    // We join workflow_tracking so we can read the ON/OFF switches
    $stmt = $db->prepare("
        SELECT c.*, w.* FROM clients c 
        LEFT JOIN workflow_tracking w ON c.client_id = w.client_id 
        WHERE c.client_id = ? LIMIT 1
    ");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>Error: Client Not Found.</h2></div>");
    }
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// 2. Map Database fields to the Contract Variables
$clientName      = $client['client_name'] ?? $client['company_name'];
$date            = date('F j, Y'); 
$iqamaNo         = "To Be Provided"; // Change this if you have an Iqama column
$serviceProvider = "Flyburj Travels and Tourism Company";
$serviceFee      = number_format($client['contract_value'] ?? 0, 2); 
$timelineDays    = "40"; 
$companyLocation = "BURAYDAH, AL QASSIM-SAUDI ARABIA";
$year            = date('Y'); 

// 3. GENERATE DYNAMIC SCOPE OF SERVICES (FROM WORKFLOW TABLE ONLY)
$scopeList = [];

if (!empty($client['hire_foreign_company']) && $client['hire_foreign_company'] !== 'Not Required') {
    $scopeList[] = "Arrangement of a Foreign Company (as required by MISA)";
}
if (!empty($client['misa_application']) && $client['misa_application'] !== 'Not Required') {
    $scopeList[] = "Application and approval of MISA Service License";
}
if (!empty($client['sbc_application']) && $client['sbc_application'] !== 'Not Required') {
    $scopeList[] = "SBC Application & Registration";
}
if (!empty($client['article_association']) && $client['article_association'] !== 'Not Required') {
    $scopeList[] = "Preparation of Articles of Association";
}
if (!empty($client['qiwa']) && $client['qiwa'] !== 'Not Required') {
    $scopeList[] = "Qiwa Registration";
}
if (!empty($client['muqeem']) && $client['muqeem'] !== 'Not Required') {
    $scopeList[] = "Muqeem Registration";
}
if (!empty($client['gosi']) && $client['gosi'] !== 'Not Required') {
    $scopeList[] = "GOSI Registration";
}
if (!empty($client['chamber_commerce']) && $client['chamber_commerce'] !== 'Not Required') {
    $scopeList[] = "Chamber of Commerce Registration";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service License Agreement - <?php echo htmlspecialchars($clientName); ?></title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@600;800&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap');

        /* Core Theme Variables */
        :root {
            /* Shared Colors */
            --theme-primary: #800020; /* Corporate Burgundy */
            --theme-accent: #D4AF37;  /* Corporate Gold */
            --text-dark: #111111;
            --text-muted: #555555;
            --bg-offwhite: #fdfdfd;
            --doc-gray-text: #777777;
        }

        body {
            background-color: #e9ecef;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 40px 0;
        }

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
            background-color: var(--theme-primary);
            color: white;
            border: 2px solid var(--theme-accent);
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        /* --------------------------------------
           COMMON PAGE STYLES 
           -------------------------------------- */
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
           NEW EXECUTIVE COVER PAGE STYLES 
           -------------------------------------- */
        .document-page.cover-page {
            padding: 0;
            background-image: none;
            background-color: var(--bg-offwhite);
            font-family: 'Montserrat', sans-serif;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 60%;
            transform: translate(-50%, -50%);
            width: 600px;
            opacity: 0.03;
            pointer-events: none;
            z-index: 2;
            filter: brightness(0) drop-shadow(2px 0 0 white) drop-shadow(-2px 0 0 white) drop-shadow(0 2px 0 white) drop-shadow(0 -2px 0 white) invert(1);
        }

        .wave-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 5;
            pointer-events: none;
        }

        .content-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
        }

        .brand-container {
            position: absolute;
            top: 80px;
            left: 80px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .brand-icon-wrapper img {
            width: 30%; /* Sized for standard logos */
            filter: brightness(0) invert(1); /* Removes if logo is white, adjust if needed */
        }

        .title-wrapper {
            position: absolute;
            top: 340px;
            left: 80px;
        }

        .doc-arabic {
            font-family: 'Cairo', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--theme-accent);
            margin-bottom: -5px;
            margin-left: 5px;
        }

        .cover-sub-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--theme-primary);
            text-transform: uppercase;
            letter-spacing: 5px;
            margin-bottom: 5px;
            margin-left: 5px;
        }

        .cover-main-title {
            font-size: 80px;
            font-weight: 900;
            color: var(--text-dark);
            text-transform: uppercase;
            line-height: 0.95;
            letter-spacing: -3px;
            margin: 0;
            margin-bottom: 45px;
        }

        .doc-data-box {
            border-left: 4px solid var(--theme-accent);
            padding-left: 25px;
            margin-left: 8px;
            max-width: 450px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .data-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .data-icon {
            width: 16px;
            height: 16px;
            fill: var(--theme-primary);
            margin-top: 3px;
            opacity: 0.8;
        }

        .doc-data-text {
            font-size: 14px;
            line-height: 1.5;
            color: var(--text-muted);
            font-weight: 500;
            margin: 0;
        }

        .doc-data-text strong {
            font-weight: 800;
            color: var(--text-dark);
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .doc-year {
            position: absolute;
            bottom: 180px;
            right: 120px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            z-index: 10;
        }

        .year-main {
            font-size: 55px;
            font-weight: 900;
            color: white;
            letter-spacing: -1px;
            line-height: 1;
        }

        .year-sub {
            font-size: 14px;
            font-weight: 600;
            color: var(--theme-accent);
            letter-spacing: 2px;
            margin-top: 2px;
        }

        /* --------------------------------------
           CONTRACT INNER PAGES STYLES 
           -------------------------------------- */
        .content {
            line-height: 1.6;
            font-size: 11pt;
            border-top: 3px solid var(--theme-primary);
            margin-top: 15px;
            padding-top: 15px;
            color: var(--text-dark);
        }

        .inner-doc-title {
            color: var(--theme-primary);
            font-size: 14px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .inner-doc-subtitle {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        h2 {
            color: var(--theme-primary);
            font-size: 13pt;
            border-bottom: 1px dashed var(--theme-accent);
            padding-bottom: 5px;
            margin-top: 5px;
            text-transform: uppercase;
        }

        p { margin: 0px; }
        ul, ol { padding-left: 20px; margin: 0px; }

        .layout-table {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .bg-gray {
            background-color: #f5f5f5;
            padding: 15px;
            border-left: 3px solid var(--theme-accent);
        }

        .bank-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10pt;
            background: rgba(255, 255, 255, 0.8);
        }

        .bank-table th, .bank-table td {
            border: 1px solid var(--theme-accent);
            padding: 8px 12px;
            text-align: left;
        }

        .bank-table th {
            background-color: var(--theme-primary);
            color: white;
            width: 40%;
        }

        .signature-line {
            border-bottom: 1px solid var(--text-dark);
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
            <img src="../assets/img/logo_transparent.png" class="watermark" alt="">

            <svg class="wave-bg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M 0,0 L 80,0 C 50,22 20,24 0,14 Z" fill="none" stroke="var(--theme-accent)" stroke-width="0.5" transform="translate(1, 1)" opacity="0.6" />
                <path d="M 0,0 L 80,0 C 50,22 20,24 0,14 Z" fill="var(--theme-primary)" />
                <path d="M 100,22 C 85,35 85,55 100,68 Z" fill="var(--theme-accent)" opacity="0.8" transform="translate(-1.5, 0)" />
                <path d="M 100,22 C 85,35 85,55 100,68 Z" fill="var(--theme-primary)" />
                <path d="M 0,45 C 30,90 70,72 100,78 L 100,84 C 70,78 30,98 0,100 Z" fill="none" stroke="var(--theme-accent)" stroke-width="0.5" transform="translate(0, -1.5)" opacity="0.8" />
                <path d="M 0,45 C 30,90 70,72 100,78 L 100,84 C 70,78 30,98 0,100 Z" fill="var(--theme-primary)" />
            </svg>

            <div class="content-layer">
                <div class="brand-container">
                    <div class="brand-icon-wrapper">
                        <img src="../assets/img/logo.png" alt="Basmat Rooq">
                    </div>
                </div>

                <div class="title-wrapper">
                    <div class="doc-arabic">ملف ترخيص وزارة الاستثمار</div>
                    <div class="cover-sub-title">MISA INVESTOR</div>
                    <h1 class="cover-main-title">LICENSE</h1>

                    <div class="doc-data-box">
                        <div class="data-row">
                            <svg class="data-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z" />
                            </svg>
                            <p class="doc-data-text"><strong>Company Name</strong> <?php echo htmlspecialchars($client['company_name'] ?? 'Jahangir Contracting Ltd.'); ?></p>
                        </div>

                        <div class="data-row">
                            <svg class="data-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z" />
                            </svg>
                            <p class="doc-data-text"><strong>Trade Name</strong> <?php echo htmlspecialchars($client['trade_name'] ?? 'ALSAMA SKR'); ?></p>
                        </div>

                        <div class="data-row">
                            <svg class="data-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                            </svg>
                            <p class="doc-data-text"><strong>Client Representative</strong> <?php echo htmlspecialchars($clientName); ?></p>
                        </div>

                        <div class="data-row">
                            <svg class="data-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" />
                            </svg>
                            <p class="doc-data-text"><strong>Date Issued</strong> <?php echo $date; ?></p>
                        </div>
                    </div>
                </div>

                <div class="doc-year">
                    <span class="year-main"><?php echo $year; ?></span>
                    <span class="year-sub">1447 HIJRI</span>
                </div>
            </div>
        </div>

        <div class="document-page">
            <div class="content">
                <div class="inner-doc-title">SERVICE LICENSE AGREEMENT</div>
                <div class="inner-doc-subtitle">(MISA Service License Facilitation)</div>

                <p>This Service Agreement ("Agreement") is made between:</p>

                <div class="layout-table bg-gray">
                    <strong>Service Provider:</strong><br>
                    <p><?php echo htmlspecialchars($serviceProvider); ?></p>
                    <p>Email: info@flyburjco.com </p>
                    <br>
                    <p><strong>Client Name: </strong> <?php echo htmlspecialchars($clientName); ?></p>
                    <p> Iqama No: <?php echo htmlspecialchars($iqamaNo); ?></p>
                </div>

                <h2>1. OBJECTIVE OF THE AGREEMENT</h2>
                <p>The objective of this Agreement is to appoint Flyburj Travels & Tourism Company as a facilitator and consultant to assist the Client in obtaining a MISA Service License in the Kingdom of Saudi Arabia, in accordance with the regulations of the Ministry of Investment of Saudi Arabia (MISA).</p>

                <h2>2. PERMITTED ACTIVITIES UNDER SERVICE LICENSE</h2>
                <p>Service-based activities including consultancy, IT services, management support, marketing, training, professional advisory services, and other non-trading activities as approved by MISA</p>

                <h2>3. SCOPE OF SERVICES</h2>
                <p>The Service Provider shall be responsible for completing the following services for the Client:</p>
                <ol>
                    <?php foreach ($scopeList as $item): ?>
                        <li><?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
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
                <p>The Total professional service fee for this Agreement is <strong>SAR <?php echo htmlspecialchars($serviceFee); ?> (Saudi Riyals Fifteen Thousand only)</strong>.</p>
                <p><em style="color:red;">Note: All kind of Service provide by Flyburj Travels & Tourism Co. & All kind of Govt. Payments are to be borne by the Client. </em></p>

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
                <p>Any delay caused by incomplete documents or late payments shall not be the responsibility of the Service Provider.</p>

                <h2>8. TIMELINE & DELAYS</h2>
                <p>The estimated timeline to complete the MISA Service License and related registrations is approximately forty <strong><?php echo htmlspecialchars($timelineDays); ?> working days</strong>, subject to timely submission of documents and payments by the Client.</p>

                <p>The Service Provider shall not be held responsible for any delay caused by:</p>
                <ul>
                    <li>Government system or server issues</li>
                    <li>Portal downtime or technical errors</li>
                    <li>Scheduled or unscheduled system maintenance</li>
                </ul>
                <p>Any delays arising from external or governmental processes shall not be considered a breach of this Agreement and will not affect the agreed service charges.</p>

                <h2>9. ACCEPTANCE & SIGNATURES</h2>
                <p>By signing below, both Parties agree to the terms and conditions of this Agreement.</p>

                <div class="layout-table">
                    <strong>For Flyburj Travels And Tourism Company</strong><br>
                    <p>Name: <strong>Saifullah</strong></p>

                    <div style="display:flex; margin-top: 10px;">Signature: <div class="signature-line"></div></div>
                    <br>
                    <strong>For the Client</strong>
                    <p>Name: <strong><?php echo htmlspecialchars($clientName); ?></strong></p>
                    <div style="display:flex; margin-top: 20px;">Signature: <div class="signature-line"></div></div>
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
                image: { type: 'jpeg', quality: 1 },
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