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

    $idNumber = $_SESSION['IdNumber'];

    // Fetch user details from the database based on the IdNumber
    $query = "SELECT fullName, Email, profile_picture FROM medprac WHERE IdNumber = ?";
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

// Retrieve the id parameter from the URL
$id = mysqli_real_escape_string($conn, $_GET['id']);
$date = mysqli_real_escape_string($conn, $_GET['date']);

$query = "SELECT a.*, COALESCE(s.IdNumber, f.IdNumber) AS IdNumber, COALESCE(s.fullName, f.fullName) AS userFullName, a.appointment_type
        FROM appointments AS a
        LEFT JOIN students AS s ON a.IdNumber = s.IdNumber
        LEFT JOIN faculty AS f ON a.IdNumber = f.IdNumber
        WHERE a.id = '$id' AND a.appointment_date = '$date'";


$result = mysqli_query($conn, $query);
$queryResults = mysqli_num_rows($result);

if ($queryResults > 0) { 
    while ($row = mysqli_fetch_assoc($result)) {
        $userFullName = $row['userFullName'];
        $user_IdNumber = $row['IdNumber'];
        $notificationId = $row['id']; // Assuming there's a field 'notification_id' in the appointments table
        $appointmentType = $row['appointment_type']; // Fetch the appointment type

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['action']) && $_POST['action'] == 'accept') {
                // Handle accepting appointment
                $updateAppointmentQuery = "UPDATE appointments SET status = 'accept', doctor = '$fullName' WHERE id = $id AND appointment_date = '$date'";
                $updateNotificationQuery = "UPDATE appointments SET is_unread = 0, is_unreadusers = 1 WHERE id = $notificationId";
                
                if (mysqli_query($conn, $updateAppointmentQuery) && mysqli_query($conn, $updateNotificationQuery)) {
                    echo "success";
                } else {
                    echo "error";
                }
                exit;
            } elseif (isset($_POST['action']) && $_POST['action'] == 'deny') {
                // Handle denying appointment
                $updateAppointmentQuery = "UPDATE appointments SET status = 'denied', doctor = '$fullName' WHERE id = $id AND appointment_date = '$date'";
                $updateNotificationQuery = "UPDATE appointments SET is_unread = 0, is_unreadusers = 1 WHERE id = $notificationId";
                
                if (mysqli_query($conn, $updateAppointmentQuery) && mysqli_query($conn, $updateNotificationQuery)) {
                    echo "success";
                } else {
                    echo "error";
                }
                exit;
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
    
    <title>KonsulTap</title>
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
                            <a href="notifications.php" class="nav-link py-3 fw-bold fs-5 text active">
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
            <div class="container">
                <div class="row pt-5 pb-5">
                    <div class="col-lg-12 text-center pt-lg-5 pb-3">
                        <div class="col-12 border border-2 border-dark rounded shadow-bottom shadow">
                            <!-- navbar for card -->
                            <nav class="nav navbar navbar-expand-lg border-bottom border-2" style="background-color: #000066; min-height: 5rem;"></nav>
                            <!-- card information -->
                            <div class="row px-5 pb-3 pt-5" >
                                <h1> <?php echo $userFullName; ?> Booked a Consultation</h1>
                            </div>


                            <div class="row px-5 pb-3">
                                 <h3>Appointment Type:</h3>
                                    <p><?php echo $appointmentType; ?></p> <!-- Display the appointment type -->
                            </div>
                            <div class="row px-5 pb-3">
                                <h3>Reason:</h3>
                                <p> <?php echo $row['reason']; ?></p>
                            </div>
                            <div class="row px-5 pb-3">
                                <h3>Medication History:</h3>
                                <p><?php echo $row['medication']; ?></p>
                            </div>
                            <?php } ?>
                            <?php } ?>
                            <div class="row px-5 pb-3">
                                <h2></h2>
                            </div>
                            <div class="row justify-content-center px-lg-5 pb-5">
                                <!-- accept button -->
                                <a class="text-decoration-none button border-0 text-dark fs-4 fw-bold py-2 col-lg-2 col-4" name="click" style="background-color: #B6E9C1; border-radius: 10px;" href="javascript:accept();">ACCEPT</a>
                                <!-- separator -->
                                <div class="col-1"></div>
                                <!-- deny button -->
                                <a class="text-decoration-none button border-0 text-dark fs-4 fw-bold py-2 col-lg-2 col-4" name="deny" style="background-color: #E45B5B; border-radius: 10px;" href="javascript:deny();">DENY</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 

</body>
</html>


<script type="text/javascript">
function accept() {
    $.ajax({
        url: '',
        type: 'POST',
        data: { 
            id: <?php echo $_GET['id']; ?>,
            date: <?php echo $_GET['date']; ?>,
            action: 'accept'
        },
        success: function(response) {
            Swal.fire({
                icon: "success",
                title: "Successfully Accepted Request",
                showCloseButton: true,
                showConfirmButton: false
            });
            setTimeout(function() {
                window.location.href = "consultation-information.php?id=<?php echo $id; ?>&IdNumber=<?php echo $user_IdNumber; ?>&appointment_date=<?php echo $date; ?>&status=accept";
            }, 2000);
        },
        error: function() {
            Swal.fire({
                icon: "error",
                title: "Failed to Accept Request",
                showCloseButton: true,
                showConfirmButton: false
            });
        }
    });
}

function deny() {
    $.ajax({
        url: '',
        type: 'POST',
        data: { 
            id: <?php echo $_GET['id']; ?>,
            date: <?php echo $_GET['date']; ?>,
            action: 'deny'
        },
        success: function(response) {
            Swal.fire({
                icon: "error",
                title: "Denied Request",
                showCloseButton: true,
                showConfirmButton: false
            });
            setTimeout(function() {
                window.location.href = "deny-approval.php?id=<?php echo $id; ?>&IdNumber=<?php echo $user_IdNumber; ?>&appointment_date=<?php echo $date; ?>&status=deny";
            }, 2000);
        },
        error: function() {
            Swal.fire({
                icon: "error",
                title: "Failed to Deny Request",
                showCloseButton: true,
                showConfirmButton: false
            });
        }
    });
}
</script>