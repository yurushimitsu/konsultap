<?php

require "vendor/autoload.php";

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if text is provided
    if (!empty($_POST["text"])) {
        $text = $_POST["text"];

        // Generate QR code
        $qrCode = new QrCode($text);

        // Generate QR code image
        $writer = new PngWriter;
        $result = $writer->write($qrCode);

        // Output QR code image
        header("Content-Type: " . $result->getMimeType());
        echo $result->getString();
    } else {
        echo "Please provide text.";
    }
} else {
    echo "Method not allowed.";
}

?>




<!DOCTYPE html>
<html>
<head>
    <title>QR Code Generator with PDF</title>
</head>
<body>
    <form method="post">
        <label for="text">Enter Text:</label><br>
        <input type="text" id="text" name="text"><br>
        <input type="submit" value="Generate QR Code and PDF">
    </form>
</body>
</html>
