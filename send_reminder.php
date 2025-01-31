<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'config.php';
require 'vendor/autoload.php';
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';


// Retrieve appointments with status 'accept'
$query = "SELECT a.id, a.appointment_date, a.appointment_time, a.appointment_type, a.created_at, a.status, COALESCE(s.Email, f.Email) AS Email, COALESCE(s.fullName, f.fullName) AS Name
          FROM appointments AS a
          LEFT JOIN students AS s ON a.IdNumber = s.IdNumber
          LEFT JOIN faculty AS f ON a.IdNumber = f.IdNumber
          WHERE a.status = 'accept'";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Query Error: " . mysqli_error($conn);
} elseif (mysqli_num_rows($result) == 0) {
    echo "No accepted appointments found.";
} else {
    // date_default_timezone_set('Asia/Manila'); // Initialize timezone
    
    while ($row = mysqli_fetch_assoc($result)) {
        $recipientEmail = $row['Email'];
        $recipientName = $row['Name'];
        $formalDate = date('F j, Y', strtotime($row['appointment_date'])); // Date to be send in email
        $appointmentDate = date('Y-m-d', strtotime($row['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($row['appointment_time']));
        $endTime = date('g:i A', strtotime('+1 hour', strtotime($row['appointment_time']))); // + 1hr to appointment time
        $appointmentType = ucfirst($row['appointment_type']);
        $createdAtDate = date('F j, Y', strtotime($row['created_at']));
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day', strtotime($today)));
        
        $current_time = date('H:i:s');

        if ($tomorrow == $appointmentDate) {
            // echo $recipientEmail.' Today is '.$today.' in the '.$current_time.' and tomo is '.$tomorrow.' and '.$appointmentDate.' your appointment is tomo<br>';
            
            // Send Email with PHPMailer
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->SMTPDebug = 2; // Enable debug output for detailed error messages
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = '';
            // Use your app password here if 2-Step Verification is enabled
            $mail->Password = ''; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // or use ENCRYPTION_STARTTLS for port 587
            $mail->Port = 465; // Use 587 for TLS

            // Recipient settings
            $mail->setFrom('', 'KonsulTap');
            $mail->addAddress($recipientEmail);
            
            // Ensure this is in absolute path
            $htmlContent = file_get_contents('/home/u801109301/domains/konsultap.com/public_html/emailTemplates/reminder_template.php');

            // Replace placeholders with actual values
            $htmlContent = str_replace('{{name}}', $recipientName, $htmlContent);
            $htmlContent = str_replace('{{appointmentType}}', $appointmentType , $htmlContent);
            $htmlContent = str_replace('{{formalDate}}', $formalDate, $htmlContent);
            $htmlContent = str_replace('{{appointmentTime}}', $appointmentTime, $htmlContent);
            $htmlContent = str_replace('{{endTime}}', $endTime , $htmlContent);
            $htmlContent = str_replace('{{createdAtDate}}', $createdAtDate , $htmlContent);
            $htmlContent = str_replace('{{year}}', date('Y'), $htmlContent);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = "Appointment Reminder";
            $mail->Body = $htmlContent; // Set the Body of the email

            if ($mail->send()) {
                echo "Reminder sent to $recipientEmail successfully.<br>";
            } else {
                echo "Error sending email to $recipientEmail: " . $mail->ErrorInfo . "<br>";
            }
            echo 'Succesfully send email to users';
        }        
    }
}
?>
