<?php
session_start();
require '../config.php';

// Check if the user is logged in
if (isset($_SESSION['role'])) {
    // Check if the user is verified
    if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
        header("Location: ../verification.php"); // Redirect to verification page if not verified
        exit;
    }

    $role = $_SESSION['role'];
    $idNumber = $_SESSION['IdNumber'];

    // Define the query based on the user's role
    if ($role == 'student') {
        $query = "SELECT fullName, Email, profile_picture, Password FROM students WHERE IdNumber = ?";
    } elseif ($role == 'faculty') {
        $query = "SELECT fullName, Email, profile_picture, Password FROM faculty WHERE IdNumber = ?";
    } else {
        // Handle other roles if necessary
        echo "Error: Invalid role";
        exit;
    }

    // Prepare the statement
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Handle error if the statement preparation fails
        die("Error: " . mysqli_error($conn));
    }

    // Bind parameters
    mysqli_stmt_bind_param($stmt, "s", $idNumber);

    // Execute the query
    mysqli_stmt_execute($stmt);

    // Get the result
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
            header("Location: change-password.php");
            exit;
        } 
    } else {
        // Handle the case if user details are not found
        $fullName = "User Not Found";
        $email = "N/A";
        $profilePicture = "../images/default_profile_picture.jpg";
    }
} else {
    // Redirect if the user's role is not set
    header("Location: ../index.php");
    exit;
}

// Get the current year and month
$currentYear = date('Y');
$currentMonth = date('m');

// PAGINATION START
// start value
$start = 0;
// number of rows to display per page
$rowPerPage = 5;

// fetch total rows for pagination
$totalAppointmentsQuery = "SELECT COUNT(*) as total FROM appointments a 
WHERE a.status = 'accept' 
AND a.IdNumber = ? 
AND YEAR(a.appointment_date) = ? 
AND MONTH(a.appointment_date) = ? 
AND a.consult_done = 0";
$stmtTotalAppointments = mysqli_prepare($conn, $totalAppointmentsQuery);
mysqli_stmt_bind_param($stmtTotalAppointments, "sii", $idNumber, $currentYear, $currentMonth);
mysqli_stmt_execute($stmtTotalAppointments);
$resultTotalAppointments = mysqli_stmt_get_result($stmtTotalAppointments);
$totalRow = mysqli_fetch_assoc($resultTotalAppointments)['total'];

//calculate number of pages to display
$pages = ceil($totalRow / $rowPerPage);

// if user clicks on a page, set new starting point
if (isset($_GET['page'])) {
    $page = $_GET['page'] - 1;
    $start = $page * $rowPerPage;
}

// Updated query to fetch appointments within the current month, excluding those where consult_done = 1
$queryAppointments = "SELECT a.id, a.appointment_date, a.appointment_time, a.appointment_type, a.doctor, a.url, a.created_at
FROM appointments a
WHERE a.status = 'accept' 
AND a.IdNumber = ? 
AND YEAR(a.appointment_date) = ? 
AND MONTH(a.appointment_date) = ? 
AND a.consult_done = 0
ORDER BY a.appointment_date ASC, a.appointment_time ASC
LIMIT ?, ?"; 

$stmtAppointments = mysqli_prepare($conn, $queryAppointments);
mysqli_stmt_bind_param($stmtAppointments, "siiii", $idNumber, $currentYear, $currentMonth, $start, $rowPerPage);
mysqli_stmt_execute($stmtAppointments);
$resultAppointments = mysqli_stmt_get_result($stmtAppointments);

