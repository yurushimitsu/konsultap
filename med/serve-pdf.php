<!-- sweetalert -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

<?php
session_start();

// Check for expiration
if (isset($_GET['expires']) && $_GET['expires'] < time()) {
    echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "warning",
                    title: "QR code has expired",
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
            </script>';
    exit();
}

// Get user ID and filename
$userIDNumber = $_GET['id']; // Make sure to sanitize this input
$pdfFilename = $_GET['file'];

// Filepath to pdf
$filePath = 'pdfs/'.$userIDNumber.'/'.$pdfFilename;

// Check if the file exists
if (file_exists($filePath)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    readfile($filePath);
    exit;
} else {
    echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "warning",
                    title: "File not found",
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
            </script>';
    exit();
}

?>
