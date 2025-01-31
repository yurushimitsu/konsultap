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

    // Execute the query
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

        if (password_verify($idNumber, $password)) {
            $warningMessage = "Enter a new password before logging in.";
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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if all fields are set and not empty
    if (isset($_POST['old_password'], $_POST['new_password'], $_POST['confirm_password']) && !empty($_POST['old_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        unset($warningMessage); // Remove warning message

        $oldPassword = $_POST['old_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // Define the db based on the user's role
        if ($role == 'student') {
            $db = 'students';
        } elseif ($role == 'faculty') {
            $db = 'faculty';
        }
        
        // Retrieve the current password from the database
        $query = "SELECT password FROM $db WHERE IdNumber = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $idNumber);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $currentPassword = $row['password'];

            // Verify if the old password matches the current password
            if (password_verify($oldPassword, $currentPassword)) {
                // Check if new password and confirm password match
                if ($newPassword === $confirmPassword) {
                    // Initialize the errors array
                    $errors = [];
                    // Password validation
                    function validatePassword($newPassword) {
                        $errors = [];
                        // Check for minimum length of 12 characters
                        if (strlen($newPassword) < 12) {
                            $errors[] = "Password must be at least 12 characters long.";
                        }
                        // Check for at least one uppercase letter
                        if (!preg_match('/[A-Z]/', $newPassword)) {
                            $errors[] = "Password must contain at least one uppercase letter.";
                        }
                        // Check for at least one lowercase letter
                        if (!preg_match('/[a-z]/', $newPassword)) {
                            $errors[] = "Password must contain at least one lowercase letter.";
                        }
                        // Check for at least one numeric character
                        if (!preg_match('/[0-9]/', $newPassword)) {
                            $errors[] = "Password must contain at least one numeric character.";
                        }
                        // Check for at least one special character
                        if (!preg_match('/[\W_]/', $newPassword)) {
                            $errors[] = "Password must contain at least one special character.";
                        }
                        return $errors; // Return the array of errors
                    }
                    
                    // Validate the new password
                    $passwordValidation = validatePassword($newPassword);
                    // If no errors, proceed
                    if (empty($passwordValidation)) {
                        $hashedPass = password_hash($newPassword, PASSWORD_BCRYPT); // Hashed password
    
                        // Update the password in the database
                        $updateQuery = "UPDATE $db SET password = ? WHERE IdNumber = ?";
                        $updateStmt = mysqli_prepare($conn, $updateQuery);
                        mysqli_stmt_bind_param($updateStmt, "ss", $hashedPass, $idNumber);
                        mysqli_stmt_execute($updateStmt);
    
                        // Check if the password was successfully updated
                        if (mysqli_stmt_affected_rows($updateStmt) > 0) {
                            // Password updated successfully, display success alert
                            $successMessage = "Password has been successfully changed.";
                        } else {
                            // Password update failed, display error alert
                            $error = "Failed to update password. Please try again.";
                        }
                    } else {
                        $errors = array_merge($errors, $passwordValidation);
                    }
                } else {
                    // Handle password mismatch error
                    $error = "New password and confirm password do not match.";
                }
            } else {
                // Handle incorrect old password error
                $error = "Incorrect old password.";
            }
        } else {
            // Handle database error
            $error = "Error retrieving user information.";
        }
    } else {
        // Handle case if any field is missing or empty
        $error = "Please fill in all fields.";
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
        <div class="row justify-content-center">
            <!-- Title -->
            <div class="col-lg-12 text-center py-4 px-5">
                <p class="fs-1 fw-bold" style="color: #000066;">Update Password</p>
            </div>
            <!-- Change password form -->
            <div class="col-lg-6">
                <div class="alert alert-info">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li>At least 12 characters long</li>
                        <li>Contains at least one uppercase letter (A-Z)</li>
                        <li>Contains at least one lowercase letter (a-z)</li>
                        <li>Contains at least one numeric character (0-9)</li>
                        <li>Contains at least one special character (e.g., !@#$%^&*)</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <form action="#" method="post">
                    <!-- Display any errors here -->
                    <?php if (isset($error)) { ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php } ?>
                    <?php if (isset($errors) && !empty($errors)) { ?>
                        <?php foreach ($errors as $error) { ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php } ?>
                    <?php } ?>
                    <?php if (isset($successMessage)) { ?>
                        <div class="alert alert-success"><?php echo $successMessage; ?></div>
                    <?php } ?>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control ps-4" id="old_password" name="old_password" placeholder="Enter Old Password" required>
                        <label for="old_password" class="ps-4">Enter Old Password <i class="text-danger">*</i></label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control ps-4" id="new_password" name="new_password" placeholder="Enter New Password" required>
                        <label for="new_password" class="ps-4">Enter New Password <i class="text-danger">*</i></label>
                    </div>
                    <div class="form-floating mb-3 pb-4">
                        <input type="password" class="form-control ps-4" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                        <label for="confirm_password" class="ps-4">Confirm Password <i class="text-danger">*</i></label>
                    </div>
                    <button type="submit" class="btn text-light fs-4" style="width: 100%; background-color: #000066; border-radius: 25px;"  role="button">Update</button>
                </form>
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