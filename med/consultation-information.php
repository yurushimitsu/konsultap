<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../config.php';
require 'agora/RtcTokenBuilder.php';
require '../vendor/autoload.php';
require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

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

// Retrieve appointment details
$appointment_id = mysqli_real_escape_string($conn, $_GET['id']);

$query = "SELECT a.id, a.appointment_date, a.appointment_time, a.created_at, a.status, a.reason, a.appointment_type, COALESCE(s.fullName, f.fullName) AS fullName, COALESCE(s.Email, f.Email) AS Email, COALESCE(s.profile_picture, f.profile_picture) AS userProfilePic, a.IdNumber
          FROM appointments AS a
          LEFT JOIN students AS s ON a.IdNumber = s.IdNumber
          LEFT JOIN faculty AS f ON a.IdNumber = f.IdNumber
          WHERE a.id = '$appointment_id'";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Query Error: " . mysqli_error($conn);
} elseif (mysqli_num_rows($result) == 0) {
    echo "Appointment not found.";
} else {
    $row = mysqli_fetch_assoc($result);
    $applicationDate = date('F j, Y', strtotime($row['created_at']));
    $consultationDate = date('F j, Y', strtotime($row['appointment_date']));
    $status = htmlspecialchars($row['status']);
    $reason = htmlspecialchars($row['reason']);
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if the appointment ID is valid
if ($appointment_id === null) {
    die("Invalid appointment ID.");
}
    $patientName = $row['fullName'];
    $appointmentTime = date('g:i A', strtotime($row['appointment_time']));
    $appointmentType = htmlspecialchars($row['appointment_type']);
    $recipientEmail = $row['Email']; // The student's email
    $recipientIdNumber = $row['IdNumber']; // IdNumber of the student/faculty
    $consultDone = $row['consult_done']; // Retrieve consult_done status
    
    $userProfilePic = '../images/'.$row['userProfilePic'];
    if (empty($row['userProfilePic'])) {
        $userProfilePic = "../images/default_profile_picture.jpg";
    }

    $videoConfId = ''; // Variable to store videoconference_id

if (isset($_POST['mark_as_done'])) {
    // Get the appointment ID from the form submission
    $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);

    // Prepare the query to update consult_done to 1
    $updateQuery = "UPDATE appointments SET consult_done = 1 WHERE id = ?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);

    // Execute the query and check if it was successful
    if (mysqli_stmt_execute($stmt)) {
        // Redirect back to consultation-list.php
        
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "success",
                    title: "Consultation Mark as Done",
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
    
            setTimeout(function name(params) {
                document.location = "consultation-list.php";
            }, 3000);
            </script>';
    } else {
        // Redirect back with an error status if the update failed
        header("Location: consultation-list.php?status=error");
        exit();
    }
}

    // Retrieve videoconference_id from the database
    // Query the videoconference table for a matching appointment_id
$query = "SELECT videoconference_id, call_duration FROM videoconference WHERE appointments_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if a videoconference_id was found
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $videoConfId = $row['videoconference_id'];
    
    $callDuration = $row['call_duration']; // Assuming this is in seconds
    // Convert the call duration to hours, minutes, and seconds
    $hours = floor($callDuration / 3600);
    $minutes = floor(($callDuration % 3600) / 60);
    $seconds = $callDuration % 60;
    
    // Format the result as HH:MM:SS
    $callDurationFormatted = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}

    if ($fetchResult && mysqli_num_rows($fetchResult) > 0) {
        $videoConfId = mysqli_fetch_assoc($fetchResult)['videoconference_id'];
    }

    // Check if 'Generate Video Link' button was pressed and no existing videoconference_id found
    if (isset($_POST['generate_video_link']) && empty($videoConfId)) {
        // Agora API Integration - Generate Token and Link
        $appID = 'ad9d0e39ec7d499b8cd54b5aaea0ec6c'; // Agora App ID
        $appCertificate = '78f98177decc40849391308b48659c83'; // Agora App Certificate
        $channelName = "video_conf_" . uniqid(); // Unique channel name
        $token = generateAgoraToken($appID, $appCertificate, $channelName, $recipientIdNumber);

        // Agora Link (you can customize this further)
        $videoLink = "https://konsultap.com/videoconference/videoconference.php?videoconference_id=$channelName";

        // Save to the videoconference table
       $videoconferenceQuery = "INSERT INTO videoconference (appointments_id, videoconference_id, idNumber_user1, idNumber_user2, fullName_user1, fullName_user2)
                         VALUES ('$appointment_id', '$channelName', '$idNumber', '$recipientIdNumber', '$fullName', '{$row['fullName']}')";

// Execute the query
if (mysqli_query($conn, $videoconferenceQuery)) {
    // echo "Video conference details stored successfully.";
} else {
    echo "Error: " . mysqli_error($conn);
}
        
        // Update url in appoinments table
        $urlQuery = "UPDATE appointments SET url = '$videoLink' WHERE id = '$appointment_id'";
        mysqli_query($conn, $urlQuery);

        // Update the $videoConfId variable with the newly created id
        $videoConfId = $channelName;

        // Send Email with PHPMailer
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Recipient settings
        $mail->setFrom('', 'KonsulTap');
        $mail->addAddress($recipientEmail); // Send to the student's email
        
        $htmlContent = file_get_contents('../emailTemplates/generate_vidcon_link_template.php');

        // Replace placeholders with actual values
        $htmlContent = str_replace('{{patientName}}', $patientName, $htmlContent);
        $htmlContent = str_replace('{{appointmentDate}}', $appointmentDate, $htmlContent);
        $htmlContent = str_replace('{{appointmentTime}}', $appointmentTime, $htmlContent);
        $htmlContent = str_replace('{{appointmentType}}', $appointmentType, $htmlContent);
        $htmlContent = str_replace('{{videoLink}}', $videoLink, $htmlContent);
        $htmlContent = str_replace('{{year}}', date('Y'), $htmlContent);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Video Consultation Link";
        // $mail->Body = "Hello, <br><br>Here is your video consultation link: <a href='$videoLink'>$videoLink</a>.<br>Please click on the link at the time of your appointment.<br><br>Best Regards,<br>KonsulTap";
        $mail->Body = $htmlContent; // Set the Body of the email

        if ($mail->send()) {
            // echo "Video link sent successfully.";
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "success",
                    title: "Link Sent",
                    text: "Video link sent successfully.",
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
            </script>';
        } else {
            echo "Error sending email: " . $mail->ErrorInfo;
        }
    }
}

