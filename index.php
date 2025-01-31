<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'config.php';
require 'vendor/autoload.php';

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

// Redirect to a verfifcation page if the user is already has a verfication code
if (isset($_SESSION['verification_code'])){
    header('Location: verification.php');
    exit();
}

// Function to generate a random 5-digit code
function generateVerificationCode() {
    return rand(10000, 99999);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract username and password from the form
    $idNumber = $_POST['inputUsername']; // Assuming IdNumber is used as username
    $password = $_POST['inputPassword'];

    // Perform database query to check user credentials in students table
    $query = "SELECT fullName, Email, status, Password FROM students WHERE IdNumber = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $idNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if a row is returned
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if ($user['status'] == 'disabled') {
            // Display an alert indicating that the user is not active anymore
            $error = "User is not active anymore";
        } elseif (password_verify($password, $user['Password'])) {
            // User is active, proceed with sending verification code
            $_SESSION['role'] = 'student'; // Set user role in session
            $_SESSION['IdNumber'] = $idNumber; // Store IdNumber in session
            $_SESSION['verification_code'] = generateVerificationCode(); // Generate and store verification code in session
            $_SESSION['verification_code_timestamp'] = time(); // Get current time when verification code is generated

            // Assign fullName to $name
            $name = $user['fullName']; // Assuming fullName is a field in students table

            // Send verification code to user's email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'konsultap2024@gmail.com'; // Your Gmail username
                $mail->Password = 'yxhc yoxm ksht dluh'; // Your app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                //Recipient
                $mail->setFrom('konsultap2024@gmail.com', 'KonsulTap'); // Sender's email and name
                $mail->addAddress($user['Email']); // Recipient's email from the students table

               $htmlContent = file_get_contents('emailTemplates/verification_template.php');

                    // Replace placeholders with actual values
                    $htmlContent = str_replace('{{name}}', $name, $htmlContent);
                    $htmlContent = str_replace('{{verification_code}}', $_SESSION['verification_code'], $htmlContent);
                    $htmlContent = str_replace('{{year}}', date('Y'), $htmlContent);
                
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'KONSULTAP: TWO-FACTOR LOGIN AUTHENTICATION';
                    $mail->Body = $htmlContent; // Set the Body of the email
                
                    $mail->send();
                    echo 'Message has been sent';
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

            header('Location: verification.php'); // Redirect to verification page
            exit();
        } else {
            // Handle incorrect password
            $error = "Invalid ID Number or Password";
        }
    } else {
        // Handle incorrect password
        $error = "Invalid ID Number or Password";
    }

    // Perform similar checks for other user roles (itc, medprac, faculty)
    $tables = ['itc', 'medprac', 'faculty'];
    foreach ($tables as $table) {
        $query = "SELECT fullName, Email, status, Password FROM $table WHERE IdNumber = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $idNumber);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Check if a row is returned
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            if ($user['status'] == 'disabled') {
                // Display an alert indicating that the user is not active anymore
                $error = "User is not active anymore";
            } elseif (password_verify($password, $user['Password'])) {
                // User is active, proceed with sending verification code
                $_SESSION['role'] = $table; // Set user role in session
                $_SESSION['IdNumber'] = $idNumber; // Store IdNumber in session
                $_SESSION['verification_code'] = generateVerificationCode(); // Generate and store verification code in session
                $_SESSION['verification_code_timestamp'] = time(); // Get current time when verification code is generated

                 // Assign fullName to $name
                $name = $user['fullName']; // Assuming fullName is a field in students table
                // Send verification code to user's email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'konsultap2024@gmail.com'; // Your Gmail username
                    $mail->Password = 'yxhc yoxm ksht dluh'; // Your app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;

                    //Recipient
                    $mail->setFrom('konsultap2024@gmail.com', 'KonsulTap'); // Sender's email and name
                    $mail->addAddress($user['Email']); // Recipient's email from the current table

                   $htmlContent = file_get_contents('emailTemplates/verification_template.php');

                    // Replace placeholders with actual values
                    $htmlContent = str_replace('{{name}}', $name, $htmlContent);
                    $htmlContent = str_replace('{{verification_code}}', $_SESSION['verification_code'], $htmlContent);
                    $htmlContent = str_replace('{{year}}', date('Y'), $htmlContent);
                
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'KONSULTAP: TWO-FACTOR LOGIN AUTHENTICATION';
                    $mail->Body = $htmlContent; // Set the Body of the email
                
                    $mail->send();
                    echo 'Message has been sent';
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

                header('Location: verification.php'); // Redirect to verification page
                exit();
            } else {
                // Handle incorrect password
                $error = "Invalid ID Number or Password";
            }
        } 
    }    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="icon" href="images/logo_icon.png" type="image/icon type">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>KonsulTap︱Login</title>

    <style>
        
    </style>
