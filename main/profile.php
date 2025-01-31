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

if (isset($_SESSION['IdNumber'])) {
    $idNumber = $_SESSION['IdNumber'];

    // Check the session role to determine the table to query
    $tableName = ($_SESSION['role'] == 'faculty') ? 'faculty' : 'students';

    $query = "SELECT fullName, ContactPerson, Phone FROM $tableName WHERE IdNumber = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $idNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $fullName = $row['fullName'];
            $contactPerson = $row['ContactPerson'];
            $contactPhone = $row['Phone'];
        } else {
            echo "No rows returned by the query.<br>";
        }
    } else {
        echo "Failed to execute the query.<br>";
    }
} else {
    echo "IdNumber session variable is not set.";
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
            font-size: 90px;
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
    <title>KonsulTap︱Profile</title>
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
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="common-illness.php">GENTLE TREATMENTS</a>
            <a class="nav-link text-light" href="appointment.php">SCHEDULE AN <br><span class="fs-3 fw-bolder">APPOINTMENT</span></a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="consultations.php">CONSULTATIONS</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="records-appointments.php">RECORDS</a>
        </nav>
    </div>

    <!-- body -->
    <div class="container py-5">
        <div class="row">
            <!-- Profile picture -->
            <div class="col-lg-12 px-5 text-center">
                <picture class="position-relative justify-content-center align-items-center">
                    <img src="../images/<?php echo $profilePicture; ?>" style="object-fit: cover;" width="135px" height="135px" alt="" class="rounded-circle">
                    <span class="position-absolute end-0 border border-light rounded-circle bg-light p-2"><a href="change-profile-pic.php"><i class="bi bi-pencil-fill text-dark"></i></a></span>
                </picture>
            </div>
            <!-- Name -->
            <div class="col-lg-12 text-center pt-3 px-5">
                <p class="fs-2 fw-bold" style="color: #000066;"><?php echo $fullName; ?></p>
            </div>
            <!-- Left Table -->
            <div class="col-lg-6 col-sm-12 pt-5">
                <p class="fs-5">Basic Information</p>
                <div class="table-responsive">
                    <table class="table table-bordered border-dark table-light lh-lg">
                        <tbody class="col-sm-12">
                          <tr>
                            <td class="" style="border-right: 0px;">Fullname</td>
                            <td class=" fw-bold" style="border-left: 0px;"><?php echo $fullName; ?></td>
                          </tr>
                          <tr>
                            <td class="" style="border-right: 0px;">Email</td>
                            <td class=" fw-bold" style="border-left: 0px;"><?php echo $email; ?></td>
                          </tr>
                          <tr>
                            <td class="" style="border-right: 0px;">Password</td>
                            <td class="" style="border-left: 0px;"><a href="change-password.php" class="text-dark">Change Password</a></td>
                          </tr>
                        </tbody>
                    </table>
                </div>
                
            </div>
            <!-- Right Table -->
            <div class="col-lg-6 col-sm-12 pt-5">
                <p class="fs-5">Contact Information</p>
                <table class="table table-bordered border-dark table-light lh-lg">
                    <tbody>
                      <tr>
                        <td class="col-lg-3" style="border-right: 0px;">Contact Number</td>

                        <?php if (!empty($contactPhone)) {?>
                            <td class="col-lg-9" style="border-left: 0px;"><a href="add-contact.php" class="text-dark"><?php echo $contactPhone ?><span class="text-decoration-none"> <i class="bi bi-pencil-fill"></i></span></a></td>
                        <?php } else { ?>
                            <td class="col-lg-9" style="border-left: 0px;"><a href="add-contact.php" class="text-dark">Add Contact Number</a></td>
                        <?php }?>
                      </tr>
                    </tbody>
                </table>
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