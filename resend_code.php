<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'config.php';
require 'vendor/autoload.php';

// Function to generate a random 5-digit code
function generateVerificationCode() {
    return rand(10000, 99999);
}

// Check if the resend button is clicked
if (isset($_POST['resendCode'])) {
    $idNumber = $_SESSION['IdNumber']; // Assuming IdNumber is stored in session
    $userRole = $_SESSION['role']; // Assuming user role is stored in session

    // Perform database query to get user email based on role
    $query = "SELECT Email, fullName FROM $userRole WHERE IdNumber = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $idNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if a row is returned
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['verification_code'] = generateVerificationCode(); // Generate and store new verification code in session

        // Send verification code to user's email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = ''; // Your Gmail username
            $mail->Password = ''; // Your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            //Recipient
            $mail->setFrom(''); // Sender's email
            $mail->addAddress($user['Email']); // Recipient's email

             $htmlContent = file_get_contents('verification_template');

            // Replace placeholders with actual values
            $htmlContent = str_replace('{{name}}', $name, $htmlContent);
            $htmlContent = str_replace('{{verification_code}}', $_SESSION['verification_code'], $htmlContent);
            $htmlContent = str_replace('{{year}}', date('Y'), $htmlContent);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'KONSULTAP: RESEND TWO-FACTOR LOGIN AUTHENTICATION';
            $mail->Body = $htmlContent; // Set the Body of the email
        
            $mail->send();
            echo 'New verification code has been sent to your email.';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo 'Error retrieving user information.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <title>Resend Verification Code</title>
</head>
<body>
    <div class="container">
        <h1>Resend Verification Code</h1>
        <form method="POST">
            <button type="submit" name="resendCode" class="btn btn-primary">Resend Verification Code</button>
        </form>
    </div>
</body>
</html>
