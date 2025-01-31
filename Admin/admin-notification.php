<?php
require '../config.php';
require '../vendor/autoload.php'; // Ensure you have PHPMailer installed via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Check if the user is not logged in or does not have the role 'itc', redirect to login page or show invalid role message
if (!isset($_SESSION['IdNumber']) || empty($_SESSION['IdNumber']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'itc') {
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
$query = "SELECT fullName, Email, profile_picture FROM itc WHERE IdNumber = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $idNumber);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $fullName = $row['fullName'];
    $email = $row['Email'];
    $profilePicture = $row['profile_picture'];

    // Check if profile picture exists, if not, use default
    if (empty($profilePicture)) {
        $profilePicture = "../images/default_profile_picture.jpg";
    }
} else {
    // Handle the case if user details are not found
    $fullName = "User Not Found";
    $email = "N/A";
    $profilePicture = "../images/default_profile_picture.jpg";
}

// Fetch unread notifications for password reset
$notifResult = $conn->query("SELECT * FROM notification_forgotpassword WHERE is_unread = 1");

// Function to generate a random password
function generateRandomPassword($length = 5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomPassword = '';
    for ($i = 0; $i < $length; $i++) {
        $randomPassword .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomPassword;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['click'])) {
    $idNumber = $_POST['id'];
    $newPassword = generateRandomPassword();

    // Hash the password before saving it in the database
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Determine the correct table to update
    $tables = ['students', 'faculty', 'medprac', 'itc'];
    $passwordReset = false;
    $userEmail = '';

    foreach ($tables as $table) {
        $updateSql = "UPDATE $table SET Password = ? WHERE IdNumber = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ss", $hashedPassword, $idNumber);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Fetch the email of the user
            $emailQuery = "SELECT Email, fullName FROM $table WHERE IdNumber = ?";
            $emailStmt = $conn->prepare($emailQuery);
            $emailStmt->bind_param("s", $idNumber);
            $emailStmt->execute();
            $emailResult = $emailStmt->get_result();
            $emailRow = $emailResult->fetch_assoc();
            $userEmail = $emailRow['Email'];
            $userName = $emailRow['fullName'];

            // Update notification status to read (is_unread = 0)
            $updateNotifSql = "UPDATE notification_forgotpassword SET is_unread = 0 WHERE IdNumber = ?";
            $notifStmt = $conn->prepare($updateNotifSql);
            $notifStmt->bind_param("s", $idNumber);
            $notifStmt->execute();

            $passwordReset = true;
            break;
        }
    }

    if ($passwordReset && !empty($userEmail)) {
        // Send the email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'konsultap2024@gmail.com';
            $mail->Password = 'yxhc yoxm ksht dluh'; // App-specific password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('konsultap2024@gmail.com', 'KonsulTap Admin');
            $mail->addAddress($userEmail); // Add the user's email address
            
            $htmlContent = file_get_contents('../emailTemplates/reset_pass_template.php');

            // Replace placeholders with actual values
            $htmlContent = str_replace('{{userName}}', $userName, $htmlContent);
            $htmlContent = str_replace('{{newPassword}}', $newPassword, $htmlContent);
            $htmlContent = str_replace('{{year}}', date('Y'), $htmlContent);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'KonsulTap Password Reset';
            $mail->Body = $htmlContent; // Set the Body of the email

            $mail->send();
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "success",
                    title: "Password reset successfully and email sent!",
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  

            setTimeout(function name(params) {
                document.location = "admin-notification.php";
            }, 3000);
            </script>';
        } catch (Exception $e) {
            echo "<script>alert('Password reset but failed to send email. Mailer Error: {$mail->ErrorInfo}');</script>";
        }
    } else {
        echo "<script>alert('Failed to reset password or email not found.');</script>";
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

    <title>KonsulTap︱Notifications</title>
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

        ul li:hover .nav-link {
            background-color: #0B99A7;
            color: #B6E9C1;
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
    
    @media (min-width: 768px) {
        .custom-min-width {
            min-width: 16rem;
        }
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- sidebar -->
            <div class="col-sm-auto sticky-top shadow p-0 custom-min-width" style="background-color: #006699; height:auto; max-height: 100vh; overflow-y: auto;">
                <div class="d-flex flex-sm-column flex-row flex-nowrap align-items-center sticky-top p-0" style="background-color: #006699;">
                    <span class="d-none d-sm-inline col-12 bg-light">
                        <a href="#" class="d-flex align-items-center justify-content-center px-4 py-3 mb-md-0 me-md-auto">
                            <span class="d-md-none"><img src="../images/logo_icon.png" width="35px" alt=""></span>
                            <span class="d-none d-md-inline"><img src="../images/logomain.png" width="150" alt=""></span>
                        </a>
                    </span>
                    <hr class="text-dark d-none d-md-inline mt-0" style="height: 2px; min-width: 100%;">
                    <!-- admin profile -->
                    <div class="row px-3 text-dark">
                       <div class="col-lg-3 d-flex align-items-center justify-content-center py-3">
                            <a href="admin-profile.php">
                                <?php if (isset($fullName)) { ?>
                                    <?php
                                    // Check if profile_picture is set, otherwise use default
                                    if (!empty($profilePicture)) {
                                        echo "<img src='../images/" . $profilePicture . "' style='object-fit: cover;' height='35px' width='35px' alt='' class='rounded-circle'>";
                                    } else {
                                        echo "<img src='../images/default_profile_picture.jpg' style='object-fit: cover;' height='35px' width='35px' alt='' class='rounded-circle'>";
                                    }
                                    ?>
                                <?php } ?>
                            </a>
                        </div>
                        <!-- admin name -->
                        <div class="col-lg-9 text-lg-start text-md-center py-3 d-none d-md-inline" style="color: #FFDE59;">
                            <div class="row">
                            <a href="admin-profile.php" class="text-decoration-none text-dark text-nowrap">
                                    <h5 style="color: white;"><?php echo $fullName; ?></h5>
                                </a>
                            </div>
                            <div class="row">
                                <h5 class="fw-bold">Administrator</h5>
                            </div>
                        </div>
                    </div>
                    <!-- sidebar nav -->
                    <ul class="nav nav-pills nav-flush flex-sm-column flex-row flex-nowrap mb-auto mb-0 px-lg-3">
                        <li class="nav-item">
                            <a href="admincrud.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-list-task"></i> <span class="d-none d-md-inline">Manage User</span>
                            </a>
                        </li>

                        <?php
                            $notifCtr = $conn->query("SELECT * FROM notification_forgotpassword WHERE is_unread = 1");
                            $ctr = mysqli_num_rows($notifCtr); // count all the notification
                            $notifRow = mysqli_fetch_assoc($notifCtr); // fetch all notification
                        ?>

                        <li class="nav-item">
                            <a href="admin-notification.php" class="nav-link py-3 fw-bold fs-5 text active">
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
                            <a href="admin-calendar.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="bi bi-calendar"></i> <span class="d-none d-md-inline">Calendar</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="archives.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="bi bi-archive"></i> <span class="d-none d-md-inline">Archives</span>
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
                        <a class="navbar-brand d-none d-md-inline" href="#">Notification List</a>
                        <div class="d-flex justify-content-lg-end justify-content-md-end justify-content-center collapse navbar-collapse" id="navbarSupportedContent">

                        </div>
                    </div>
                </nav>
                <div class="row p-lg-5 p-3 text-lg-start text-center m-0">
                    <h1 class="col-lg-6 col-md-12">Notifications</h1>
                </div>
                <div class="col-12 px-5 pb-5">
                    <div class="col-12 border border-2 border-dark rounded shadow-bottom shadow" style="min-height: 40rem;">
                        <!-- navbar for card -->
                        <nav class="nav navbar navbar-expand-lg border-bottom border-2">
                            <div class="container-fluid">
                                <div class="navbar-collapse justify-content-lg-start justify-content-center" id="navbarNavAltMarkup">
                                    <div class="navbar nav-pills justify-content-center">
                                        <a class="nav-link px-5 text-white" style="background-color: #000066;" href="notifications.php">All Notifications</a>
                                    </div>
                                </div>
                            </div>
                        </nav>
                        <!-- card information -->
                        <div class="row">
                            <div class="table-responsive">
                                <!-- notification list table -->
                                <table class="table table-hover">
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($notifResult)) {?>
                                                <form action="#" id="form-id" method="post">
                                                    <input hidden type="text" name="id" value="<?php echo $row['IdNumber']; ?>">
                                                    <input hidden type="text" name="date" value="<?php echo $row['fullName']; ?>">
                                                    <tr class="fw-bold">
                                                        <td class="col-lg-9 col-9"><i class="bi bi-circle-fill px-2" style="color: red;"></i> <?php echo $row['fullName']; ?> Requested for Password Reset</td>
                                                        <td class="col-lg-2 col-1 text-end"><button type="submit" name="click" class="btn btn-link" onclick="showLoader()">Reset Password</button></td>
                                                    </tr>
                                                </form>
                                        <?php } ?>
                                    </tbody>
                                </table>
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

