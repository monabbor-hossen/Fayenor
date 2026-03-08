<?php
// 1. Point to the manual DomPDF autoloader
require 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 2. Setup PDF Options (Important for loading your local images)
$options = new Options();
$options->set('isRemoteEnabled', true); 
$options->set('isHtml5ParserEnabled', true);
$options->set('chroot', __DIR__); // Security setting allowing it to read local files in this folder

// 3. Initialize DomPDF
$dompdf = new Dompdf($options);

// 4. Capture your contract's HTML
ob_start();
include 'contract.php';
$html = ob_get_clean();

// 5. Load HTML and set A4 Paper
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

// 6. Render and Download
$dompdf->render();

// Output the PDF. "Attachment" => true forces a download.
$dompdf->stream("Bablu_Ahmed_Service_Agreement.pdf", array("Attachment" => true));
?>