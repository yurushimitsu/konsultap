<?php
session_start();
require '../config.php';

// Check if the user is not logged in or does not have the role 'medprac', redirect to login page or show invalid role message
if (!isset($_SESSION['IdNumber']) || empty($_SESSION['IdNumber']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'medprac') {
    echo "Invalid role or not logged in";
    exit;
}

// Check if the user has completed OTP verification
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: ../verification.php");
    exit;
}

// User is logged in, continue fetching user details
$idNumber = $_SESSION['IdNumber'];

// Fetch user details from the database based on the IdNumber
$query = "SELECT fullName, Email, profile_picture, Password FROM medprac WHERE IdNumber = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $idNumber);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $fullName = $row['fullName'];
    $email = $row['Email'];
    $profilePicture = $row['profile_picture'];
    $password = $row['Password'];

    // Check if profile picture exists, if not, use default
    if (empty($profilePicture)) {
        $profilePicture = "../images/default_profile_picture.jpg";
    }

    // Check if user changed their password from default password
    if (password_verify($idNumber, $password)) {
        header("Location: staff-change-password.php");
        exit;
    } 
} else {
    // Handle the case if user details are not found
    $fullName = "User Not Found";
    $email = "N/A";
    $profilePicture = "../images/default_profile_picture.jpg";
}

// AJAX handling, ID Number Searching
if (isset($_POST['action']) && $_POST['action'] === 'fetch_user_data') {
    $idNumber = $_POST['idNumber'];
    $response = [];

    // Initialize an array to store queries for each user type
    $queries = [];
    $queries['students'] = "SELECT fullName, dateOfBirth, Gender FROM students WHERE IdNumber = ?";
    $queries['faculty'] = "SELECT fullName, dateOfBirth, Gender FROM faculty WHERE IdNumber = ?";

    // Execute queries for each table
    foreach ($queries as $type => $query) {
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $idNumber);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $response['fullName'] = $row['fullName'];
            $response['dateOfBirth'] =  date('Y-m-d', strtotime(htmlspecialchars($row['dateOfBirth']))); // Format the DateTime object to YYYY-MM-DD, to display in form
            $response['Gender'] = $row['Gender'];
            break; // Exit loop once data is found
        }
    }

    echo json_encode($response);
    exit;
}

