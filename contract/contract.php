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
$serviceProvider = "Basmat Rooq Company Limited";
$serviceFee      = number_format($client['contract_value'] ?? 0, 2); 
$timelineDays    = "40"; 
$companyLocation = "BURAYDAH, AL QASSIM-SAUDI ARABIA";
$year            = date('Y'); 

// --- CALCULATE DYNAMIC HIJRI YEAR (Kuwaiti Algorithm) ---
$y = (int)date('Y'); $m = (int)date('n'); $d = (int)date('j');
$jd = (int)((1461*($y+4800+(int)(($m-14)/12)))/4) + (int)((367*($m-2-12*((int)(($m-14)/12))))/12) - (int)((3*((int)(($y+4900+(int)(($m-14)/12))/100)))/4) + $d - 32075;
$l = $jd - 1948440 + 10632;
$n = (int)(($l - 1) / 10631);
$l = $l - 10631 * $n + 354;
$j = ((int)((10985 - $l) / 5316)) * ((int)(50 * $l / 17719)) + ((int)($l / 5670)) * ((int)(43 * $l / 15238));
$hijriYear = 30 * $n + $j - 30;
// --------------------------------------------------------

// 3. GENERATE DYNAMIC SCOPE OF SERVICES (FROM WORKFLOW TABLE ONLY)
$scopeList = [];
if (!empty($client['hire_foreign_company']) && $client['hire_foreign_company'] !== 'Not Required') { $scopeList[] = "Arrangement of a Foreign Company (as required by MISA)"; }
if (!empty($client['misa_application']) && $client['misa_application'] !== 'Not Required') { $scopeList[] = "Application and approval of MISA Service License"; }
if (!empty($client['sbc_application']) && $client['sbc_application'] !== 'Not Required') { $scopeList[] = "SBC Application & Registration"; }
if (!empty($client['article_association']) && $client['article_association'] !== 'Not Required') { $scopeList[] = "Preparation of Articles of Association"; }
if (!empty($client['qiwa']) && $client['qiwa'] !== 'Not Required') { $scopeList[] = "Qiwa Registration"; }
if (!empty($client['muqeem']) && $client['muqeem'] !== 'Not Required') { $scopeList[] = "Muqeem Registration"; }
if (!empty($client['gosi']) && $client['gosi'] !== 'Not Required') { $scopeList[] = "GOSI Registration"; }
if (!empty($client['chamber_commerce']) && $client['chamber_commerce'] !== 'Not Required') { $scopeList[] = "Chamber of Commerce Registration"; }

// 4. FETCH GLOBAL DEFAULTS FIRST
$stmtDef = $db->query("SELECT * FROM default_contract_settings WHERE id = 1");
$globalDefaults = $stmtDef->fetch(PDO::FETCH_ASSOC);

// 5. FETCH CUSTOM CONTRACT TEXT (IF INDIVIDUALLY EDITED)
$stmtText = $db->prepare("SELECT * FROM client_contracts WHERE client_id = ?");
$stmtText->execute([$client_id]);
$customText = $stmtText->fetch(PDO::FETCH_ASSOC);

// --- MERGE ADDITIONAL SCOPE ---
if (!empty($customText['additional_scope'])) {
    $extraLines = explode("\n", $customText['additional_scope']);
    foreach ($extraLines as $line) {
        $cleanLine = trim($line);
        if ($cleanLine !== '') {
            $scopeList[] = $cleanLine; 
        }
    }
}

// 6. APPLY DATA (Use Individual Edit -> Or Global Default)
$txt_objective = $customText['objective'] ?? $globalDefaults['objective'];
$txt_permitted = $customText['permitted_activities'] ?? $globalDefaults['permitted_activities'];
$txt_docs = $customText['documentation'] ?? $globalDefaults['documentation'];
$txt_payment = $customText['payment_terms'] ?? $globalDefaults['payment_terms'];
$txt_obligations = $customText['obligations'] ?? $globalDefaults['obligations'];
$txt_timeline_days = $customText['timeline_days'] ?? $globalDefaults['timeline_days'];
$txt_timeline_text = $customText['timeline_text'] ?? $globalDefaults['timeline_text'];

$txt_bank_name = $customText['bank_name'] ?? $globalDefaults['bank_name'];
$txt_account_number = $customText['account_number'] ?? $globalDefaults['account_number'];
$txt_iban_number = $customText['iban_number'] ?? $globalDefaults['iban_number'];
$txt_account_name = $customText['account_name'] ?? $globalDefaults['account_name'];
// Create a safe string for the PDF filename
$pdfClientName = htmlspecialchars(str_replace(' ', '_', $clientName));

