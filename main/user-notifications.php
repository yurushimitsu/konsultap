<?php
session_start();
require '../config.php';

// Function to format appointment time
function formatAppointmentTime($start_time) {
    // Assuming each appointment lasts for one hour
    $end_time = date('H:i', strtotime('+1 hour', strtotime($start_time)));
    return date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time));
}

// Check if the user is logged in
if (isset($_SESSION['role'])) {
    // Check if the user is verified
    if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
        header("Location: ../verification.php"); // Redirect to verification page if not verified
        exit;
    }

// Check if the user is logged in
if (isset($_SESSION['role'])) {
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

    // PAGINATION START
    // start value
    $start = 0;
    // number of row to display per page
    $rowPerPage = 5;

    // fetch total of rows
    $appointments = $conn->query("SELECT * FROM appointments a WHERE a.IdNumber = $idNumber");
    $num_rows = mysqli_num_rows($appointments);

    //calculate num of data to display per page
    $pages = ceil($num_rows / $rowPerPage);

    // if user clicks on a page, set new starting point
    if (isset($_GET['page'])) {
        $page = $_GET['page'] - 1;
        $start = $page * $rowPerPage;
    }

    // Query to retrieve notifications for the specific IdNumber with status 'accept' or 'deny'
    $queryAppointments = "SELECT a.*, COALESCE(s.fullName, f.fullName) AS fullName, a.description
    FROM appointments AS a
    LEFT JOIN students AS s ON a.IdNumber = s.IdNumber
    LEFT JOIN faculty AS f ON a.IdNumber = f.IdNumber
    WHERE a.IdNumber = ? AND (a.status = 'accept' OR a.status = 'denied')
    ORDER BY a.created_at DESC
    LIMIT $start, $rowPerPage"; //limit number of rows to display per page

    $stmtAppointments = mysqli_prepare($conn, $queryAppointments);
    mysqli_stmt_bind_param($stmtAppointments, "s", $idNumber);
    mysqli_stmt_execute($stmtAppointments);
    $resultAppointments = mysqli_stmt_get_result($stmtAppointments);

    // for active page
    if (isset($_GET['page'])) {
        $id = $_GET['page'];
    } else {
        $id = 1;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $appointmentId = $_POST['appointmentId'];

        $conn->query("UPDATE appointments
        SET is_unreadusers = 0
        WHERE id = $appointmentId;");
        
        header("refresh: 0");
    }

    
} else {
    // Redirect if the user's role is not set
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
    <title>KonsulTap︱Notifications</title>
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
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="consultations.php">CONSULTATIONS</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="records-appointments.php">RECORDS</a>
        </nav>
    </div>

    <!-- body -->
<!-- body -->
<div class="container py-5">
    <div class="row">
        <!-- Texts -->
       <div class="col-lg-12 text-center pt-1 px-lg-5 pb-3">
            <div class="appointments-box">
                <p class="fs-1 fw-bold m-0 p-1">NOTIFICATIONS</p>
            </div>
        </div>
    </div>
    <div class="row px-lg-5 pb-3">
        <div>
            <a href="user-notifications.php" class="btn text-light" style="background-color: #000066;">ALL</a>
            
           <a href="user-notifications-unread.php" class="btn border border-dark position-relative" style="background-color:#E4E4E4 ; color: black;">
                UNREAD
                <?php if (!empty($ctr)) {?>
                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill counter"><?php echo $ctr?></span>
                <?php } ?>
            </a>
        </div>
    </div>

    <?php if (mysqli_num_rows($resultAppointments) > 0) { ?>

    <div class="row px-lg-5 pb-3">
        <!-- if notif is unread, it will be blue -->
        <?php 
        while($row = mysqli_fetch_assoc($resultAppointments)) { 
            if ($row['is_unreadusers'] == 1) { ?>
                <div class="pb-3">
                    <!-- onclick, notification will be read -->
                    <form action="" method="post">
                        <input type="hidden" name="appointmentId" value="<?php echo $row["id"] ?>">
                        <button type="submit" class="card col-12 text-light fw-bold border-dark" style="background-color: #004AAD;">
                            <div class="card-body d-flex flex-column">
                                <div class="col-9 mb-auto text-start p-lg-0">
                                    Your Consultation for your
                                    <?php echo ($row['reason']); ?> on
                                    <?php echo date('F j, Y', strtotime($row['appointment_date'])); ?> at
                                    <?php echo formatAppointmentTime($row['appointment_time']); ?> is
                                    <?php 
                                        if ($row['status'] == 'accept') {
                                            echo 'accepted';
                                        } else {
                                            echo $row['status'].' because '.$row['description'];
                                        }
                                    ?>
                                </div>
                                <span class="position-absolute top-50 end-0 translate-middle-y pe-5 fw-lighter fst-italic d-none d-md-block">
                                    <?php echo date('F j, Y', strtotime($row['created_at'])); ?>
                                </span>
                                <span class="fw-lighter fst-italic text-end d-md-none pt-2">
                                    <?php echo date('F j, Y', strtotime($row['created_at'])); ?>
                                </span>
                            </div>
                        </button>
                    </form>
                </div>
            <?php }  else { ?>
                <!-- else normal  -->
                <div class="pb-4">
                    <button class="card col-12">
                        <div class="card-body d-flex flex-column">
                            <div class="col-9 mb-auto text-start p-lg-0">
                                Your Consultation for your
                                <?php echo ($row['reason']); ?> on
                                <?php echo date('F j, Y', strtotime($row['appointment_date'])); ?> at
                                <?php echo formatAppointmentTime($row['appointment_time']); ?> is
                                <?php 
                                    if ($row['status'] == 'accept') {
                                        echo 'accepted';
                                    } else {
                                        echo $row['status'].' because '.$row['description'];
                                    }
                                ?>
                            </div>
                            <span class="position-absolute top-50 end-0 translate-middle-y pe-5 fw-lighter fst-italic d-none d-md-block">
                                <?php echo date('F j, Y', strtotime($row['created_at'])); ?>
                            </span>
                            <span class="fw-lighter fst-italic text-end d-md-none pt-2">
                                <?php echo date('F j, Y', strtotime($row['created_at'])); ?>
                            </span>
                        </div>
                    </button>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
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
    <?php } else { ?>
            <div class="text-center" style="font-size: 35px; font-weight: bold;">
                <img src="../images/Pill.png" alt="No Records" style="color:#0b0fe3; width: 100px; height: auto; display: block; margin: 0 auto;">
                No Notification(s)
            </div>
    <?php }?>
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


</div>
</body>
</html>