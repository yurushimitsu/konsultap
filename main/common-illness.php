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
    <title>KonsulTap︱Gentle Treatments</title>
    <link rel="icon" href="../images/logo_icon.png" type="image/icon type">    
</head>
<body>
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
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold active" href="common-illness.php">GENTLE TREATMENTS</a>
            <a class="nav-link text-light" href="appointment.php">SCHEDULE AN <br><span class="fs-3 fw-bolder">APPOINTMENT</span></a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="consultations.php">CONSULTATIONS</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="records-appointments.php">RECORDS</a>
        </nav>
    </div>

    <!-- body -->
    <div class="container pt-5">
        <div class="row">
            <!-- Texts -->
            <div class="col-lg-12 text-center pt-1 px-lg-5">
                    <div class="appointments-box">
                        <p class="fs-1 fw-bold m-0 p-1">COMMON ILLNESSES</p>
                    </div>
                </div>
            <!-- Buttons for common illnesses -->
            <div class="col-lg-12 p-4">
                <div class="row">
                    <div class="col-lg-3 pb-5 d-flex align-items-center justify-content-center ">
                        <a href="common-illness/common-illness-dengue.php" class="card border border-dark position-relative" style="width: 15rem; background-color: #003659; border-radius: 20%;">
                            <div class="position-absolute top-0 start-50 translate-middle-x text-center text-shadow pt-3">Dengue</div>
                            <img src="../images/Mosquito.png" alt="...">
                        </a>
                    </div>
                    <div class="col-lg-3 pb-4 d-flex align-items-center justify-content-center ">
                        <a href="common-illness/common-illness-tonsilitis.php" class="card border border-dark position-relative" style="width: 15rem; background-color: #0B99A7; border-radius: 20%;">
                            <div class="position-absolute top-0 start-50 translate-middle-x text-center text-shadow pt-3">Tonsillitis</div>
                            <img src="../images/Tonsillitis.png" style="width: 240px; height: 250px; object-fit: scale-down;" alt="...">
                        </a>
                    </div>
                    <div class="col-lg-3 pb-4 d-flex align-items-center justify-content-center ">
                        <a href="common-illness/common-illness-hypertension.php" class="card border border-dark position-relative" style="width: 15rem; background-color: #0F1B60; border-radius: 20%;">
                            <div class="position-absolute top-0 start-50 translate-middle-x text-center text-shadow pt-3">Hypertension</div>
                            <img src="../images/Hypertension.png" alt="...">
                        </a>
                    </div>
                    <div class="col-lg-3 pb-4 d-flex align-items-center justify-content-center ">
                        <a href="common-illness/common-illness-flu.php" class="card border border-dark position-relative" style="width: 15rem; background-color: #FCDB6C; border-radius: 20%;">
                            <div class="position-absolute top-0 start-50 translate-middle-x text-center text-shadow pt-3">Flu</div>
                            <img src="../images/Flu.png" style="width: 240px; height: 250px; object-fit: scale-down;" alt="...">
                        </a>
                    </div>

                    <div class="col-lg-3 pb-4 d-flex align-items-center justify-content-center ">
                        <a href="common-illness/common-illness-diarrhea.php" class="card border border-dark position-relative" style="width: 15rem; background-color: #37C4AA; border-radius: 20%;">
                            <div class="position-absolute top-0 start-50 translate-middle-x text-center text-shadow pt-3">Diarrhea</div>
                            <img src="../images/Diarrhea.png" style="width: 240px; height: 250px; object-fit: scale-down;" alt="...">
                        </a>
                    </div>
                    <div class="col-lg-3 pb-4 d-flex align-items-center justify-content-center ">
                        <a href="common-illness/common-illness-cold.php" class="card border border-dark position-relative" style="width: 15rem; background-color: #5499F4; border-radius: 20%;">
                            <div class="position-absolute top-0 start-50 translate-middle-x text-center text-shadow pt-3">Cold</div>
                            <img src="../images/Cold.png" alt="...">
                        </a>
                    </div>
                    <div class="col-lg-3 pb-4 d-flex align-items-center justify-content-center ">
                        <a href="common-illness/common-illness-rashes.php" class="card border border-dark position-relative" style="width: 15rem; background-color: #CCCC00; border-radius: 20%;">
                            <div class="position-absolute top-0 start-50 translate-middle-x text-center text-shadow pt-3">Rashes</div>
                            <img src="../images/Rashes.png" style="width: 240px; height: 250px; object-fit: scale-down;" alt="...">
                        </a>
                    </div>
                    <div class="col-lg-3 pb-4 d-flex align-items-center justify-content-center ">
                        <a href="common-illness/common-illness-sore-eyes.php" class="card border border-dark position-relative" style="width: 15rem; background-color: #3F48CC; border-radius: 20%;">
                            <div class="position-absolute top-0 start-50 translate-middle-x text-center text-shadow pt-3">Sore Eyes</div>
                            <img src="../images/sore-eyes.png" style="width: 240px; height: 250px; padding: 0 40px 0; object-fit: scale-down;" alt="...">
                        </a>
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