// Define page title and load header
$pageTitle = "Service License Agreement - " . htmlspecialchars($clientName);
require_once 'header.php';
?>

    <a href="edit_contract.php?id=<?php echo $client_id; ?>" class="edit-btn" data-html2canvas-ignore="true">✏️ Edit Contract</a>
    <button class="download-btn" onclick="generatePDF()" data-html2canvas-ignore="true">⬇ Download PDF</button>

    <div id="contract-content" data-client-name="<?php echo $pdfClientName; ?>">

        <div class="document-page cover-page">
            <svg width="794" height="1123" viewBox="0 0 794 1123" style="position:absolute; top:0; left:0; z-index:1;"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M 0,0 L 550,0 C 350,150 150,220 0,180 Z" fill="none" style="stroke: var(--theme-accent);"
                    stroke-width="4" transform="translate(8, 8)" opacity="0.8" />
                <path d="M 0,0 L 550,0 C 350,150 150,220 0,180 Z" style="fill: var(--theme-primary);" />

                <path d="M 794,250 C 650,400 650,600 794,750 Z" fill="none" style="stroke: var(--theme-accent);"
                    stroke-width="4" transform="translate(-8, 0)" opacity="0.8" />
                <path d="M 794,250 C 650,400 650,600 794,750 Z" style="fill: var(--theme-primary);" />

                <path d="M 0,450 C 100,1050 550,800 794,880" fill="none" style="stroke: var(--theme-accent);"
                    stroke-width="4" transform="translate(0, -8)" opacity="0.8"></path>
                <path d="M 0,450 C 100,1050 550,800 794,880 L 794,1025 C 500,960 200,1123 0,1123 Z"
                    style="fill: var(--theme-primary);"></path>
            </svg>
            <img src="../assets/img/logo_transparent.png" class="watermark" alt="">

            <div class="cover-content-layer">

                <img src="../assets/img/logo.png" class="brand-logo" alt="Basmat Rooq">

                <div class="title-section">
                    <div class="doc-arabic">ملف ترخيص وزارة الاستثمار</div>
                    <div class="doc-subtitle">MISA INVESTOR</div>
                    <h1 class="cover-main-title">LICENSE</h1>

                    <div class="doc-data-box">
                        <div class="data-row">
                            <svg class="data-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z" />
                                </svg>
                            <div class="data-text-block">
                                <span class="data-label">Company Name</span>
                                <span class="data-value"><?php echo htmlspecialchars($client['company_name'] ?? 'Jahangir Contracting Ltd.'); ?></span>
                            </div>
                        </div>

                        <div class="data-row">
                            <svg class="data-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24">
                                <path
                                    d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z" />
                                </svg>
                            <div class="data-text-block">
                                <span class="data-label">Trade Name</span>
                                <span
                                    class="data-value"><?php echo htmlspecialchars($client['trade_name'] ?? 'ALSAMA SKR'); ?></span>
                            </div>
                        </div>

                        <div class="data-row">
                            <svg class="data-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                </svg>
                            <div class="data-text-block">
                                <span class="data-label">Client Representative</span>
                                <span class="data-value"><?php echo htmlspecialchars($clientName); ?></span>
                            </div>
                        </div>

                        <div class="data-row">
                            <svg class="data-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24">
                                <path
                                    d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" />
                                </svg>
                            <div class="data-text-block">
                                <span class="data-label">Date Issued</span>
                                <span class="data-value"><?php echo $date; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="doc-year">
                    <span class="year-main"><?php echo $year; ?></span>
                    <span class="year-sub"><?php echo $hijriYear; ?> HIJRI</span>
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
                    <p><strong>Client Name: </strong> <?php echo htmlspecialchars($clientName); ?></p>
                    <p> Iqama No: <?php echo htmlspecialchars($iqamaNo); ?></p>
                </div>

                <h2>1. OBJECTIVE OF THE AGREEMENT</h2>
                <p><?php echo $txt_objective; ?></p>
                <h2>2. PERMITTED ACTIVITIES UNDER SERVICE LICENSE</h2>
                <p><?php echo $txt_permitted; ?></p>
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
                <div><?php echo $txt_docs; ?></div>
                <h2>5. SERVICE CHARGES</h2>
                <p>The Total professional service fee for this Agreement is <strong>SAR
                        <?php echo htmlspecialchars($serviceFee); ?> (Saudi Riyals Fifteen Thousand only)</strong>.</p>
                <p><em style="color:red;">Note: All kind of Service provide by Flyburj Travels & Tourism Co. & All kind
                        of Govt. Payments are to be borne by the Client. </em></p>

                <h2>6. PAYMENT TERMS</h2>
                <div><?php echo $txt_payment; ?></div>
                <table class="bank-table">
                    <tr>
                        <th>NAME OF BANK</th>
                        <td><?php echo htmlspecialchars($txt_bank_name); ?></td>
                    </tr>
                    <tr>
                        <th>ACCOUNT NUMBER</th>
                        <td><?php echo htmlspecialchars($txt_account_number); ?></td>
                    </tr>
                    <tr>
                        <th>ACCOUNT IBAN NUMBER</th>
                        <td><?php echo htmlspecialchars($txt_iban_number); ?></td>
                    </tr>
                    <tr>
                        <th>AC NAME</th>
                        <td><?php echo htmlspecialchars($txt_account_name); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="document-page">
            <div class="content">
                <h2>7. CLIENT OBLIGATIONS</h2>
                <div><?php echo $txt_obligations;?></div>
                
                <h2>8. TIMELINE & DELAYS</h2>
                <p>The estimated timeline to complete the MISA Service License and related registrations is
                    approximately <strong><?php echo htmlspecialchars($txt_timeline_days); ?> working days</strong>,
                    subject to timely submission of documents and payments by the Client.</p>
                    
                <div><?php echo $txt_timeline_text; ?></div>
                <h2>9. ACCEPTANCE & SIGNATURES</h2>
                <p>By signing below, both Parties agree to the terms and conditions of this Agreement.</p>

                <div class="layout-table">
                    <strong>For <?php echo htmlspecialchars($serviceProvider); ?></strong>
                    <p>Name: <strong>Saifullah</strong></p>
                    <div style="display:flex; align-items: baseline;">Signature: <div class="signature-line"></div>
                    </div>
                    <strong>For the Client</strong>
                    <p>Name: <strong><?php echo htmlspecialchars($clientName); ?></strong></p>
                    <div style="display:flex; align-items: baseline;">Signature: <div class="signature-line"></div>
                    </div>
                    <p><strong>Date:</strong> _____________________</p>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'footer.php'; ?>