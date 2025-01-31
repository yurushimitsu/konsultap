<?php
session_start();
require '../../config.php';

// Check if the user is logged in
if (isset($_SESSION['role'])) {
    // Check if the user is verified
    if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
        header("Location: ../../verification.php"); // Redirect to verification page if not verified
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
            $profilePicture = "../../images/default_profile_picture.jpg";
        }
        // Check if user changed their password from default password
        if (password_verify($idNumber, $password)) {
            header("Location: ../change-password.php");
            exit;
        } 
    } else {
        // Handle the case if user details are not found
        $fullName = "User Not Found";
        $email = "N/A";
        $profilePicture = "../../images/default_profile_picture.jpg";
    }
} else {
    // Redirect if the user's role is not set
    header("Location: ../../index.php");
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
    <script src="timeout.js"></script> 
    
    <style>
        body {
            background-image: url('../../images/cold-bg.png'); height: 100vh; width: 100%;
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
    <title>KonsulTap</title>
    <link rel="icon" href="../../images/logo_icon.png" type="image/icon type">
</head>
<body>
    <!-- header -->
    <?php 
        include ("include-header-common-ill.php")
    ?>

    <!-- navbar -->
    <div class="nav navbar-dark container-fluid px-0 navbar-expand-md">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#n_bar" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
       <nav class="nav-pills nav-justified collapse navbar-collapse" id="n_bar">
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" aria-current="page" href="../dashboard.php">HOME</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold active" href="../common-illness.php">GENTLE TREATMENTS</a>
            <a class="nav-link text-light" href="../appointment.php">SCHEDULE AN <br><span class="fs-3 fw-bolder">APPOINTMENT</span></a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="../consultations.php">CONSULTATIONS</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="../records-appointments.php">RECORDS</a>
        </nav>
    </div>

    <!-- body -->
    <div class="container py-5">
        <div class="row justify-content-center text-center px-4">
            <p class="title fs-1 fw-bold" style="color: #000066;">Dengue</p>
            <!-- Button trigger modal for FAQs -->
            <button type="button" class="btn btn-light border-dark shadow col-lg-4" data-bs-toggle="modal" data-bs-target="#faqsModal" style="border-radius: 25px;">
                <div class="row">
                    <i class="bi bi-chat-dots-fill" style="font-size: 125px; color: #006699;"></i>
                    <h2 class="text-center" style="color: #006699;">Frequently Asked <br> Questions</h2>
                </div>
            </button>

            <!-- Modal For FAQs -->
            <div class="modal fade" id="faqsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">FAQs About Dengue</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <img src="../../images/FAQS/dengue.png" class="img-fluid">
                        </div>
                        <div class="modal-footer d-flex justify-content-between">
                            <span class="text-start">Source: <a href="https://my.clevelandclinic.org/health/diseases/17753-dengue-fever" target="_blank">Dengue Fever</a></span> 
                            <button type="button" class="btn text-light" style="background-color: #76a6e5;" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- spacer in the middle -->
            <div class="col-2 py-4"></div>

            <!-- Button trigger modal for Home Remedys -->
            <button type="button" class="btn btn-light border-dark shadow col-lg-4" data-bs-toggle="modal" data-bs-target="#homeRemedyModal" style="border-radius: 25px;">
                <div class="row">
                    <i class="bi bi-capsule" style="font-size: 125px; color: #006699;"></i>
                    <h2 class="text-center" style="color: #006699;">Home Remedy</h2>
                </div>
            </button>

            <!-- Modal For Home Remedys -->
            <div class="modal fade" id="homeRemedyModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Home Remedy For Dengue</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <img src="../../images/homeremedy/dengue.png" class="img-fluid">
                        </div>
                        <div class="modal-footer d-flex justify-content-between">
                            <span class="text-start">Source: <a href="https://www.truemeds.in/blog/home-remedies-for-dengue" target="_blank">10 Effective Home Remedies for Dengue</a></span> 
                            <button type="button" class="btn text-light" style="background-color: #76a6e5;" data-bs-dismiss="modal">Close</button>
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