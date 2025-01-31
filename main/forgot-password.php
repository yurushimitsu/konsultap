<?php
session_start();
// Redirect to a different page if the user is already logged in
if (isset($_SESSION['role']) && isset($_SESSION['IdNumber'])) {
    // Check if the user is verified
    if (isset($_SESSION['verified']) && $_SESSION['verified'] === true) {
        // Redirect to dashboard based on user role
        switch ($_SESSION['role']) {
            case 'student':
            case 'faculty':
                header('Location: ../main/dashboard.php');
                break;
            case 'itc':
                header('Location: ../Admin/admincrud.php');
                break;
            case 'medprac':
                header('Location: ../med/dashboard.php');
                break;
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include your database connection file
    include('../config.php');

    // Get the input ID from the form
    $idNumber = $_POST['studID'];

    // Initialize variables
    $name = "";
    $is_unread = 1;  // Default value for is_unread

    // Query each table for the ID
    $tables = ['students', 'faculty', 'medprac'];
    foreach ($tables as $table) {
        $sql = "SELECT fullName FROM $table WHERE IdNumber = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $idNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // If the ID is found, get the full name
            $row = $result->fetch_assoc();
            $name = $row['fullName'];
            break;  // Exit the loop once a match is found
        }
    }

    if ($name) {
        // Select all pending request from user
        $check_sql = "SELECT idNumber, is_unread FROM notification_forgotpassword WHERE IdNumber = ? AND is_unread = 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $idNumber);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        // If no pending request, continue, else notify there is still a pending request
        if ($check_result->num_rows == 0) {
            // Insert into the notification_forgotpassword table
            $insert_sql = "INSERT INTO notification_forgotpassword (IdNumber, fullName, is_unread) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssi", $idNumber, $name, $is_unread);
            
            if ($insert_stmt->execute()) {
                echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
                    <script> 
                    $(this).ready(function () {
                        Swal.fire({
                            icon: "success",
                            title: "Password reset request sent.",
                            text: "You have successfully notified the admin to change your password! Please wait for a few moments while we process your request.",
                            showCloseButton: true,
                            showConfirmButton: false
                        });
                    })  
            
                   setTimeout(function() {
                        document.location = "../index.php";
                    }, 5000);
                    </script>';
            } else {
                echo "<script>alert('Error: " . addslashes($conn->error) . "');</script>";
            }
        } else {
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "warning",
                    title: "You Have a Pending Request",
                    text: "It seems that you still have a pending request. Kindly wait for the admin to reset your password. If you need assistance or have any issues, please contact the ITC Department.",
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
            </script>';
        }
    } else {
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "warning",
                    title: "ID Number not found in any records",
                    timer: 5000,
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  
            </script>';
    }

    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- sweetalert -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        body {
            background-image:url('../images/bg-wallpaper.png'); 
            background-repeat: no-repeat; 
            background-size: cover; 
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
        .login {
            border-radius: 5px 5px 5px 5px;
            padding: 10px 20px 20px 20px;
            width: 150%;
            max-width: 500px;
            background: #FFFFFF;
            position: relative;
            box-shadow: 0px 1px 5px rgba(0, 0, 0, 0.3);
            }
        
            .login button {
    width: 100%;
    height: 100%;
    padding: 10px 10px;
    background: #000066;
    color: #fff;
    display: block;
    border: none;
    margin-top: 20px;
    position: absolute;
    left: 0;
    bottom: 0;
    max-height: 60px;
    border: 0px solid rgba(0, 0, 0, 0.1);
    border-radius: 0 0 2px 2px;
    transform: rotateZ(0deg);
    transition: all 0.1s ease-out;
    border-bottom-width: 7px;
  }

  .login button:hover {
    background-color: #fcdc5c; /* Change to the desired hover color */
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
            /* font-size: 90px; */
            font-size: 5vw;
            font-weight: bold;
            color: #000066;
        }

        .text-shadow {
            color: white;
            font-size: 25px;
            text-shadow: 0px 4px 4px black;
        }

        input {
            height: 10vh;
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
    <title>KonsulTap︱Forgot Password</title>
    <link rel="icon" href="../images/logo_icon.png" type="image/icon type">
</head>
<body>
    <div class="d-flex flex-column min-vh-100 min-vw-100">
        <div class="container d-flex flex-grow-1 justify-content-center align-items-center">
        <div class="col-lg-7 d-flex justify-content-center align-items-center">
                    <form class="login" id="forgotPasswordForm" method="POST">
                        <img src="../images/logomain.png" alt="Logo" width="100%" height="100%">
                        <h3 class="text-center py-3" style="color: #000066;">FORGOT PASSWORD</h3>
                        <div class="row pb-3 px-lg-1">
                            <div class="col-12">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="studID" id="studID" placeholder="Student I.D. Number" required>
                                    <label for="studID" class="form-label">I.D. Number <i class="text-danger">*</i></label>
                                </div>
                            </div>
                        </div>
                        <div class="row px-lg-5">
                            <div class="col-12">
                                <div class="d-grid my-3">
                                    <a href="../index.php" class="text-center text-dark fw-bold">GO BACK TO LOGIN</a>
                                </div>
                            </div>
                        </div>
                        <div class="row px-lg-1">
                            <div class="col-12">
                                <div class="d-grid py-3">
                                    <button class="btn btn-lg text-light" type="submit">SUBMIT</button>   
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
