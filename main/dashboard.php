<?php
session_start();
require '../config.php';

// Check if the user is logged in and has completed OTP verification
if (isset($_SESSION['role']) && isset($_SESSION['verified']) && $_SESSION['verified'] === true) {
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
    // Redirect to verification page if not verified
    header("Location: ../verification.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
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
        }

        .title {
            font-size: 5vw;
            font-weight: bold;
            color: #000066;
        }

        .text-shadow {
            color: white;
            font-size: 25px;
            text-shadow: 0px 4px 4px black;
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
    <title>KonsulTap︱Home</title>
    <link rel="icon" href="../images/logo_icon.png" type="image/icon type">
</head>
<body>
   <!-- header -->
    <?php 
        include ("include-header.php");
    ?>

    <!-- navbar -->
    <div class="nav navbar-dark container-fluid px-0 navbar-expand-md">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#n_bar" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <nav class="nav-pills nav-justified collapse navbar-collapse" id="n_bar">
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold active" aria-current="page" href="dashboard.php">HOME</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="common-illness.php">GENTLE TREATMENTS</a>
            <a class="nav-link text-light" href="appointment.php">SCHEDULE AN <br><span class="fs-3 fw-bolder">APPOINTMENT</span></a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="consultations.php">CONSULTATIONS</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="records-appointments.php">RECORDS</a>
        </nav>
    </div>

    <!-- body -->
    <div class="container">
        <div class="row">
            <!-- Left Texts -->
            <div class="col-lg-7 pt-5">
                <p class="title">You deserve better healthcare.</p>
                <p class="fs-5"> Welcome to <span class="fw-bold">KonsulTap</span>, where expertise meets convenience!
                    Unleash the power of seamless online consultations, connecting
                    you with trusted professionals at your fingertips. Elevate your
                    insights, empower your decisions – KonsulTap is your gateway
                    to expert advice, effortlessly delivered in the digital realm.
                </p>
            </div>
            <!-- Images on the right -->
            <div class="col-lg-5 pt-4">
                <div class="row">
                    <div class="col-lg-6 pb-3 d-flex align-items-center justify-content-center ">
                        <div class="card border border-dark position-relative" style="width: 12.5rem; background-color: #F2EB85; border-radius: 20%;">
                            <img src="../images/Convenient.png" alt="...">
                            <div class="position-absolute bottom-0 start-50 translate-middle text-center text-shadow">CONVENIENT</div>
                        </div>
                    </div>
                    <div class="col-lg-6 pb-3 d-flex align-items-center justify-content-center ">
                        <div class="card border border-dark position-relative" style="width: 12.5rem; background-color: #F7F978; border-radius: 20%;">
                            <img src="../images/Fast.png" alt="...">
                            <div class="position-absolute bottom-0 start-50 translate-middle text-center text-shadow">FAST</div>
                        </div>
                    </div>
                    <div class="col-lg-6 pb-3 d-flex align-items-center justify-content-center ">
                        <div class="card border border-dark position-relative" style="width: 12.5rem; background-color: #006699; border-radius: 20%;">
                            <img src="../images/Reliable.png" alt="...">
                            <div class="position-absolute bottom-0 start-50 translate-middle text-center text-shadow">RELIABLE</div>
                        </div>
                    </div>
                    <div class="col-lg-6 pb-3 d-flex align-items-center justify-content-center ">
                        <div class="card border border-dark position-relative" style="width: 12.5rem; background-color: #363A89; border-radius: 20%;">
                            <img src="../images/Efficient.png" alt="...">
                            <div class="position-absolute bottom-0 start-50 translate-middle text-center text-shadow">EFFICIENT</div>
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

    </div>
</body>
</html>