</head>
<body style="background-image:url('images/bg-wallpaper.png'); background-repeat: no-repeat; background-size: cover; height: 100vh; width: 100%;">
    <div class="container py-2 pt-5 pt-lg-4">
        <div class="row justify-content-center">
            <div class="col-lg-5 bg-transparent pb-12 shadow-lg" style="background-color:#FFFFFF">
                <div class="container pt-2"></div>
                <form class="login pt-2 text-center" id="loginForm" method="POST">
                    <div class="white-container">
                        <img class="img-fluid border-end border-1 border-dark" src="images/lics-logo.png" alt="" width="45">
                        <img class="img-fluid" src="images/logomain.png" alt="" width="250" height="100">
                    </div>
                    <h1 class="h2 pt-2 pb-2 font-weight-normal" style="color: #000066;">LOGIN</h1>
                    <?php if(isset($error)) { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php } ?>
                    <div class="col-md-12"> 
                        <div class="form-floating mb-3">
                            <input type="text" id="inputUsername" name="inputUsername" class="form-control mx-auto mb-3" placeholder="ID Number" required autofocus style="width:100%;">
                            <label for="inputUsername" id="floatingLabel" class="">ID Number</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" id="inputPassword" name="inputPassword" class="form-control mx-auto mb-3" placeholder="ID Number" required autofocus style="width:100%;">
                            <label for="inputPassword" id="floatingLabel" class="">Password</label>
                            <span id="togglePassword" class="position-absolute" style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;" onclick="togglePasswordVisibility()">
                                <i id="passwordIcon" class="bi bi-eye"></i>
                            </span>
                        </div>
                        <a href="main/forgot-password.php" class="pb-3 pt-3 font-weight-normal" style="font-size: 20px;">Forgot Password?</a>
                    </div>
                    <div class="container pt-5">
                    <button class="btn btn-lg btn-block"  type="submit" id="submitbutton">LOGIN</button></div>
                    <div class="loader" id="loader"></div>
                </form>
                <div class="container-text pt-3 pb-2" style="text-align: center;">Copyright &copy; 2024 KonsulTAP </div>
            </div>
        </div>
    </div>
    <script>
        // Show password function
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('inputPassword');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text'; // Show password
                passwordIcon.classList.remove('bi-eye');
                passwordIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password'; // Hide password
                passwordIcon.classList.remove('bi-eye-slash');
                passwordIcon.classList.add('bi-eye');
            }
        }
    
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            const form = document.getElementById('loginForm');
            const loader = document.getElementById('loader');

            form.addEventListener('submit', function() {
                loader.classList.add('loader-visible');
            });

            function checkFilled() {
                inputs.forEach(input => {
                    const label = input.nextElementSibling; // Find the label immediately after the input
                    if (input.value.trim() !== '') {
                        input.classList.add('filled');
                    } else {
                        input.classList.remove('filled');
                    }
                });
            }

            // Check input value on input and change events
            inputs.forEach(input => {
                input.addEventListener('input', checkFilled);
                input.addEventListener('change', checkFilled);
            });

            // Initial check in case inputs are pre-filled
            checkFilled();
        });
    </script>

</body>