// QR and PDF Generator
require 'qr/vendor/autoload.php';
require 'qr/vendor/setasign/fpdf/fpdf.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if ID Number and name is provided
    // if (!empty($_POST["idNumber"]) && !empty($_POST["name"])) {
    //     $userIDNumber = $_POST['idNumber'];
    //     $name = $_POST['name'];
    //     $dateBirth =date('F j, Y', strtotime($_POST['dateBirth'])) ;
    //     $docName = $_POST['docName'];
    //     $illness = $_POST['illness'];
    //     $dateIssued = date('F j, Y');
    //     $description = $_POST['description'];
    //     // Fix Spelling of type
    //     if ($_POST['type'] == 'medCert') {
    //         $type = 'Medical Certificate';
    //     } elseif ($_POST['type'] == 'prodHistory') {
    //         $type = 'Procedure History';
    //     } else {
    //         $type = $_POST['type'];
    //     }

        
       
    //     // Create a new PDF instance
    //     $pdf = new FPDF();
    //     $pdf->AddPage();

    //     $pdf->Cell(0, 10, '', 0, 1); // New line

    //     // Set header font
    //     $pdf->SetFont('Arial', 'B', 14);
    //     $pdf->Cell(0, 10, "$type", 0, 1, 'C');

    //     // Set body font
    //     $pdf->SetFont('Arial', '', 12);
    //     // Add text to the PDF from form data
    //     $pdf->Cell(0, 10, "$dateIssued", 0, 1);
    //     $pdf->Cell(0, 10, '', 0, 1); // New line
    //     $pdf->Cell(0, 10, "ID Number: $userIDNumber", 0, 1);
    //     $pdf->Cell(0, 10, "Name: $name", 0, 1);
    //     $pdf->Cell(0, 10, "Date of Birth: $dateBirth", 0, 1);
    //     $pdf->Cell(0, 10, "Doctor's Name: $docName", 0, 1);
    //     $pdf->Cell(0, 10, "Illness: $illness", 0, 1);
    //     $pdf->Cell(0, 10, "Description:", 0, 1);
    //     $pdf->Cell(0, 10, "$description", 0, 1);

    //     // Generate a unique filename
    //     $filename = uniqid($type.'_', true); // Filename depends on file type

    //     // Create the new directory path using the ID number
    //     $idDirectory = 'pdfs/'.$userIDNumber.'/';

    //     // Check if the directory exists, if not, create it
    //     if (!file_exists($idDirectory)) {
    //         mkdir($idDirectory, 0777, true); // Create the folder with appropriate permissions
    //     }

    //     // Generate QR code
    //     // $qrCode = new QrCode("https://localhost/capstone/konsultap/med/pdfs/".$userIDNumber.'/'.$filename); // Chan's file directory
    //     $qrCode = new QrCode("https://konsultap.com/med/pdfs/".$userIDNumber.'/'.$filename.'.pdf'); // Change link once deployed // Jed's file directory
    //     $writer = new PngWriter();
    //     $result = $writer->write($qrCode);

    //     // Create the new QR folder path using the ID number
    //     $qrDirectory = 'pdfs/'.$userIDNumber.'/qr/';

    //     // Check if the directory exists, if not, create it
    //     if (!file_exists($qrDirectory)) {
    //         mkdir($qrDirectory, 0777, true); // Create the folder with appropriate permissions
    //     }

    //     // QR file directory
    //     $qrCodeFile = $qrDirectory.$filename.'.png';
    //     $result->saveToFile($qrCodeFile);

    //     // Insert the QR code image into the PDF
    //     $pdf->Image($qrCodeFile, 170, 10, 30, 30); // Adjust X, Y position and size as needed
    //     $pdf->Image('../images/logomain.png', 10, 10, 35, 6); // KonsulTap Logo

    //     $filepath = $idDirectory.$filename.'.pdf'; // Change filepath accordingly once deployed

    //     // Output PDF to a file
    //     $pdf->Output($filepath, 'F');

    //     // Document title
    //     $title = $type." for ".$name."'s ".$illness;
    //     if ($_POST['type'] == 'medCert') {
    //         $query = "INSERT INTO `medical_history_records`(`title`, `description`, `IdNumber`, `date_added`, `medpracNumber`) VALUES (?, ?, ?, NOW(), ?)";
    //         $stmt = mysqli_prepare($conn, $query);
    //         mysqli_stmt_bind_param($stmt, "ssss", $title, $filename, $userIDNumber, $idNumber);
    //         mysqli_stmt_execute($stmt);
            
    //         // Display success message, display qr, and redirect
    //         echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    //         <script> 
    //         $(this).ready(function () {
    //             Swal.fire({
    //                 icon: "success",
    //                 title: "Medical Certificate QR and PDF File Generated",
    //                 imageUrl: "'.$qrCodeFile.'", 
    //                 imageWidth: 200,
    //                 imageHeight: 200,
    //                 showCloseButton: true,
    //                 showConfirmButton: false
    //             })
    //         })  
    //         </script>';

    //     } elseif ($_POST['type'] == 'prodHistory') {
    //         $query = "INSERT INTO `procedure_history_records`(`title`, `description`, `IdNumber`, `date_added`) VALUES (?, ?, ?, NOW())";
    //         $stmt = mysqli_prepare($conn, $query);
    //         mysqli_stmt_bind_param($stmt, "sss", $title, $filename, $userIDNumber);
    //         mysqli_stmt_execute($stmt);

    //         // Display success message, display qr, and redirect
    //         echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    //         <script> 
    //         $(this).ready(function () {
    //             Swal.fire({
    //                 icon: "success",
    //                 title: "Prodecure History QR and PDF File Generated",
    //                 imageUrl: "'.$qrCodeFile.'", 
    //                 imageWidth: 200,
    //                 imageHeight: 200,
    //                 showCloseButton: true,
    //                 showConfirmButton: false
    //             })
    //         })  
    //         </script>';
    //     } elseif ($_POST['type'] == 'Prescription') {
    //         $query = "INSERT INTO `prescription_records`(`title`, `description`, `IdNumber`, `date_added`) VALUES (?, ?, ?, NOW())";
    //         $stmt = mysqli_prepare($conn, $query);
    //         mysqli_stmt_bind_param($stmt, "sss", $title, $filename, $userIDNumber);
    //         mysqli_stmt_execute($stmt);

    //         // Display success message, display qr, and redirect
    //         echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    //         <script> 
    //         $(this).ready(function () {
    //             Swal.fire({
    //                 icon: "success",
    //                 title: "Prescription QR and PDF File Generated",
    //                 imageUrl: "'.$qrCodeFile.'", 
    //                 imageWidth: 200,
    //                 imageHeight: 200,
    //                 showCloseButton: true,
    //                 showConfirmButton: false
    //             })
    //         })  
    //         </script>';
    //     } else {
    //         // Display warning message and redirect
    //         echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    //         <script> 
    //         $(this).ready(function () {
    //             Swal.fire({
    //                 icon: "warning",
    //                 title: "Error",
    //                 showCloseButton: true,
    //                 showConfirmButton: false
    //             })
    //         })  
    
    //         setTimeout(function name(params) {
    //             document.location = "generate-qr.php";
    //         }, 3000);
    //         </script>';
    //     }        
    // } 
    
    
    
    // Check if ID Number and name is provided
    if (!empty($_POST["idNumber"]) && !empty($_POST["name"])) {
        $userIDNumber = $_POST['idNumber'];
        $name = $_POST['name'];
        $sex = $_POST['sex'];
        $address = $_POST['address'];
        $docName = $_POST['docName'];
        $illness = $_POST['illness'];
        $dateIssued = date('F j, Y');
        $licNum = $_POST['licNum'];
        $ptrNum = $_POST['ptrNum'];
        $description = $_POST['description'];

        $signature = ''; // Initialize signature variable to empty (if no file uploaded)
        // Upload signature
        if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../images/'; // Make sure this directory exists and is writable
            $signatureFileName = basename($_FILES['signature']['name']);
            $signaturePath = $uploadDir . uniqid() . '_' . $signatureFileName;

            if (move_uploaded_file($_FILES['signature']['tmp_name'], $signaturePath)) {
                $signature = $signaturePath; // Path to be used in PDF generation
            } else {
                echo "Error uploading file."; // Handle error appropriately
            }
        }

        // Get age
        $dateBirth = date('F j, Y', strtotime($_POST['dateBirth']));
        $birthDate = new DateTime($dateBirth); // Create a DateTime object for the birth date
        $today = new DateTime(); // Get the current date
        $age = $today->diff($birthDate)->y; // Calculate the age

        // Fix Spelling of type
        if ($_POST['type'] == 'medCert') {
            $type = 'Medical_Certificate';
        } elseif ($_POST['type'] == 'prodHistory') {
            $type = 'Procedure_History';
        } else {
            $type = $_POST['type'];
        }

        // QR Start
        // Generate a unique filename
        $filename = uniqid($type.'_', true); // Filename depends on file type

        // Create the new directory path using the ID number
        $idDirectory = 'pdfs/'.$userIDNumber.'/';

        // Check if the directory exists, if not, create it
        if (!file_exists($idDirectory)) {
            mkdir($idDirectory, 0777, true); // Create the folder with appropriate permissions
        }

        // Generate QR code
        $expirationTime = time() + 300; // Expires 5 minutes from now
        $qrCode = new QrCode("https://konsultap.com/med/serve-pdf.php?id=".$userIDNumber."&file=".$filename.'.pdf'."&expires=".$expirationTime); // Link in QR Code
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Create the new QR folder path using the ID number
        $qrDirectory = 'pdfs/'.$userIDNumber.'/qr/';

        // Check if the directory exists, if not, create it
        if (!file_exists($qrDirectory)) {
            mkdir($qrDirectory, 0777, true); // Create the folder with appropriate permissions
        }

        // QR file directory
        $qrCodeFile = $qrDirectory.$filename.'.png';
        $result->saveToFile($qrCodeFile);


        // PDF Start
        // Create a new PDF instance
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10); // 5mm bottom margin

        // Med Cert Layout
        if ($type == 'Medical_Certificate') {
            // School Name
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetXY(69, 10);
            $pdf->Cell(0, 10, "LA IMMACULADA CONCEPCION SCHOOL", 0, 1);

            // School Address
            $pdf->SetFont('Arial', '', 13);
            $pdf->SetXY(86, 15);
            $pdf->Cell(0, 10, "SCHOOL CLINIC", 0, 1);

            // Title
            $pdf->SetFont('Arial', 'B', 13);
            $pdf->SetXY(78, 25);
            $pdf->Cell(0, 10, "MEDICAL CERTIFICATE", 0, 1);

            // Date
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(153, 30);
            $pdf->Cell(0, 10, "Date: ", 0, 1);
            $pdf->SetXY(165, 30);
            $pdf->Cell(0, 10, "$dateIssued", 0, 1);
            $pdf->Line(165, 38, 200, 38); // (Start x, start y, end x, end y)

            // Patient Name
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(10, 45);
            $pdf->Cell(0, 10, "To Whom It May Concern, ", 0, 1);
            $pdf->SetXY(18, 57);
            $pdf->Cell(0, 10, "This is to certify that  ", 0, 1);
            $pdf->SetXY(59, 57);
            $pdf->Cell(0, 10, "$name", 0, 1);
            $pdf->Line(58, 64, 200, 64); // (Start x, start y, end x, end y)

            // Patient Address
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(10, 64);
            $pdf->Cell(0, 10, "of", 0, 1);
            $pdf->SetXY(15, 64);
            $pdf->Cell(0, 10, "$address", 0, 1);
            $pdf->Line(15, 71, 134, 71); // (Start x, start y, end x, end y)
            $pdf->SetXY(135, 64);
            $pdf->Cell(0, 10, "came to the clinic for consultation.", 0, 1);

            // Patient Complaint
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(10, 80);
            $pdf->Cell(0, 10, "Complaint: ", 0, 1);
            $pdf->SetXY(45, 80);
            $pdf->Cell(0, 10, "$illness", 0, 1);
            $pdf->Line(33, 88, 95, 88); // (Start x, start y, end x, end y)

            // Impression and Remarks
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(10, 95);
            $pdf->Cell(0, 10, "Impression and Remarks: ", 0, 1);
            $pdf->SetXY(20, 105);
            // $pdf->Cell(0, 10, "$description", 0, 1);
            $pdf->MultiCell(0, 7, "$description", 0, 'L');

            // Notes
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetXY(47, 230);
            $pdf->Cell(0, 10, "This medical certificate is issued for work/school purposes only.", 0, 1);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetXY(52, 235);
            $pdf->Cell(0, 10, "** Any unauthorized erasure or alteration will invalidate the certicate **", 0, 1);

            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(125, 245);
            $pdf->Cell(0, 10, "Sincerely yours,", 0, 1);
        }

        // Procedure Layout
        if ($type == 'Procedure_History') {
            // School Name
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetXY(70, 10);
            $pdf->Cell(0, 10, "LA IMMACULADA CONCEPCION SCHOOL", 0, 1);

            // School Address
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(79, 15);
            $pdf->Cell(0, 10, "E. CARUNCHO AVE. PASIG CITY", 0, 1);

            // Title
            $pdf->SetFont('Arial', 'B', 13);
            $pdf->SetXY(81, 25);
            $pdf->Cell(0, 10, "PROCEDURE HISTORY", 0, 1);

            // Date
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(153, 30);
            $pdf->Cell(0, 10, "Date: ", 0, 1);
            $pdf->SetXY(165, 30);
            $pdf->Cell(0, 10, "$dateIssued", 0, 1);
            $pdf->Line(165, 38, 200, 38); // (Start x, start y, end x, end y)

            // Patient Name
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(10, 45);
            $pdf->Cell(0, 10, "Patient: ", 0, 1);
            $pdf->SetXY(30, 45);
            $pdf->Cell(0, 10, "$name", 0, 1);
            $pdf->Line(27, 53, 125, 53); // (Start x, start y, end x, end y)

            // Age
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(130, 45);
            $pdf->Cell(0, 10, "Age: ", 0, 1);
            $pdf->SetXY(144, 45);
            $pdf->Cell(0, 10, "$age", 0, 1);
            $pdf->Line(140, 53, 157, 53); // (Start x, start y, end x, end y)

            // Sex
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(160, 45);
            $pdf->Cell(0, 10, "Sex: ", 0, 1);
            $pdf->SetXY(175, 45);
            $pdf->Cell(0, 10, "$sex", 0, 1);
            $pdf->Line(170, 53, 200, 53); // (Start x, start y, end x, end y)

            // Patient Address
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(10, 55);
            $pdf->Cell(0, 10, "Address: ", 0, 1);
            $pdf->SetXY(30, 55);
            $pdf->Cell(0, 10, "$address", 0, 1);
            $pdf->Line(29, 63, 200, 63); // (Start x, start y, end x, end y)

            // Title
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(10, 75);
            $pdf->Cell(0, 10, "Procedure history for $name's $illness", 0, 1);

            // Procedure Detials
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(20, 85);
            $pdf->MultiCell(0, 7, "$description", 0, 'L');
        }

        // Prescription Layout
        if ($type == 'Prescription') {
            // School Name
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetXY(70, 10);
            $pdf->Cell(0, 10, "LA IMMACULADA CONCEPCION SCHOOL", 0, 1);

            // School Address
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(79, 15);
            $pdf->Cell(0, 10, "E. CARUNCHO AVE. PASIG CITY", 0, 1);

            // Title
            $pdf->SetFont('Arial', 'B', 13);
            $pdf->SetXY(73, 25);
            $pdf->Cell(0, 10, "STUDENT HEALTH SERVICES", 0, 1);

            // Date
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(153, 30);
            $pdf->Cell(0, 10, "Date: ", 0, 1);
            $pdf->SetXY(165, 30);
            $pdf->Cell(0, 10, "$dateIssued", 0, 1);
            $pdf->Line(165, 38, 200, 38); // (Start x, start y, end x, end y)

            // Patient Name
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(10, 45);
            $pdf->Cell(0, 10, "Patient: ", 0, 1);
            $pdf->SetXY(30, 45);
            $pdf->Cell(0, 10, "$name", 0, 1);
            $pdf->Line(27, 53, 125, 53); // (Start x, start y, end x, end y)

            // Age
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(130, 45);
            $pdf->Cell(0, 10, "Age: ", 0, 1);
            $pdf->SetXY(144, 45);
            $pdf->Cell(0, 10, "$age", 0, 1);
            $pdf->Line(140, 53, 157, 53); // (Start x, start y, end x, end y)

            // Sex
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(160, 45);
            $pdf->Cell(0, 10, "Sex: ", 0, 1);
            $pdf->SetXY(175, 45);
            $pdf->Cell(0, 10, "$sex", 0, 1);
            $pdf->Line(170, 53, 200, 53); // (Start x, start y, end x, end y)

            // Patient Address
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(10, 55);
            $pdf->Cell(0, 10, "Address: ", 0, 1);
            $pdf->SetXY(30, 55);
            $pdf->Cell(0, 10, "$address", 0, 1);
            $pdf->Line(29, 63, 200, 63); // (Start x, start y, end x, end y)

            $pdf->Image('../images/rx-logo.png', 10, 68, 25, 25); // RX Logo

            // Prescriptions
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetXY(15, 100);
            $pdf->MultiCell(0, 7, "$description", 0, 'L');
        }

        // Logos
        $pdf->Image('../images/lics-logo.png', 25, 10, 18, 22); // LICS Logo
        $pdf->Image('../images/logomain.png', 15, 275, 30, 6); // KonsulTap Logo

        // Footer (same footer for all docu)
        // $pdf->Image($qrCodeFile, 10, 260, 25, 25); // QR Code

        // Signature (optional, added only if signature exists)
        if (!empty($signature)) {
            $pdf->Image("$signature", 150, 253, 20, 20); // signature here
        }
        
        // Doctor Name
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(128, 265);
        $pdf->Cell(0, 10, strtoupper("$docName M.D."), 0, 1);

        // License No
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(135, 270);
        $pdf->Cell(0, 10, "LIC No. ", 0, 1);
        $pdf->SetXY(153, 270);
        $pdf->Cell(0, 10, "$licNum", 0, 1);
        $pdf->Line(151, 277, 180, 277); // (Start x, start y, end x, end y)

        // PTR No
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(135, 275);
        $pdf->Cell(0, 10, "PTR No.", 0, 1);
        $pdf->SetXY(153, 275);
        $pdf->Cell(0, 10, "$ptrNum", 0, 1);
        $pdf->Line(151, 282, 180, 282); // (Start x, start y, end x, end y)

        $filepath = $idDirectory.$filename.'.pdf'; // Change filepath accordingly once deployed

        // Output PDF to a file
        $pdf->Output($filepath, 'F');

        // Signature (optional, added only if signature exists)
        if (!empty($signature)) {
            // After the PDF is generated, delete the uploaded signature file for security purposes
            if (file_exists($signature)) {
                unlink($signature); // Delete the uploaded signature
            } else {
                echo "Signature file does not exist.";
            }
        }
        // Document title
        $title = $type." for ".$name."'s ".$illness;
        if ($_POST['type'] == 'medCert') {
            $query = "INSERT INTO `medical_history_records`(`title`, `description`, `IdNumber`, `date_added`, `medpracNumber`) VALUES (?, ?, ?, NOW(), ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $title, $filename, $userIDNumber, $idNumber);
            mysqli_stmt_execute($stmt);
            
            // Display success message, display qr, and redirect
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "success",
                    title: "Medical Certificate QR and PDF File Generated",
                    imageUrl: "'.$qrCodeFile.'", 
                    imageWidth: 200,
                    imageHeight: 200,
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
            </script>';

        } elseif ($_POST['type'] == 'prodHistory') {
            $query = "INSERT INTO `procedure_history_records`(`title`, `description`, `IdNumber`, `date_added`) VALUES (?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sss", $title, $filename, $userIDNumber);
            mysqli_stmt_execute($stmt);

            // Display success message, display qr, and redirect
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "success",
                    title: "Prodecure History QR and PDF File Generated",
                    imageUrl: "'.$qrCodeFile.'", 
                    imageWidth: 200,
                    imageHeight: 200,
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
            </script>';
        } elseif ($_POST['type'] == 'Prescription') {
            $query = "INSERT INTO `prescription_records`(`title`, `description`, `IdNumber`, `date_added`) VALUES (?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sss", $title, $filename, $userIDNumber);
            mysqli_stmt_execute($stmt);

            // Display success message, display qr, and redirect
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "success",
                    title: "Prescription QR and PDF File Generated",
                    imageUrl: "'.$qrCodeFile.'", 
                    imageWidth: 200,
                    imageHeight: 200,
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
            </script>';
        } else {
            // Display warning message and redirect
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "warning",
                    title: "Error",
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
    
            setTimeout(function name(params) {
                document.location = "generate-qr.php";
            }, 3000);
            </script>';
        }        
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

    <!-- sweetalert -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- timeout -->
    <script src="../timeout.js"></script> 

    <title>KonsulTap︱Medical Documents</title>
    <link rel="icon" href="../images/logo_icon.png" type="image/icon type">

    <style>
        .active {
            background-color: #0B99A7 !important;
            color: #B6E9C1 !important;
        }

        .text {
            color: white;
            font-weight: bold;
            font-size: 20px;
        }

        ul li:hover .nav-link{
            background-color: #0B99A7;
            color: #B6E9C1;
        }

        .nav-center {
            display: flex;
            align-items: center; /* Vertically centers the items */
        }

        .nav-center .bi {
            margin-right: 10px; /* Adjust space between icon and text */
        }
        
        .loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: rgba(227, 226, 225, 0.75);
        transition: opacity 0.75s, visibility 0.75s;
        visibility: hidden;
    }

    .loader-visible {
        opacity: 1;
        visibility: visible;
    }

    .loader::after {
        content: "";
        width: 75px;
        height: 75px;
        border: 15px solid #204299;
        border-top-color: #FEDE57;
        border-radius: 50%;
        animation: spin 1.50s ease infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- sidebar -->
            <div class="col-sm-auto sticky-top shadow p-0" style="background-color: #006699; height:auto; max-height: 100vh; overflow-y: auto;">
                <div class="d-flex flex-sm-column flex-row flex-nowrap align-items-center sticky-top p-0" style="background-color: #006699;">
                    <span class="d-none d-sm-inline col-12 bg-light">
                        <a href="#" class="d-flex align-items-center justify-content-center px-4 py-3 mb-md-0 me-md-auto">
                            <span class="d-md-none"><img src="../images/logo_icon.png" width="35px" alt=""></span>
                            <span class="d-none d-md-inline"><img src="../images/logomain.png" width="150" alt=""></span>
                        </a>
                    </span>
                    <hr class="text-dark d-none d-md-inline mt-0" style="height: 2px; min-width: 100%;">
                    <!-- staff profile -->
                    <div class="row px-3 text-dark">
                        <div class="col-lg-3 d-flex align-items-center justify-content-center py-3">
                            <a href="staff-profile.php">
                                <img src="../images/<?php echo $profilePicture; ?>" style="object-fit: cover;" height="35px" width="35px" alt="" class="rounded-circle">

                            </a>
                        </div>
                        <!-- staff name -->

                        <div class="col-lg-9 text-lg-start text-md-center py-3 d-none d-md-inline" style="color: #FFDE59;">
                            <div class="row">
                            <a href="staff-profile.php" class="text-decoration-none text-dark text-nowrap">
                                    <h5 style="color: white;"><?php echo $fullName; ?></h5>
                                </a>
                            </div>
                            <div class="row">
                                <h5 class="fw-bold">Doctor/Nurse</h5>
                            </div>
                        </div>
                    </div>
                    <!-- sidebar nav -->
                    <ul class="nav nav-pills nav-flush flex-sm-column flex-row flex-nowrap mb-auto mb-0 px-lg-3">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi-house"></i> <span class="d-none d-md-inline">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="patient-list.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi-people"></i> <span class="d-none d-md-inline">Patient List</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="consultation-list.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-clipboard"></i> <span class="d-none d-md-inline">Consultation List</span>
                            </a>
                        </li>
                        
                        <?php
                            
                            $notifCtr = $conn->query("SELECT * FROM appointments WHERE is_unread = 1 AND status != 'denied'"); // select all notifcations
                            $ctr = mysqli_num_rows($notifCtr); // count all the notification
                            $notifRow = mysqli_fetch_assoc($notifCtr); // fetch all notification
                        ?>

                        <li class="nav-item">
                            <a href="notifications.php" class="nav-link py-3 fw-bold fs-5 text">
                                <div class="position-relative">
                                <i class="fs-4 bi bi-bell "> </i> 
                                <span class="d-none d-md-inline">Notifications</span>
                                    <?php if (!empty($notifRow['is_unread'])) {?>
                                        <span class="d-md-none position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle"></span>
                                        <span class="badge bg-danger counter d-none d-md-inline"><?php echo $ctr ?></span>
                                    <?php } ?>
                                </div>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="generate-qr.php" class="nav-link nav-center py-3 fw-bold fs-5 text active">
                                <i class="fs-4 bi bi-qr-code"></i> <span class="d-none d-md-inline">Issue Medical<br>Documents</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="generate-vid-con.php" class="nav-link nav-center py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-camera-video"></i> 
                                <span class="d-none d-md-inline">Video Consultation</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="staff-calendar.php" class="nav-link py-3 fw-bold fs-5 text ">
                                <i class="bi bi-calendar"></i> <span class="d-none d-md-inline">Calendar</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="bi bi-box-arrow-left"></i> <span class="d-none d-md-inline">Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- content -->
            <div class="col position-relative px-0 min-vh-100">
                <nav class="navbar navbar-expand-lg navbar-light bg-transparent border border-bottom">
                    <div class="container-fluid">
                        <a class="navbar-brand d-none d-md-inline" href="#">Generate Medical Documents</a>
                    </div>
                </nav>
                <div class="container">
                    <div class="row">
                        <div class="col-12 p-lg-5">
                            <div class="col-12 border border-2 border-dark rounded shadow-bottom shadow">
                                <nav class="nav navbar navbar-expand-lg border-bottom border-2" style="min-height: 4rem; background-color:#006699">
                                    <div class="container-fluid">
                                        <div class="navbar-collapse justify-content-center" id="navbarNavAltMarkup">
                                            <div class="navbar nav-pills justify-content-center">
                                            </div>
                                        </div>
                                    </div>
                                </nav>
                                <!-- document generator form -->
                                <form action="" method="post" id="qrForm" enctype="multipart/form-data">
                                    <div class="mb-3 pt-3 px-4 row">
                                        <div class="input-group justify-content-center mb-3">
                                            <label for="idNumber" class="col-lg-2 col-form-label">ID Number <i class="text-danger">*</i></label>
                                            <div class="col-lg-6">
                                                <input type="number" class="form-control rounded-pill" id="idNumber" name="idNumber" placeholder="Search by ID Number" value="<?php echo htmlentities($search); ?>" aria-label="Search" aria-describedby="basic-addon2" required>
                                            </div>
                                        </div>
                                        <div class="input-group justify-content-center mb-3">
                                            <div class="form-floating mb-3 col-lg-3 col-12 px-1">
                                                <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                                                <label for="name" class="ps-3">Name <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-3 col-12 px-1">
                                                <input type="date" class="form-control" id="dateBirth" name="dateBirth" placeholder="Date of Birth" required>
                                                <label for="dateBirth" class="ps-3">Date of Birth <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-3 col-12 px-1">
                                                <select class="form-select" aria-label="Default select example" id="sex" name="sex" required>
                                                    <option value="" selected>Select Sex</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                </select>
                                                <label for="sex" class="ps-3">Sex <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-3 col-12 px-1">
                                                <input type="text" class="form-control" id="illness" name="illness" placeholder="Patient's Illness" required>
                                                <label for="illness" class="ps-3">Patient's Illness <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-12 col-12 px-1">
                                                <input type="text" class="form-control" id="address" name="address" placeholder="Patient's Address" required>
                                                <label for="address" class="ps-3">Patient's Address <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4 col-12 px-1">
                                                <input type="text" class="form-control" id="docName" name="docName" placeholder="Doctor's Name" required>
                                                <label for="docName" class="ps-3">Doctor's Name <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4 col-12 px-1">
                                                <input type="text" class="form-control" id="licNum" name="licNum" placeholder="License Number" required>
                                                <label for="licNum" class="ps-3">License Number <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4 col-12 px-1">
                                                <input type="text" class="form-control" id="ptrNum" name="ptrNum" placeholder="PTR Number" required>
                                                <label for="ptrNum" class="ps-3">PTR Number <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="mb-3 col-lg-4 col-12 px-1">
                                                <label for="signature" class="">Upload Signature</label>
                                                <input type="file" class="form-control" id="signature" name="signature" placeholder="Upload Signature" accept="image/png">
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4 col-12 px-1">
                                                <input type="date" class="form-control" id="dateIssued" name="dateIssued" placeholder="Date Issued" readonly required>
                                                <label for="dateIssued" class="ps-3">Date Issued <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4 col-12 px-1">
                                                <select class="form-select" aria-label="Default select example" id="type" name="type" required>
                                                    <option value="" selected>Select Document Type</option>
                                                    <option value="prodHistory">Procedure History</option>
                                                    <option value="medCert">Medical Certificate</option>
                                                    <option value="Prescription">Prescription</option>
                                                </select>
                                                <label for="type" class="ps-3">Document Type <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-12 col-12 px-1">
                                                <textarea class="form-control" style="min-height: 10rem;" id="description" name="description" placeholder="Description" required></textarea>
                                                <label for="description" class="ps-3" id="descriptionLabel">Description <i class="text-danger">*</i></label>
                                            </div>
                                            
                                        </div>
                                    </div>
                                    <div class="mb-3 p-4 row justify-content-center">
                                        <div class="col-12 text-center">
                                            <!-- Clear button -->
                                            <button type="button" id="clearButton" class="btn text-light" style="background-color: #76a6e5;">Clear</button>
                                            <!-- Submit button -->
                                            <button type="submit" name="submit" id="btnSubmit" class="btn text-light" style="background-color: #000066;">Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="loader" id="loader"></div>
    <script>
        function showLoader() {
            const loader = document.getElementById('loader');
            // Show loader on page load
           loader.classList.add('loader-visible');
    
           // Hide loader after the page has fully loaded
           window.onload = function() {
               loader.classList.remove('loader-visible');
           };
        }
    
       document.addEventListener('DOMContentLoaded', function() {
           const loader = document.getElementById('loader');

           // Show loader on page load
           loader.classList.add('loader-visible');

           // Hide loader after the page has fully loaded
           window.onload = function() {
               loader.classList.remove('loader-visible');
           };
       });
   </script>

</body>

</html>

<!-- Update description label based on docu type -->
<script>
    document.getElementById('type').addEventListener('change', function() {
        const descriptionLabel = document.getElementById('descriptionLabel');
        const selectedValue = this.value;

        switch (selectedValue) {
            case 'prodHistory':
                descriptionLabel.innerHTML = 'Procedure History Details <i class="text-danger">*</i>';
                break;
            case 'medCert':
                descriptionLabel.innerHTML = 'Impression and Remarks <i class="text-danger">*</i>';
                break;
            case 'Prescription':
                descriptionLabel.innerHTML = 'Prescription Details <i class="text-danger">*</i>';
                break;
            default:
                descriptionLabel.innerHTML = 'Description <i class="text-danger">*</i>';
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('qrForm');        
        const idNumberInput = document.getElementById('idNumber');
        const nameInput = document.getElementById('name');
        const dateOfBirthInput = document.getElementById('dateBirth');
        const sexInput = document.getElementById('sex');

         // check idNumber input field
        idNumberInput.addEventListener('input', function() {
            const idNumber = this.value;

            if (idNumber.length > 0) {
                fetchUserData(idNumber);
            } else {
                clearUserData();
            }
        });

        // Function to fetch user data based on the provided ID number
        function fetchUserData(idNumber) {
            fetch('generate-qr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    'action': 'fetch_user_data',
                    'idNumber': idNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                 // Check if there is an error in the response
                if (data.error) {
                    clearUserData(); // Clear any existing user data
                    alert(data.error); 

                 // If no error, populate the fields with user data
                } else {
                    nameInput.value = data.fullName || '';
                    dateOfBirthInput.value = data.dateOfBirth || '';
                    sexInput.value = data.Gender || '';
                }
            })
            .catch(error => console.error('Error fetching user data:', error));
        }

        // Function to clear user data from input fields
        function clearUserData() {
            nameInput.value = '';
            dateOfBirthInput.value = '';
            sexInput.value = '';
        }

        // automatically set current date
        const dateIssuedInput = document.getElementById('dateIssued');
        function setTodayDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0'); // Months are zero-indexed
            const day = String(today.getDate()).padStart(2, '0');
            const todayDate = `${year}-${month}-${day}`;
            
            dateIssuedInput.value = todayDate;
        }
        setTodayDate();
        
        // Clear button function
        function clearForm() {
            // Clear all fields except for the dateIssued field
            document.getElementById('idNumber').value = '';
            document.getElementById('name').value = '';
            document.getElementById('dateBirth').value = '';
            document.getElementById('illness').value = '';
            document.getElementById('sex').selectedIndex = 0; // Reset select field
            document.getElementById('address').value = '';
            document.getElementById('docName').value = '';
            document.getElementById('licNum').value = '';
            document.getElementById('ptrNum').value = '';
            document.getElementById('signature').value = ''; // Clear file input
            document.getElementById('type').selectedIndex = 0; // Reset document type select
            document.getElementById('description').value = '';
        }

        // Attach the clearForm function to the clear button
        const clearButton = document.getElementById('clearButton');
        clearButton.addEventListener('click', clearForm);

    });

</script>
