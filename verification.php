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

// Function to generate a random 5-digit code
function generateVerificationCode() {
    return rand(10000, 99999);
}

// Check if user is logged in and verification code is generated
if (!isset($_SESSION['role']) || !isset($_SESSION['IdNumber']) || !isset($_SESSION['verification_code'])) {
    header('Location: index.php'); // Redirect to login page if not logged in
    exit();
}

// Check if the verification code is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['num1']) && isset($_POST['num2']) && isset($_POST['num3']) && isset($_POST['num4']) && isset($_POST['num5'])) {
    // Extract the submitted verification code
    $submitted_code = $_POST['num1'] . $_POST['num2'] . $_POST['num3'] . $_POST['num4'] . $_POST['num5'];

    $verification_code_timestamp = $_SESSION['verification_code_timestamp']; // Time when verification code is generated from login
    $current_time = time(); // Current time
    
    // Check if submitted code matches the generated code
    if ($submitted_code == $_SESSION['verification_code']) {
        // Check if verification code is still valid with 5 minutes
        if (($current_time - $verification_code_timestamp) <= 300) {
            // Verification successful, set verified session variable
            $_SESSION['verified'] = true;
            
            // Redirect to dashboard based on user role
            if ($_SESSION['role'] == 'student') {
                header('Location: ../main/dashboard.php');
            } elseif ($_SESSION['role'] == 'itc') {
                header('Location: ../Admin/admincrud.php');
            } elseif ($_SESSION['role'] == 'faculty') {
                header('Location: ../main/dashboard.php');
            } elseif ($_SESSION['role'] == 'medprac') {
                header('Location: ../med/dashboard.php');
            }
            exit();
        } else {
            // Verification expired, display error message
            $error = "Expired verification code. Please resend code and try again.";
        }
    } else {
        // Verification failed, display error message
        $error = "Invalid verification code. Please try again.";
    }
}

