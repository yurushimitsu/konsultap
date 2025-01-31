<?php
session_start();
require '../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Load PHPMailer classes via Composer's autoloader

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

$idNumber = $_SESSION['IdNumber'];

// Fetch user details from the database based on the IdNumber
$query = "SELECT fullName, Email, profile_picture, Password FROM medprac WHERE IdNumber = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $idNumber);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $medpracFullName = $row['fullName'];
    $medpracEmail = $row['Email'];
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
    $medpracFullName = "User Not Found";
    $medpracEmail = "N/A";
    $profilePicture = "../images/default_profile_picture.jpg";
}

// Handling appointment cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $appointmentId = $_POST['id'];

    // Prepare the select statement to get the appointment time, student's name, and email
    $appointmentTimeQuery = "SELECT appointment_time, 
                                     students.fullName AS studentName, 
                                     students.Email AS studentEmail 
                              FROM appointments 
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
        $appointmentTime = $row['appointment_time'];
        $studentName = $row['studentName']; // Get the user's full name
        $studentEmail = $row['studentEmail']; // Get the user's email address

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

            // Insert into cancel_appointments table
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
                $mail->setFrom('', 'KonsulTAP Team'); // Sender's email and name
                $mail->addAddress($studentEmail); // Recipient's email from the query

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'KONSULTAP: Appointment Cancellation Notice';
                $mail->Body = 'Dear ' . $studentName . ',' .
                    '<br><br>We regret to inform you that your appointment scheduled for ' . $formattedTimeInterval . ' has been unfortunately cancelled.' .
                    '<br><br>Dr. ' . $medpracFullName . ' has cancelled your appointment today for the following reason: ' . $reason . 
                    '<br><br>If you have any questions, feel free to contact us.<br><br>' .
                    'Best regards,<br>KonsulTAP Team';


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

        // Redirect back to staff-calendar.php after deletion
        header("Location: staff-calendar");
        exit();
    } else {
        echo "Appointment not found.";
        mysqli_stmt_close($appointmentStmt);
    }
}
?>
