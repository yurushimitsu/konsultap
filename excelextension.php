<?php
session_start();
require 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if(isset($_FILES['import_file'])) {
    $fileName = $_FILES['import_file']['name'];
    $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $allowed_ext = ['xls', 'csv', 'xlsx'];

    if(in_array($file_ext, $allowed_ext)) {
        $inputFileNamePath = $_FILES['import_file']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileNamePath);
        $data = $spreadsheet->getActiveSheet()->toArray();

        foreach($data as $row) {
            $fullname = $row['0'];
            $idNumber = $row['1'];
         
            $gradeLevel = isset($row['2']) ? $row['2'] : null; // For students
            // Check if $idNumber is a valid integer (string of digits)
            if ($idNumber !== null && !ctype_digit($idNumber)) {
                $_SESSION['message'] = "idNumber";
                header('Location: Admin/admincrud.php');
                exit(0);
            }

             // Check if $gradeLevel is valid (assuming it should also be an integer)
            if ($gradeLevel !== null && !ctype_digit($gradeLevel)) {
                $_SESSION['message'] = "gradeLevel";
                header('Location: Admin/admincrud.php');
                exit(0);
            }

            $gender = $row['3'];
            $email = $row['4'];
            $height = $row['5'];
            $weight = $row['6'];
            $phone = $row['7'];
            $contactPerson = $row['8'];
            $dateOfBirth = $row['9'];
            $password = password_hash($row['10'], PASSWORD_BCRYPT); // Hashed password
            $role = $row['11']; // Role column in the Excel file
            $status = $row['12']; // User status

            // Check the role specified in the Excel file and determine the table
            switch ($role) {
                case 'faculty':
                    $table = 'faculty';
                    $idField = 'IdNumber';
                    break;
                case 'student':
                    $table = 'students';
                    $idField = 'IdNumber';
                    break;
                case 'itc':
                    $table = 'itc';
                    $idField = 'IdNumber';
                    break;
                case 'medprac':
                    $role = 'medical practitioner'; 
                    $table = 'medprac';
                    $idField = 'IdNumber';
                    break;
                default:
                    // Handle other roles or invalid roles
                    continue 2; // Skip this row and proceed to the next iteration of the outer loop
            }
            
            // Check if the row contains valid data
            if (!empty($fullname) && !empty($idNumber)) {
                // Check if the record already exists based on IdNumber
                $checkQuery = "SELECT * FROM $table WHERE $idField = '$idNumber'";
                $checkResult = mysqli_query($conn, $checkQuery);

                if(mysqli_num_rows($checkResult) > 0) {
                    // Update existing record
                    $updateQuery = "UPDATE $table SET fullName='$fullname', Gender='$gender', Email='$email', Height='$height', Weight='$weight', Phone='$phone', ContactPerson='$contactPerson', dateOfBirth='$dateOfBirth', Password='$password', status='$status', disabled_at=null WHERE $idField='$idNumber'";
                    mysqli_query($conn, $updateQuery);
                } else {
                    // Insert new record
                    $insertQuery = "INSERT INTO $table (fullName, $idField, " . ($table == 'students' ? "GradeLevel, " : "") . "Gender, Email, Height, Weight, Phone, ContactPerson, dateOfBirth, Password, status) VALUES ('$fullname', '$idNumber', " . ($table == 'students' ? "'$gradeLevel', " : "") . "'$gender', '$email', '$height', '$weight', '$phone', '$contactPerson', '$dateOfBirth', '$password', '$status')";
                    mysqli_query($conn, $insertQuery);
                }
            }
        }

        $_SESSION['message'] = "success";
        // header('Location: Admin/admincrud');
        
        // Output the loader HTML and JavaScript to show it before redirect
        echo '
        <html>
        <head>
            <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        
        <title>KonsulTap︱Importing Excel</title>
        <link rel="icon" href="images/logo_icon.png" type="image/icon type">
        
            <style>
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
            <script>
                // Show the loader first, then redirect after a delay
                window.onload = function() {
                    document.getElementById("loader").classList.add("loader-visible");
                    // Redirect after 1 second (1000ms) to allow loader to appear
                    setTimeout(function() {
                        window.location.href = "Admin/admincrud";
                    }, 3000);  // Adjust the delay as needed
                };
            </script>
        </head>
        <body>
            <!-- Loader (hidden by default, will be shown by JS) -->
            <div class="loader" id="loader"></div>
        </body>
        </html>
        ';
        
        exit(0);
    } else {
        $_SESSION['message'] = "invalid";
        header('Location: Admin/admincrud');
        exit(0);
    }
}
?>