// Resend verification code 
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['resend_code'])) {
    $idNumber = $_SESSION['IdNumber'];
    $_SESSION['verification_code'] = generateVerificationCode(); // Generate and store new verification code in session
    
    $dbTables = ['students', 'faculty', 'medprac', 'itc'];

    foreach ($dbTables as $db) {
        // Perform database query to check user credentials in students table
        $query = "SELECT fullName, Email, status, Password FROM $db WHERE IdNumber = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $idNumber);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            $name = $user['fullName'];
    
            $mail = new PHPMailer(true);
            try {
               //Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = ''; // Your Gmail username
                $mail->Password = ''; // Your app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;
    
                //Recipient
                $mail->setFrom('', 'KonsulTap'); // Sender's email and name
                $mail->addAddress($user['Email']); // Recipient's email from the students table
    
                $htmlContent = file_get_contents('emailTemplates/verification_template.php');

                // Replace placeholders with actual values
                $htmlContent = str_replace('{{name}}', $name, $htmlContent);
                $htmlContent = str_replace('{{verification_code}}', $_SESSION['verification_code'], $htmlContent);
                $htmlContent = str_replace('{{year}}', date('Y'), $htmlContent);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'KONSULTAP: RESEND TWO-FACTOR LOGIN AUTHENTICATION';
                $mail->Body = $htmlContent; // Set the Body of the email
            
                $mail->send();
    
                $_SESSION['verification_code_timestamp'] = time(); // Reset time when resend code
    
                // Success message
                echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
                <script> 
                $(this).ready(function () {
                    Swal.fire({
                        icon: "success",
                        title: "Code Resent",
                        text: "A new code has been sent to your email",
                        showCloseButton: true,
                        showConfirmButton: false
                    })
                })      
            
                setTimeout(function name(params) {
                    document.location = "verification.php";
                },3000);
                </script>';
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    
    <!-- sweetalert -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        body {
            background-image: url('images/bg-wallpaper.png'); height: 100vh; width: 100%;
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
    <title>KonsulTap︱Verification </title>
    <link rel="icon" href="images/logo_icon.png" type="image/icon type">
</head>
<body>
    <div class="d-flex flex-column min-vh-100 min-vw-100">
        <div class="container d-flex flex-grow-1 justify-content-center align-items-center">
            <div class="row justify-content-center">
                <div col-lg-7 shadow-lg p-5">
                    <form class="login" action="" method="post">
                        <h1 class="text-center fw-bold py-3" style="color: #000066;">TWO-FACTOR AUTHENTICATION</h1>
                        <p class="text-center fw-bold">
                            Enter the 5-digit code generated in your 
                            email to confirm your action
                        </p>
                    
                        <?php if(isset($error)) { ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php } ?>
                        <div class="row pb-3 px-lg-1">
                            <div class="col">
                                <input type="tel" class="form-control text-center" name="num1" id="num1" value="" placeholder="-" maxlength="1" autofocus required oninput="focusNextInput(event, 'num2')">
                            </div>
                            <div class="col">
                                <input type="tel" class="form-control text-center" name="num2" id="num2" value="" placeholder="-" maxlength="1"  required oninput="focusNextInput(event, 'num3')">
                            </div>
                            <div class="col">
                                <input type="tel" class="form-control text-center" name="num3" id="num3" value="" placeholder="-" maxlength="1" required  oninput="focusNextInput(event, 'num4')">
                            </div>
                            <div class="col">
                                <input type="tel" class="form-control text-center" name="num4" id="num4" value="" placeholder="-" maxlength="1" required  oninput="focusNextInput(event, 'num5')">
                            </div>
                            <div class="col">
                                <input type="tel" class="form-control text-center" name="num5" id="num5" value="" placeholder="-" maxlength="1" required oninput="focusNextInput(event, 'num5')" >
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-7 col-lg-5">
                                <div class="d-grid my-3">
                                    <a id="resendLink" href="?resend_code" class="text-center text-dark fw-bold" style="pointer-events: none; color: gray;" onclick="showLoader()">RESEND CODE</a>
                                    <div id="timer" class="text-center" style="display:none;">Available in <span id="countdown" class="fw-bold">60</span> seconds</div>
                                </div>
                            </div>
                        </div>
                        <div class="row px-lg-1">
                            <div class="col-12">
                                <div class="d-grid py-3">
                                    <button class="btn btn-lg btn-block" type="submit">VERIFY</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
            function showLoader() {
                const loader = document.getElementById('loader');
                // Show loader on page load
               loader.classList.add('loader-visible');
    
               // Hide loader after the page has fully loaded
               window.onload = function() {
                   loader.classList.remove('loader-visible');
               };
            }
            
            let timer;
            let countdownTime = 60;

            document.addEventListener('DOMContentLoaded', function() {
                const resendLink = document.getElementById('resendLink');
                const timerDiv = document.getElementById('timer');
                const countdownSpan = document.getElementById('countdown');

                // Show the timer and disable the link on load
                resendLink.style.pointerEvents = 'none'; // Disable link interaction
                resendLink.style.color = 'gray'; // Change color to indicate it's disabled
                timerDiv.style.display = 'block'; // Show the timer

                // Start the countdown
                countdownTime = 60;
                countdownSpan.textContent = countdownTime;

                timer = setInterval(() => {
                    countdownTime--;
                    countdownSpan.textContent = countdownTime;

                    if (countdownTime <= 0) {
                        clearInterval(timer);
                        resendLink.style.pointerEvents = 'auto'; // Enable link interaction
                        resendLink.style.color = ''; // Reset color
                        timerDiv.style.display = 'none'; // Hide the timer
                    }
                }, 1000);
            });
        </script>
        
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
    
    <script>
        function validateNumericInput(event, inputId) {
            const input = event.target;
    
            // Replace non-numeric characters with an empty string
            input.value = input.value.replace(/[^\d]/g, '');
    
            // Move focus to the next input field if current input is filled
            if (input.value.length >= input.maxLength) {
                const nextInput = document.getElementById(inputId);
                if (nextInput) {
                    nextInput.focus();
                }
            }
        }
    
        // Attach input event listeners to each input field
        document.getElementById('num1').addEventListener('input', function(event) {
            validateNumericInput(event, 'num2');
        });
        document.getElementById('num2').addEventListener('input', function(event) {
            validateNumericInput(event, 'num3');
        });
        document.getElementById('num3').addEventListener('input', function(event) {
            validateNumericInput(event, 'num4');
        });
        document.getElementById('num4').addEventListener('input', function(event) {
            validateNumericInput(event, 'num5');
        });
        document.getElementById('num5').addEventListener('input', function(event) {
            validateNumericInput(event, 'num5');
        });
    
        // Add keydown event listener to check for backspace
        document.querySelectorAll('input[type="tel"]').forEach(input => {
            input.addEventListener('keydown', function(event) {
                if (event.key === 'Backspace' && this.value.length === 0) {
                    const prevInputId = 'num' + (parseInt(this.id.slice(-1)) - 1);
                    const prevInput = document.getElementById(prevInputId);
                    if (prevInput) {
                        prevInput.focus();
                    }
                }
            });
        });
    </script>

</body>

</html>