// for active page
if (isset($_GET['page'])) {
    $id = $_GET['page'];
} else {
    $id = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- sweetalert -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- timeout -->
    <script src="../timeout.js"></script> 

    <style>
        body {
            background-image:url('../images/bg-wallpaper.png'); 
            background-repeat: no-repeat; 
            background-size: cover; 
            min-height: 100vh;
            height: 100%; 
            width: 100%;
        }

        .nav {
            background-color: #000066;
        }

        .nav-link:hover {
            color: black !important;
            background: #0B99A7 !important;
        }

        .active { 
            background: #0B99A7 !important;
            color: white !important;
        }

        .page-link {
            color: #000066;
        }

        .title {
            font-size: 90px;
            color: #000066;
        }

        .text-shadow {
            color: white;
            font-size: 25px;
            text-shadow: 0px 4px 4px black;
        }

        .card:hover {
            transform: scale(1.1);
        }
        
        .card {
            transition: transform 0.2s;
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

    .appointments-box {
    background-color: rgba(0, 102, 153, .8); /* Lower opacity */
    border: 2px solid #000066; /* Border color */
    border-radius: 10px; /* Rounded corners */
    padding: 1px; /* Space inside the box */
    margin: 5px 0; /* Space around the box */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    color: #ffffff;
}
    </style>
    <title>KonsulTap︱Consultations</title>
    <link rel="icon" href="../images/logo_icon.png" type="image/icon type">
</head>
<body id="<?php echo $id ?>">
    <!-- header -->
    <?php 
        include ("include-header.php")
    ?>

    <!-- navbar -->
    <div class="nav navbar-dark container-fluid px-0 navbar-expand-md">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#n_bar" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <nav class="nav-pills nav-justified collapse navbar-collapse" id="n_bar">
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" aria-current="page" href="dashboard.php">HOME</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="common-illness.php">GENTLE TREATMENTS</a>
            <a class="nav-link text-light" href="appointment.php">SCHEDULE AN <br><span class="fs-3 fw-bolder">APPOINTMENT</span></a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold active" href="consultations.php">CONSULTATIONS</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="records-appointments.php">RECORDS</a>
        </nav>
    </div>

    <!-- body -->
    <div class="container py-5">
        <div class="row pb-5">
            <!-- Texts -->
            <div class="col-lg-12 text-center pt-1 px-lg-5">
                    <div class="appointments-box">
                        <p class="fs-1 fw-bold m-0 p-1">UPCOMING APPOINTMENTS</p>
                    </div>
                </div>
        </div>

        <div class="row pb-5">
            <!-- Texts -->
            <div class="col-lg-12 table-responsive">
                <div class="accordion" id="accordionExample">
                    <?php
                    if (mysqli_num_rows($resultAppointments) > 0) {
                        while ($row = mysqli_fetch_assoc($resultAppointments)) {
                            $appointmentID = $row['id'];
                            $appointmentDate = $row['appointment_date'];
                            $appointmentTime = $row['appointment_time'];
                            $appointmentType = $row['appointment_type'];
                            $doctorName = $row['doctor'];
                            $url = $row['url'];
                            $createdAt = $row['created_at'];

                        // Function to format appointment time
                            // Check if the function exists before declaring it
                            if (!function_exists('formatAppointmentTime')) {
                                // Function to format appointment time
                                function formatAppointmentTime($start_time) {
                                    // Assuming each appointment lasts for one hour
                                    $end_time = date('H:i', strtotime('+1 hour', strtotime($start_time)));
                                    return date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time));
                                }
                            }
                            // Format date
                            $formattedDate = date('F j, Y', strtotime($appointmentDate));
                        ?>
                        <!-- accordion -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#appointmentID<?php echo $appointmentID?>" aria-expanded="false" aria-controls="collapseTwo" data-bs-toggle="tooltip" title="Click to view appointment details">
                                <span class="fs-4 fw-bold" style="color: #000066;">
                                <?php echo ucfirst($appointmentType); ?> consultation with Dr. <?php echo $doctorName; ?> on  <?php echo $formattedDate.', '.formatAppointmentTime($row['appointment_time']); ?>
                                </span>
                            </button>
                            </h2>
                            <div id="appointmentID<?php echo $appointmentID?>" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                <div class="accordion-body ">
                                    <!-- information body -->
                                    <div class="text-center">
                                        <span class="fs-5 fw-bold text-center" style="color: #000066;">Your Appointment Request is approve! Kindly See Below for the Appointment Details</span>
                                    </div><br>
                                    <div class="px-lg-5">
                                        <span class="fs-5 fw-bold" style="color: #006699;">Consultation With: <span style="color: #000066;">Dr. <?php echo $doctorName; ?></span> </span><br>
                                        <span class="fs-5 fw-bold" style="color: #006699;">Date: <span style="color: #000066;"><?php echo $formattedDate; ?></span> </span><br>
                                        <span class="fs-5 fw-bold" style="color: #006699;">Time: <span style="color: #000066;"><?php echo formatAppointmentTime($row['appointment_time']); ?></span> </span><br>
                                        <span class="fs-5 fw-bold" style="color: #006699;">Mode: <span style="color: #000066;"><?php echo $appointmentType; ?></span> </span><br>
                                        <?php if (!empty($url)) { ?>
                                        <span class="fs-5 fw-bold" style="color: #006699;">Url Link: <a href="<?php echo $url; ?>" target="_blank">Conference Room</a></span> <br><br>
                                        <?php } ?>
                                        <span class="fw-lighter fst-italic"><span><?php echo date('F j, Y', strtotime($createdAt)); ?></span> </span>
                                    
                                    </div>
                                </div>
                            </div>
                        </div>                            
                    <?php
                        }
                    } else {?>
                        <div class="text-center" style="font-size: 35px; font-weight: bold;">
                            <img src="../images/Pill.png" alt="No Records" style="color:#0b0fe3; width: 100px; height: auto; display: block; margin: 0 auto;">
                            No Available Consultations
                        </div>
                    <?php }
                    ?>
                </div>
            </div>
        </div>
        <?php if (mysqli_num_rows($resultAppointments) > 0) { ?>
            <div class="row pb-5">
                <div class="col-12 text-center">
                    <div class="pb-3">
                        <?php
                        if (!isset($_GET['page'])) {
                            $page = 1;
                        } else {
                            $page = (int)$_GET['page'];
                        }
                        ?>
                        Showing <?php echo $page ?> of <?php echo $pages ?> pages
                    </div>
    
                    <nav aria-label="Page navigation example">
                        <ul class="pagination justify-content-center">
                            <li class="page-item"><a class="page-link" href="?page=1">First</a></li>
                            <li class="page-item d-none d-md-block">
                                <?php if ($page > 1) { ?>
                                    <a class="page-link" href="?page=<?php echo $page - 1 ?>"><span aria-hidden="true">&laquo;</span></a>
                                <?php } else { ?>
                                    <span class="page-link" aria-hidden="true">&laquo;</span>
                                <?php } ?>
                            </li>
    
                            <?php
                            // Calculate the start and end pages to display
                            $start_page = max(1, $page - 2); // Show up to 2 pages before
                            $end_page = min($pages, $start_page + 4); // Ensure 5 pages in total
    
                            // Adjust start page if we're at the end
                            if ($end_page - $start_page < 4) {
                                $start_page = max(1, $end_page - 4); // Adjust start to ensure 5 pages are shown
                            }
    
                            // Display the page numbers
                            for ($counter = $start_page; $counter <= $end_page; $counter++) { ?>
                                <li class="page-item">
                                    <a class="page-link <?php echo $counter == $page ? 'active' : ''; ?>" href="?page=<?php echo $counter; ?>"><?php echo $counter; ?></a>
                                </li>
                            <?php } ?>
    
                            <li class="page-item d-none d-md-block">
                                <?php if ($page < $pages) { ?>
                                    <a class="page-link" href="?page=<?php echo $page + 1 ?>"><span aria-hidden="true">&raquo;</span></a>
                                <?php } else { ?>
                                    <span class="page-link" aria-hidden="true">&raquo;</span>
                                <?php } ?>
                            </li>
                            <li class="page-item"><a class="page-link" href="?page=<?php echo $pages; ?>">Last</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php } ?>
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
   <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>

    </div>
</body>
</html>