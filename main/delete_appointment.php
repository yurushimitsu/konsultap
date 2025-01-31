<?php
session_start();
require '../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Load PHPMailer classes via Composer's autoloader

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $appointmentId = $_POST['id'];

    // Prepare the select statement to get the appointment time, IdNumber, and user's name
    $appointmentTimeQuery = "SELECT appointment_date, appointment_time, students.fullName AS studentName, students.Email AS studentEmail FROM appointments 
                             JOIN students ON appointments.IdNumber = students.IdNumber 
                             WHERE appointments.id = ?";
    $appointmentStmt = mysqli_prepare($conn, $appointmentTimeQuery);
    if (!$appointmentStmt) {
        die("Error preparing appointment time statement: " . mysqli_error($conn));
    }

    // Bind parameter
    mysqli_stmt_bind_param($appointmentStmt, "i", $appointmentId);
    mysqli_stmt_execute($appointmentStmt);
    $result = mysqli_stmt_get_result($appointmentStmt);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $appointmentDate = date('F j, Y', strtotime($row['appointment_date']));
        $appointmentTime = $row['appointment_time'];
        $userName = $row['studentName'];  // Get the user's full name
        $userEmail = $row['studentEmail'];  // Get the user's email address

        // Format the time and generate 1-hour interval
        $startTime = new DateTime($appointmentTime);
        $endTime = clone $startTime;
        $endTime->modify('+1 hour'); // Add 1 hour to the appointment start time
        $formattedTimeInterval = $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A');

        // Delete the appointment
        $query = "DELETE FROM appointments WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            die("Error preparing delete statement: " . mysqli_error($conn));
        }

        // Bind parameter
        mysqli_stmt_bind_param($stmt, "i", $appointmentId);

        // Execute statement
        if (mysqli_stmt_execute($stmt)) {
            $idNumber = $_POST['idNumber'];
            $reason = $_POST['reason'];

            // Insert into cancel_appointments table (without appointment_time_cancelled)
            $cancelQuery = "INSERT INTO cancel_appointments (IdNumber, reason, appointment_date_cancelled) VALUES (?, ?, NOW())";
            $cancelStmt = mysqli_prepare($conn, $cancelQuery);
            if (!$cancelStmt) {
                die("Error preparing insert statement: " . mysqli_error($conn));
            }

            // Bind parameters for the insert
            mysqli_stmt_bind_param($cancelStmt, "ss", $idNumber, $reason);

            // Execute insert statement
            if (!mysqli_stmt_execute($cancelStmt)) {
                echo "Error inserting canceled appointment: " . mysqli_error($conn);
            }

            // Send email notification using PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = ''; // Your Gmail username
                $mail->Password = ''; // Your app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                // Recipient
                $mail->setFrom('', 'KonsulTap'); // Sender's email and name
                $mail->addAddress($userEmail); // Recipient's email from the query
                
                $htmlContent = file_get_contents('../emailTemplates/cancel_appointment_template.php');

                // Replace placeholders with actual values
                $htmlContent = str_replace('{{userName}}', $userName, $htmlContent);
                $htmlContent = str_replace('{{appointmentDate}}', $appointmentDate, $htmlContent);
                $htmlContent = str_replace('{{formattedTimeInterval}}', $formattedTimeInterval, $htmlContent);
                $htmlContent = str_replace('{{reason}}', $reason, $htmlContent);
                $htmlContent = str_replace('{{year}}', date('Y'), $htmlContent);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'KONSULTAP: Appointment Cancellation Notice';
                $mail->Body = $htmlContent; // Set the Body of the email

                $mail->send();
                echo 'Cancellation email has been sent';
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

            // Close statements
            mysqli_stmt_close($cancelStmt);
            mysqli_stmt_close($stmt);
        } else {
            echo "Error deleting appointment: " . mysqli_error($conn);
        }

        // Close appointment time statement
        mysqli_stmt_close($appointmentStmt);

        // Redirect back to appointment.php
        header("Location: appointment.php");
        exit();
    } else {
        echo "Appointment not found.";
        mysqli_stmt_close($appointmentStmt);
    }
}
?>
