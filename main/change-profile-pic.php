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

    // Handle profile picture upload
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['changeProfile'])) {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = $_FILES['profile_picture']['name'];
            $fileSize = $_FILES['profile_picture']['size'];
            $fileType = $_FILES['profile_picture']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            // Set allowed file extensions
            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');

            // Validate file extension
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Define the new file path
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension; // Create a unique file name
                $uploadFileDir = '../images/';
                $dest_path = $uploadFileDir . $newFileName;

                // Move the uploaded file
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Update the database with the new profile picture
                    $tableName = ($role == 'faculty') ? 'faculty' : 'students';
                    $query = "UPDATE $tableName SET profile_picture = ? WHERE IdNumber = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "ss", $newFileName, $idNumber);

                    if (mysqli_stmt_execute($stmt)) {
                        // Set a success message
                        $_SESSION['success'] = "Profile picture updated successfully.";
                    } else {
                        $_SESSION['error'] = "Error updating profile picture: " . mysqli_error($conn);
                    }
                } else {
                    $_SESSION['error'] = "Error moving the uploaded file.";
                }
            } else {
                $_SESSION['error'] = "Upload failed. Allowed file types: " . implode(", ", $allowedfileExtensions);
            }
        } else {
            $_SESSION['error'] = "No file uploaded or there was an upload error.";
        }
        header("Location: change-profile-pic.php"); // Redirect to the same page to show messages
        exit;
    }
    // Delete profile picture
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteProfile'])) {
        // Set profile picture to default in the database
        $query = "UPDATE students SET profile_picture = NULL WHERE IdNumber = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $idNumber);
        mysqli_stmt_execute($stmt);

        // Redirect to refresh the page with default profile picture
        header("Location: change-profile-pic.php"); 
        exit;
    }

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
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .title {
            font-size: 90px; color: #000066;
        }
        .text-shadow {
            color: white; font-size: 25px; text-shadow: 0px 4px 4px black;
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
                    <span class="position-absolute end-0 border border-light rounded-circle bg-light p-2">
                        <form method="POST">
                            <button type="submit" class="btn btn-link p-0" name="deleteProfile"><i class="bi bi-trash-fill text-dark"></i></button>
                        </form>
                    </span>
                </picture>
            </div>
            <!-- Name -->
            <div class="col-lg-12 col-12 text-center pt-3 px-lg-5 px-4">
                <p class="fs-2 fw-bold" style="color: #000066;"><?php echo $fullName; ?></p>
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8 col-12">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" name="profile_picture" class="form-control" id="profile_picture" accept="image/*" required>
                            </div>
                            <button type="submit" style="background-color: #000066;" class="btn text-light" name="changeProfile">Change Profile Picture</button>
                        </form>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success mt-3">
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger mt-3">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            

            <!-- <div class="col-lg-12 text-center">
               <img src="../images/<?php echo $profilePicture; ?>" width="45px" alt="" class="rounded-circle px-1">
                <h2 class="mt-3"><?php echo $fullName; ?></h2>
            </div>
            <div class="col-lg-12 mt-5">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Upload a new profile picture</label>
                        <input type="file" name="profile_picture" class="form-control" id="profile_picture" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Change Profile Picture</button>
                </form>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success mt-3">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger mt-3">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
            </div> -->
            
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
