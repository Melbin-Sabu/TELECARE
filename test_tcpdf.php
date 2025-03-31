<?php
// Ensure no output before this line
require_once __DIR__ . '/vendor/autoload.php'; // Load TCPDF

$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('TeleCare System');
$pdf->SetTitle('Health Report');
$pdf->SetSubject('Health Data PDF');
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 16);
$pdf->Cell(0, 10, 'TCPDF is Working!', 1, 1, 'C');

// Fix: Clear any accidental output before sending PDF
ob_clean();
$pdf->Output('test.pdf', 'I'); // Open PDF in browser
?>
