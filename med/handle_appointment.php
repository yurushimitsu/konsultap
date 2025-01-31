<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../config.php';
require '../vendor/autoload.php';
require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

// Check if the user is logged in and has the right role
if (!isset($_SESSION['IdNumber']) || empty($_SESSION['IdNumber']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'medprac') {
    echo "Invalid role or not logged in";
    exit;
}

// Fetch user details
$idNumber = $_SESSION['IdNumber'];
$query = "SELECT fullName, Email FROM medprac WHERE IdNumber = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $idNumber);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$fullName = $row['fullName'];
$email = $row['Email'];

// Function to send email reminder
function sendEmailReminder($recipientEmail, $appointmentDate, $appointmentTime) {
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'konsultap2024@gmail.com';
    $mail->Password = 'yxhc yoxm ksht dluh'; // Use environment variables for security
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Recipient settings
    $mail->setFrom('konsultap2024@gmail.com', 'KonsulTap');
    $mail->addAddress($recipientEmail);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = "Appointment Reminder";
    $mail->Body = "Hello, <br><br>Your appointment is scheduled for $appointmentDate at $appointmentTime.<br>Please be on time.<br><br>Best Regards,<br>KonsulTap";

    return $mail->send();
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept') {
    $appointment_id = mysqli_real_escape_string($conn, $_POST['id']);
    
    // Fetch appointment details
    $query = "SELECT appointment_date, appointment_time, Email FROM appointments WHERE id = '$appointment_id'";
    $result = mysqli_query($conn, $query);
    $appointment = mysqli_fetch_assoc($result);

    // Send email reminder
    if (sendEmailReminder($appointment['Email'], $appointment['appointment_date'], $appointment['appointment_time'])) {
        echo "Appointment accepted and email sent.";
    } else {
        echo "Appointment accepted but failed to send email.";
    }
}
?>
