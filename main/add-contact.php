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
        $query = "SELECT fullName, Email, profile_picture, ContactPerson, Phone FROM students WHERE IdNumber = ?";
    } elseif ($role == 'faculty') {
        $query = "SELECT fullName, Email, profile_picture, ContactPerson, Phone FROM faculty WHERE IdNumber = ?";
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
        $contactPerson = $row['ContactPerson'];
        $contactPhone = $row['Phone'];

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

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    if (isset($_POST['contact_person']) && isset($_POST['contact_number'])) {
        $inputContactPerson = $_POST['contact_person'];
        $inputContactNumber = $_POST['contact_number'];

        // Define the db based on the user's role
        if ($role == 'student') {
            $db = 'students';
        } elseif ($role == 'faculty') {
            $db = 'faculty';
        }
        
        // Retrieve the current password from the database
        $query = "UPDATE $db SET Phone = ?, ContactPerson = ? WHERE idNumber = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sss", $inputContactNumber, $inputContactPerson, $idNumber);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

         // Display success message and redirect
         echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
         <script> 
         $(this).ready(function () {
             Swal.fire({
                 icon: "success",
                 title: "Successfully Updated Contact Number",
                 showCloseButton: true,
                 showConfirmButton: false
             })
         })  

         setTimeout(function name(params) {
             document.location = "add-contact.php";
         }, 2000);
         </script>';
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
        <div class="row justify-content-center">
            <!-- Title -->
            <div class="col-lg-12 text-center py-3 px-5">
                <p class="fs-1 fw-bold" style="color: #000066;">Update Contact Number</p>
            </div>
            <!-- Change password form -->
            <div class="col-lg-4">
                <form action="#" method="post">
                    <div class="mb-3 pb-4">
                        <label for="#contact_person">Contact Person <i class="text-danger">*</i></label>
                        <input type="text" class="form-control" placeholder="Contact Person" id="contact_person" name="contact_person" aria-describedby="emailHelp" value="<?php echo $contactPerson; ?>" required>
                    </div>
                    <div class="mb-3 pb-4">
                        <label for="#contact_number">Contact Number <i class="text-danger">*</i></label>
                        <input type="text" class="form-control" placeholder="Contact Number" id="contact_number" name="contact_number" value="<?php echo $contactPhone; ?>" pattern="[09][0-9]{10}" required>
                    </div>
                    <button type="submit" class="btn text-light fs-4" style="width: 100%; background-color: #000066; border-radius: 25px;"  role="button">Update</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>