function generateAgoraToken($appID, $appCertificate, $channelName, $uid) {
    // Token generation logic using Agora SDK
    $expireTimeInSeconds = 3600;
    $currentTimeStamp = (new DateTime())->getTimestamp();
    $privilegeExpiredTs = $currentTimeStamp + $expireTimeInSeconds;
    return RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, $uid, RtcTokenBuilder::RolePublisher, $privilegeExpiredTs);
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

        <title>KonsulTap︱Consultation List</title>
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
                            <a href="consultation-list.php" class="nav-link py-3 fw-bold fs-5 text active">
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
                            <a href="generate-qr.php" class="nav-link nav-center py-3 fw-bold fs-5 text">
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


                        </div>
                    </nav>
                    <div class="row p-lg-5 p-3 text-lg-start text-center m-0">
                        <h1 class="col-md-12">Consultation Information</h1>
                    </div>
                    <div class="col-12 px-lg-5 px-3 pb-5">
                        <div class="col-12 border border-2 border-dark rounded shadow-bottom shadow">
                            <!-- navbar for card -->
                            <nav class="nav navbar navbar-expand-lg border-bottom border-2" style="background-color: #000066;">
                                <div class="container-fluid">
                                    <div class="navbar" id="navbarNavAltMarkup">
                                        <img src="<?php echo $userProfilePic; ?>" width="55px" alt="" class="rounded-circle">
                                        <h3 class="text-white ps-3"><?php echo $patientName; ?></h3>
                                    </div>
                                </div>
                            </nav>
                            <!-- card information -->
                            <div class="row p-lg-5 px-3">
                                <!-- information -->
                                <div class="pb-lg-4">
                                <p><span class="fw-bold">Consultation Status:</span> <?php echo $status; ?>
                                <p><span class="fw-bold">Consultation Reason:</span> <?php echo $reason; ?>
                                <p><span class="fw-bold">Consultation Type:</span> <?php echo $appointmentType; ?>

                                    <p><span class="fw-bold">Date of application of consultation: </span><?php echo $applicationDate; ?>
                                    <p><span class="fw-bold">Consultation Date: </span><?php echo $consultationDate; ?>
                                    <p><span class="fw-bold">Consultation Time: </span><?php echo($appointmentTime) ?>
                                    <?php if ($videoConfId): ?>
                                        <p><span class="fw-bold">Videoconference ID:</span> <?php echo $videoConfId; ?></p>
                                        <?php if ($callDuration != 0): ?>
                                            <p><span class="fw-bold">Video Call Duration:</span> <?php echo $callDurationFormatted; ?></p>
                                        <?php else: ?>
                                            <p>No Video Call Duration available for this appointment.</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p>No Videoconference ID available for this appointment.</p>
                                    <?php endif; ?>
                                    
                                    

                                    <div class="text-lg-end text-center p-3 d-flex justify-content-lg-end justify-content-center" style="gap: 10px;">
                                        <?php if ($appointmentType === 'online') { 
                                                if (empty($videoConfId)) { ?>
                                            <form method="post">
                                                <button type="submit" name="generate_video_link" class="text-decoration-none button border-0 text-white px-5 py-1" style="background-color: #000066; border-radius: 25px;" onclick="showLoader()">
                                                    <span class="d-none d-sm-inline">Generate Video Link</span>
                                                    <i class="bi bi-link-45deg d-inline d-sm-none "></i>
                                                </button>
                                            </form>
                                        <?php } }?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                                            <button type="submit" name="mark_as_done" class="text-decoration-none button border-0 text-white px-5 py-1" style="background-color: #000066; border-radius: 25px;">
                                                <span class="d-none d-sm-inline">MARK AS DONE</span>
                                                <i class="bi bi-check-circle d-inline d-sm-none"></i>
                                            </button>
                                        </form>
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

            </div>


    </body>

    </html>