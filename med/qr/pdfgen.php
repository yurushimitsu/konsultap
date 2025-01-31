<?php
require('vendor/setasign/fpdf/fpdf.php');
// Initialize variables
$pdfLink = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if text is provided
    if (!empty($_POST["text"])) {
        $text = $_POST["text"];

        // Create a new PDF instance
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('Arial', '', 12);

        // Add text to the PDF
        $pdf->Cell(0, 10, $text, 0, 1);

        // Generate a unique filename
        $filename = uniqid('output_', true) . '.pdf';
        
        // Output PDF to a temporary file
        $pdf->Output($filename, 'F');

        // Provide download link
        $pdfLink = '<a href="' . $filename . '">Download PDF</a>';
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
    <title>PDF Generator</title>
</head>
<body>
    <form method="post">
        <label for="text">Enter Text:</label><br>
        <textarea id="text" name="text" rows="4" cols="50"></textarea><br>
        <input type="submit" value="Generate PDF">
    </form>

    <?php echo $pdfLink; ?>
</body>
</html>