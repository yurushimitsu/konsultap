<?php
session_start();
require '../config.php';

// Check if the user is logged in and has completed OTP verification
if (isset($_SESSION['IdNumber']) && isset($_SESSION['verified']) && $_SESSION['verified'] === true) {
    $role = $_SESSION['role'];
    $idNumber = $_SESSION['IdNumber'];

    // Ensure only 'medprac' role can access this page
    if ($role === 'medprac') {
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

        // Fetch appointments where is_unread is 1
        $result = $conn->query("SELECT a.*, COALESCE(s.fullName, f.fullName) AS Name
                                FROM appointments AS a 
                                LEFT JOIN students AS s ON a.IdNumber = s.IdNumber
                                LEFT JOIN faculty AS f ON a.IdNumber = f.IdNumber
                                WHERE a.is_unread = 1 AND a.status != 'event'");

        // Handle "See Details" button click
        if (isset($_POST['click'])) {
            $id = $_POST['id'];
            $date = $_POST['date'];
            $time = $_POST['time'];
            $appointment_type = $_POST['type'];
            $reasons = $_POST['reason'];
            $medication = $_POST['medication'];
            $notification_id = $_POST['notification_id']; // Get notification ID

            // Debugging: Check if notification_id is correctly received
            echo "Notification ID: " . htmlspecialchars($notification_id) . "<br>";

            // Update the status of appointments with the same date and time to "denied"
            $update_query = "UPDATE appointments SET status = 'denied', description = 'Appointment for this time is booked' WHERE appointment_date = ? AND appointment_time = ? AND id != ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ssi", $date, $time, $id);
            mysqli_stmt_execute($stmt);

            // Redirect or reload the page
            header("Location: request-approval.php?id=$id&date=$date");
            exit;
        }
    } else {
        // Redirect or display an error message if the role is not 'medprac'
        echo "Error: Invalid role";
        exit;
    }
} else {
    // Redirect to login page if not logged in or OTP verification not completed
    header("Location: ../login.php");
    exit;
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
                            <a href="consultation-list.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-clipboard"></i> <span class="d-none d-md-inline">Consultation List</span>
                            </a>
                        </li>
                        <?php
                            $notifCtr = $conn->query("SELECT * FROM appointments WHERE is_unread = 1 AND status != 'denied'"); // select all notifications
                            $ctr = mysqli_num_rows($notifCtr); // count all the notifications
                            $notifRow = mysqli_fetch_assoc($notifCtr); // fetch all notifications
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
                <nav class="navbar navbar-expand-lg navbar-light bg-transparent border border-bottom">
                    <div class="container-fluid">
                        <a class="navbar-brand d-none d-md-inline" href="#">Notification List</a>
                        <div class="d-flex justify-content-lg-end justify-content-md-end justify-content-center collapse navbar-collapse" id="navbarSupportedContent"></div>
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
                                      <?php while ($row = mysqli_fetch_assoc($result)) {
    if ($row['status'] != 'denied') { ?>
        <form action="" method="post">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($row['appointment_date']); ?>">
            <input type="hidden" name="time" value="<?php echo htmlspecialchars($row['appointment_time']); ?>">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($row['appointment_type']); ?>">
            <input type="hidden" name="reason" value="<?php echo htmlspecialchars($row['reason']); ?>">
            <input type="hidden" name="medication" value="<?php echo htmlspecialchars($row['medication']); ?>">
            <input type="hidden" name="notification_id" value="<?php echo htmlspecialchars($row['id']); ?>"> <!-- Hidden field for notification ID -->
            <tr class="fw-bold">
                <td class="col-lg-9 col-9"><i class="bi bi-circle-fill px-2" style="color: red;"></i><?php echo htmlspecialchars($row['Name']); ?> Booked An Appointment on <?php echo date('F j, Y', strtotime($row['appointment_date'])); ?></td>
                <td class="col-lg-2 col-1 text-end"><button type="submit" name="click" class="btn btn-link">See Details</button></td>
            </tr>
        </form>
    <?php } ?>
<?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="loader" id="loader"></div>
    <script>
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

</body>
</html>