<style>
  #togglePassword {
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    z-index: 10; /* Ensure the icon is on top */
  }

  #passwordIcon {
    font-size: 1.25rem; /* Adjust size as needed */
    color: #6c757d; /* Default color */
    transition: color 0.3s ease; /* Smooth color transition */
  }

  input:focus + #floatingLabel,
  input:valid + #floatingLabel {
    color: #FFFFFF; /* Change label color on focus */
  }

  input:focus ~ #togglePassword #passwordIcon,
  input:valid ~ #togglePassword #passwordIcon {
    color: white; /* Change icon color on input focus */
  }

  .login {
    border-radius: 5px 5px 5px 5px;
    padding: 10px 20px 20px 20px;
    width: 100%;
    max-width: 500px;
    background: #FFFFFF;
    position: relative;
    box-shadow: 0px 1px 5px rgba(0, 0, 0, 0.3);
  }

  .white-container {
    background-color: #FFFFFF; /* Blue background color */
    padding: 20px 0px; /* Add padding for spacing */
    border-radius: 5px; /* Add border-radius for rounded corners */
    text-align: center; /* Center the content */
  }

  .login.loading button {
    max-height: 100%;
    padding-top: 50px;
  }

  .login input {
    display: block;
    padding: 15px 10px;
    margin-bottom: 10px;
    width: 100%;
    border: 1px solid #ddd;
    transition: border-width 0.2s ease;
    border-radius: 2px;
    color: #1c4494;
  }

  .login input {
    display: block;
    padding: 15px 10px;
    margin-bottom: 10px;
    width: 100%;
    border: 1px solid #ddd;
    transition: border-width 0.2s ease, background-color 0.2s ease;
    border-radius: 2px;
    color: #1c4494; /* Text color */
    background-color: #fff; /* Default background color */
    /* Placeholder color */
    ::placeholder {
        color: #B6E9C1; /* Adjust placeholder color as needed */
    }
}

.login input:focus,
.login input:valid {
    outline: none;
    color: #FFFFFF;
    border-color: #DBC14B;
    border-left-width: 35px;
    background-color: #1F438C;
}

.login input + i.fa {
    color: #e28743;
    font-size: 1em;
    position: absolute;
    margin-top: -47px;
    opacity: 0;
    left: 0;
    transition: all 0.1s ease-in;
}

.login input:focus + i.fa,
.login input:valid + i.fa {
    opacity: 1;
    left: 30px;
    transition: all 0.25s ease-out;
}

  .login a {
    font-size: 0.8em;
    color: #fcdc5c;
    text-decoration: none;
    margin-top: 20px;
  }

  .login .title {
    color: #fcdc5c;
    font-size: 1.2em;
    font-weight: bold;
    margin: 10px 0 30px 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
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

  /* Adjust margin-top for the link */
  .login a {
    font-size: 0.8em;
    color: #2196F3;
    text-decoration: none;
    margin-top: 20px; /* Add margin to separate from the button */
  }

  /* Hover effect */
  .login button:hover {
    background-color: #fcdc5c; /* Change to the desired hover color */
  }

 .loader{
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

 .loader-hidden{
    opacity: 0;
    visibility: hidden;
 }

 .loader-visible {
        opacity: 1;
        visibility: visible;
    }


 .loader::after{
    content: "";
    width: 75px;
    height: 75px;
    border: 15px solid #204299;
    border-top-color: #FEDE57;
    border-radius: 50%;
    animation: spin 1.50s ease infinite;
 }

/* Style for the label when the input is focused */
#inputUsername:focus + #floatingLabel,
#inputUsername.filled + #floatingLabel,
#inputPassword:focus + #floatingLabel,
#inputPassword.filled + #floatingLabel {
    padding-left: 50px; /* Adjust padding as needed */
    transition: padding 0.3s ease; /* Optional: smooth transition effect */
    color: white; /* Default label color */
}

/* Default label padding */
#floatingLabel {
    transition: padding 0.3s ease; /* Optional: smooth transition effect */
    color: #6C757D; /* Color when input is focused (adjust as needed) */
}

 @-webkit-keyframes spin {
        0% { -webkit-transform: rotate(0deg); }
        100% { -webkit-transform: rotate(360deg); }
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


</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputUsername = document.getElementById('inputUsername');
    const inputPassword = document.getElementById('inputPassword');
    const label = document.getElementById('floatingLabel');
    
    //check if user name is filled
    function checkFilledUsername() {
        if (inputUsername.value.trim() !== '') {
            inputUsername.classList.add('filled');
        } else {
            inputUsername.classList.remove('filled');
        }
    }
    // Check input value on input and change events
    inputUsername.addEventListener('input', checkFilledUsername);
    inputUsername.addEventListener('change', checkFilledUsername);

    //check if user name is filled
    function checkFilledPassword() {
        if (inputPassword.value.trim() !== '') {
            inputPassword.classList.add('filled');
        } else {
            inputPassword.classList.remove('filled');
        }
    }
    // Check input value on input and change events
    inputPassword.addEventListener('input', checkFilledPassword);
    inputPassword.addEventListener('change', checkFilledPassword);
});
</script>