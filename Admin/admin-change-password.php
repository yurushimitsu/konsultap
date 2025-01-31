<?php
session_start();
require '../config.php';

// Check if the user is not logged in or does not have the role 'medprac', redirect to login page or show invalid role message
if (!isset($_SESSION['IdNumber']) || empty($_SESSION['IdNumber']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'itc') {
    echo "Invalid role or not logged in";
    exit;
}

// Check if the user has completed OTP verification
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: ../verification.php");
    exit;
}

    $idNumber = $_SESSION['IdNumber'];

    // Fetch user details from the database based on the IdNumber
    $query = "SELECT fullName, Email, profile_picture FROM itc WHERE IdNumber = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $idNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $fullName = $row['fullName'];
        $email = $row['Email'];
        $profilePicture = $row['profile_picture'];

        // Check if profile picture exists, if not, use default
        if (empty($profilePicture)) {
            $profilePicture = "../images/default_profile_picture.jpg";
        }
    } else {
        // Handle the case if user details are not found
        $fullName = "User Not Found";
        $email = "N/A";
        $profilePicture = "../images/default_profile_picture.jpg";
    }


// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Retrieve the current password from the database
    $query = "SELECT password FROM itc WHERE IdNumber = ?";
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
                    $updateQuery = "UPDATE itc SET password = ? WHERE IdNumber = ?";
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
    
    @media (min-width: 768px) {
        .custom-min-width {
            min-width: 16rem;
        }
    }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-sm-auto sticky-top shadow p-0 custom-min-width" style="background-color: #006699; height:auto; max-height: 100vh; overflow-y: auto;">
                <div class="d-flex flex-sm-column flex-row flex-nowrap align-items-center sticky-top p-0" style="background-color: #006699;">
                    <span class="d-none d-sm-inline col-12 bg-light">
                        <a href="#" class="d-flex align-items-center justify-content-center px-4 py-3 mb-md-0 me-md-auto">
                            <span class="d-md-none"><img src="../images/logo_icon.png" width="35px" alt=""></span>
                            <span class="d-none d-md-inline"><img src="../images/logomain.png" width="150" alt=""></span>
                        </a>
                    </span>
                    <hr class="text-dark d-none d-md-inline mt-0" style="height: 2px; min-width: 100%;">
                    <!-- admin profile -->
                    <div class="row px-3 text-dark">
                        <div class="col-lg-3 d-flex align-items-center justify-content-center py-3">
                            <a href="admin-profile.php">
                                <?php if (isset($fullName)) { ?>
                                    <?php
                                    // Check if profile_picture is set, otherwise use default
                                    if (!empty($profilePicture)) {
                                        echo "<img src='../images/" . $profilePicture . "' style='object-fit: cover;' height='35px' width='35px' alt='' class='rounded-circle'>";
                                    } else {
                                        echo "<img src='../images/default_profile_picture.jpg' style='object-fit: cover;' height='35px' width='35px' alt='' class='rounded-circle'>";
                                    }
                                    ?>
                                <?php } ?>
                            </a>
                        </div>
                        <!-- admin name -->
                        <div class="col-lg-9 text-lg-start text-md-center py-3 d-none d-md-inline" style="color: #FFDE59;">
                            <div class="row">
                            <a href="admin-profile.php" class="text-decoration-none text-dark text-nowrap">
                                    <h5 style="color: white;"><?php echo $fullName; ?></h5>
                                </a>
                            </div>
                            <div class="row">
                                <h5 class="fw-bold">Administrator</h5>
                            </div>
                        </div>
                    </div>
                    <!-- sidebar nav -->
                    <ul class="nav nav-pills nav-flush flex-sm-column flex-row flex-nowrap mb-auto mb-0 px-lg-3">
                        <li class="nav-item">
                            <a href="admincrud.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-list-task"></i> <span class="d-none d-md-inline">Manage User</span>
                            </a>
                        </li>

                        <?php
                            $notifCtr = $conn->query("SELECT * FROM notification_forgotpassword WHERE is_unread = 1");
                            $ctr = mysqli_num_rows($notifCtr); // count all the notification
                            $notifRow = mysqli_fetch_assoc($notifCtr); // fetch all notification
                        ?>
                        <li class="nav-item">
                            <a href="admin-notification.php" class="nav-link py-3 fw-bold fs-5 text">
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
                            <a href="admin-calendar.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="bi bi-calendar"></i> <span class="d-none d-md-inline">Calendar</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="archives.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="bi bi-archive"></i> <span class="d-none d-md-inline">Archives</span>
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
            <div class="col position-relative p-3 min-vh-100">
                <div class="container">
                    <div class="row pt-5">
                        <div class="col-lg-12 text-center">
                            <picture class="position-relative">
                            <img src="../images/<?php echo $profilePicture; ?>" style="object-fit: cover;" height="130px" width="130px" alt="" class="rounded-circle">
                                <span class="position-absolute end-0 border border-light rounded-circle bg-light p-2"><a href="admin-change-profile-pic.php"><i class="bi bi-pencil-fill text-dark"></i></a></span>
                            </picture>
                            <h1><?php echo $fullName; ?></h1>
                            <h3 class="pt-4">Update Password</h3>
                        </div>
                    </div>
                    <div class="row d-flex justify-content-center px-lg-2">
                        <div class="col-lg-6 col-12">
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
                        <div class="col-lg-6 col-12">
                            <!-- change password form -->
                            <form action="" method="post">
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
                                    <label for="new_password" class="ps-4">Enter Old Password <i class="text-danger">*</i></label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control ps-4" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                                    <label for="confirm_password" class="ps-4">Confirm Password <i class="text-danger">*</i></label>
                                </div>
                                <!-- Update button -->
                                <button type="submit" class="btn text-light fs-4" style="width: 100%; background-color: #000066; border-radius: 25px;" role="button">Update</button>
                            </form>
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
