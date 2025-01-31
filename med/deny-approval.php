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

// Retrieve appointment details
$idNumber = mysqli_real_escape_string($conn, $_GET['IdNumber']);
$appointmentDate = mysqli_real_escape_string($conn, $_GET['appointment_date']);
$appointment_id = $_GET['id'];



$query = "SELECT a.id, a.appointment_date, a.created_at, COALESCE(s.fullName, f.fullName) AS fullName
          FROM appointments AS a
          LEFT JOIN students AS s ON a.IdNumber = s.IdNumber
          LEFT JOIN faculty AS f ON a.IdNumber = f.IdNumber
          WHERE a.IdNumber = '$idNumber' AND a.appointment_date = '$appointmentDate'";

$result = mysqli_query($conn, $query);


// Handle query execution error
if (!$result) {
    echo "Query Error: " . mysqli_error($conn);
} elseif (mysqli_num_rows($result) == 0) {
    echo "Appointment not found.";
} else {
    // Fetch appointment details
    $row = mysqli_fetch_assoc($result);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve description and appointment ID from form
        $description = mysqli_real_escape_string($conn, $_POST['description']);

        // Update query
        $updateQuery = "UPDATE appointments SET description = '$description', consult_done = 1, is_unread = 0 WHERE id = '$appointment_id'";

        if (mysqli_query($conn, $updateQuery)) {
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "success",
                    title: "Reason sent.",
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  

            setTimeout(function name(params) {
                document.location = "notifications.php";
            }, 3000);
            </script>';
        //   header("Location: notifications.php");
        //   exit();
        } else {
            echo "Error updating description: " . mysqli_error($conn);
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
                        <h1 class="col-lg-6 col-md-12">Consultation Information</h1>
                    </div>
                    <div class="col-12 px-lg-5 px-3 pb-5">
                        <div class="col-12 border border-2 border-dark rounded shadow-bottom shadow">
                            <!-- navbar for card -->
                            <nav class="nav navbar navbar-expand-lg border-bottom border-2" style="background-color: #000066;">
                                <div class="container-fluid">
                                    <div class="navbar" id="navbarNavAltMarkup">
                                        <img src="images/patient-profile.png" width="55px" alt="" class="rounded-circle">
                                        <h3 class="text-white ps-3"><?php echo $row['fullName']; ?></h3>
                                    </div>
                                </div>
                            </nav>
                            <!-- card information -->
                            <div class="row p-lg-5 px-3">
                                <!-- information -->
                                <div class="pb-lg-4">
                                <p><span class="fw-bold">Consultation No:</span> <?php echo $appointment_id; ?>
                                    <p><span class="fw-bold">Date of application of consultation: </span><?php echo date('F j, Y', strtotime($row['created_at'])); ?>
                                    <p><span class="fw-bold">Consultation Date: </span><?php echo date('F j, Y', strtotime($row['appointment_date'])); ?>
                                   

                                    <!-- description -->
                                    <div class="pb-4">
                                        <nav class="nav navbar navbar-expand-lg border-bottom border-2" style="background-color: #000066;">
                                            <div class="container-fluid">
                                                <div class="navbar" id="navbarNavAltMarkup">
                                                    <h4 class="text-white ps-3">Reason For Denial</h4>
                                                </div>
                                            </div>
                                        </nav>
                                        <!-- textarea input for description -->
                                        <div class="col-12 border border-2 border-dark rounded shadow-bottom shadow">
                                        <form action="" method="post">
                                            <input type="hidden" name="appointment_id" value="<?php echo ['id']; ?>">
                                            <textarea class="col-12" name="description" id="description" placeholder="What is the reason for denying the appointment request?" rows="5"></textarea>
                                            <div class="text-lg-end text-center p-3">
                                                <!-- submit button -->
                                                <button type="submit" class="button border-0 text-white px-5 py-1" style="background-color: #000066; border-radius: 25px;">SUBMIT</button>
                                            </div>
                                        </form>
                                        </div>
                                    </div>
                                    <div class="text-lg-end text-center p-3">
                                        <!-- mark as done button -->
                                        <form action="" method="post">
                                        <button type="submit" name="action" value="mark_done" class="text-decoration-none button border-0 text-white px-5 py-1" style="background-color: #000066; border-radius: 25px;">MARK AS DONE</button>
                                    </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


    </body>

    </html>