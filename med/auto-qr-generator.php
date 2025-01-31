<?php 
session_start();
require '/home/u801109301/domains/konsultap.com/public_html/config.php';

// QR Generator
require 'qr/vendor/autoload.php';
require 'qr/vendor/setasign/fpdf/fpdf.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Dbs for medical records
$dbTables = ['medical_history_records', 'prescription_records', 'procedure_history_records'];
date_default_timezone_set('Asia/Manila'); // Default timezone

foreach ($dbTables as $db) {
    $queryRecords = "SELECT * FROM $db";
    $stmtRecords = mysqli_prepare($conn, $queryRecords);
    mysqli_stmt_execute($stmtRecords);
    $resultRecords = mysqli_stmt_get_result($stmtRecords);

    if ($resultRecords && mysqli_num_rows($resultRecords) > 0) {
        while ($row = mysqli_fetch_assoc($resultRecords)) {
            $userIDNumber = $row['IdNumber'];
            $pdfFilename = $row['description'].'.pdf';
            $qrFilename = $row['description'].'.png';

            $expirationTime = time() + 300; // Expires 5 minutes from now

            // Link inside QR code
            $qrCode = new QrCode("https://konsultap.com/med/serve-pdf.php?id=".$userIDNumber."&file=".$pdfFilename."&expires=".$expirationTime);
            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            // QR Directory
            $qrDirectory = '/home/u801109301/domains/konsultap.com/public_html/med/pdfs/'.$userIDNumber.'/qr/';
            if (!file_exists($qrDirectory)) {
                echo 'file directory not found';
            }

            // QR file directory
            $qrCodeFile = $qrDirectory.$qrFilename;
            $result->saveToFile($qrCodeFile);

            $url = "https://konsultap.com/med/serve-pdf.php?id=".$userIDNumber."&file=".$pdfFilename."&expires=".$expirationTime;
            echo $url.'<br><br>';
        }
    }
}

?>