<?php
session_start();
require '../config.php';
date_default_timezone_set('Asia/Manila');
// Function to calculate age from date of birth
function calculateAge($dateOfBirth)
{
    // Convert date of birth string to a DateTime object
    $dob = new DateTime($dateOfBirth);

    // Get the current date
    $currentDate = new DateTime();

    // Calculate the difference between the current date and the birth date
    $age = $currentDate->diff($dob)->y;

    return $age;
}

// Check if the user is not logged in or does not have the role 'medprac', redirect to login page or show invalid role message
if (!isset($_SESSION['IdNumber']) || empty($_SESSION['IdNumber']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'medprac') {
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
    $query = "SELECT fullName, Email, profile_picture, Password FROM medprac WHERE IdNumber = ?";
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
    
        // Check if user changed their password from default password
        if (password_verify($idNumber, $password)) {
            header("Location: staff-change-password.php");
            exit;
        } 
    } else {
        // Handle the case if user details are not found
        $fullName = "User Not Found";
        $email = "N/A";
        $profilePicture = "../images/default_profile_picture.jpg";
    }


// Function to fetch patient details from students table
function fetchStudentDetails($conn, $idNumber)
{
    $query = "SELECT fullName, dateofbirth, IdNumber, Phone, ContactPerson, Height, Weight, gradeLevel, Gender,role,profile_picture FROM students WHERE IdNumber = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $idNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

// Function to fetch patient details from faculty table
function fetchFacultyDetails($conn, $idNumber)
{
    $query = "SELECT fullName, dateofbirth, IdNumber,Phone, ContactPerson, Height, Weight, role,Gender,profile_picture FROM faculty WHERE IdNumber = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $idNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

// Function to insert procedure history into the database
function insertProcedureHistory($conn, $idNumber, $description)
{
    $dateAdded = date('Y-m-d'); // Get current date
    $query = "INSERT INTO procedure_history_records (IdNumber, description, date_added) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $idNumber, $description, $dateAdded);
    mysqli_stmt_execute($stmt);
}

// Collect IdNumber from URL
if (isset($_GET['IdNumber'])) {
    $selectedIdNumber = $_GET['IdNumber'];

    // Fetch patient details based on the selected IdNumber
    $studentResult = fetchStudentDetails($conn, $selectedIdNumber);
    $facultyResult = fetchFacultyDetails($conn, $selectedIdNumber);

    if ($studentResult && mysqli_num_rows($studentResult) > 0) {
        $row = mysqli_fetch_assoc($studentResult);
        // Extract student details
        $patientFullName = $row['fullName'];
        $dateOfBirth = $row['dateofbirth'];
        $patientAge = calculateAge($dateOfBirth);
        $patientID = $row['IdNumber'];
        $patientStatus = $row['role'];
        $patientContactNo = $row['Phone'];
        $patientContactPerson = $row['ContactPerson'];
        $patientHeight = $row['Height'];
        $patientWeight = $row['Weight'];
        $patientGrade = $row['gradeLevel']; // Fetch grade level only from students table
        $patientGender = $row['Gender'];
        $patientProfile = !empty($row['profile_picture']) ? $row['profile_picture'] : "../images/default_profile_picture.jpg";
    } elseif ($facultyResult && mysqli_num_rows($facultyResult) > 0) {
        $row = mysqli_fetch_assoc($facultyResult);
        // Extract faculty details
        $patientFullName = $row['fullName'];
        $dateOfBirth = $row['dateofbirth'];
        $patientAge = calculateAge($dateOfBirth);
        $patientID = $row['IdNumber'];
        $patientStatus = $row['role'];
        $patientContactNo = $row['Phone'];
        $patientContactPerson = $row['ContactPerson'];
        $patientHeight = $row['Height'];
        $patientWeight = $row['Weight'];
        $patientGrade = "N/A"; // No grade level for faculty
        $patientGender = $row['Gender'];
        $patientProfile = !empty($row['profile_picture']) ? $row['profile_picture'] : "../images/default_profile_picture.jpg";
    } else {
        // Set default values if patient details are not found
        $patientFullName = "N/A";
        $patientAge = "N/A";
        $patientID = "N/A";
        $patientGrade = "N/A";
        $patientStatus = "N/A";
        $patientContactNo = "N/A";
        $patientContactPerson = "N/A";
        $patientHeight = "N/A";
        $patientWeight = "N/A";
        $patientGender = "N/A";
        $patientProfile = "../images/default_profile_picture.jpg"; // Default profile picture
    }
} else {
    // Set default values if IdNumber is not provided in the URL
    $patientFullName = "N/A";
    $patientAge = "N/A";
    $patientID = "N/A";
    $patientGrade = "N/A";
    $patientStatus = "N/A";
    $patientContactNo = "N/A";
    $patientContactPerson = "N/A";
    $patientHeight = "N/A";
    $patientWeight = "N/A";
    $patientGender = "N/A";
    $patientProfile = "../images/default_profile_picture.jpg"; // Default profile picture
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if description is set and not empty
    if (isset($_POST['description']) && !empty($_POST['description'])) {
        $description = $_POST['description'];
        insertProcedureHistory($conn, $selectedIdNumber, $description);
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

    <title>KonsulTap︱Patient List</title>
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

        .nav-center {
            display: flex;
            align-items: center; /* Vertically centers the items */
        }

        .nav-center .bi {
            margin-right: 10px; /* Adjust space between icon and text */
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
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- sidebar -->
            <div class="col-sm-auto sticky-top shadow p-0" style="background-color: #006699; height:auto; max-height: 100vh; overflow-y: auto;">
                <div class="d-flex flex-sm-column flex-row flex-nowrap align-items-center sticky-top p-0" style="background-color: #006699;">
                    <span class="d-none d-sm-inline col-12 bg-light">
                        <a href="#" class="d-flex align-items-center justify-content-center px-4 py-3 mb-md-0 me-md-auto">
                            <span class="d-md-none"><img src="../images/logo_icon.png" width="35px" alt=""></span>
                            <span class="d-none d-md-inline"><img src="../images/logomain.png" width="150" alt=""></span>
                        </a>
                    </span>
                    <hr class="text-dark d-none d-md-inline mt-0" style="height: 2px; min-width: 100%;">
                    <!-- staff profile -->
                    <div class="row px-3 text-dark">
                        <div class="col-lg-3 d-flex align-items-center justify-content-center py-3">
                            <a href="staff-profile.php">
                                <img src="../images/<?php echo $profilePicture; ?>" style="object-fit: cover;" height="35px" width="35px" alt="" class="rounded-circle">

                            </a>
                        </div>
                        <!-- staff name -->

                        <div class="col-lg-9 text-lg-start text-md-center py-3 d-none d-md-inline" style="color: #FFDE59;">
                            <div class="row">
                            <a href="staff-profile.php" class="text-decoration-none text-dark text-nowrap">
                                    <h5 style="color: white;"><?php echo $fullName; ?></h5>
                                </a>
                            </div>
                            <div class="row">
                                <h5 class="fw-bold">Doctor/Nurse</h5>
                            </div>
                        </div>
                    </div>
                    <!-- sidebar nav -->
                    <ul class="nav nav-pills nav-flush flex-sm-column flex-row flex-nowrap mb-auto mb-0 px-lg-3">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi-house"></i> <span class="d-none d-md-inline">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="patient-list.php" class="nav-link py-3 fw-bold fs-5 text active">
                                <i class="fs-4 bi-people"></i> <span class="d-none d-md-inline">Patient List</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="consultation-list.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-clipboard"></i> <span class="d-none d-md-inline">Consultation List</span>
                            </a>
                        </li>
                        
                        <?php
                            
                            $notifCtr = $conn->query("SELECT * FROM appointments WHERE is_unread = 1 AND status != 'denied'"); // select all notifcations
                            $ctr = mysqli_num_rows($notifCtr); // count all the notification
                            $notifRow = mysqli_fetch_assoc($notifCtr); // fetch all notification
                        ?>

                        <li class="nav-item">
                            <a href="notifications.php" class="nav-link py-3 fw-bold fs-5 text">
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
                            <a href="generate-qr.php" class="nav-link nav-center py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-qr-code"></i> <span class="d-none d-md-inline">Issue Medical<br>Documents</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="generate-vid-con.php" class="nav-link nav-center py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-camera-video"></i> 
                                <span class="d-none d-md-inline">Video Consultation</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="staff-calendar.php" class="nav-link py-3 fw-bold fs-5 text ">
                                <i class="bi bi-calendar"></i> <span class="d-none d-md-inline">Calendar</span>
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
            <div class="col position-relative px-0 min-vh-100">
                <nav class="navbar navbar-expand-lg navbar-light bg-transparent border border-bottom">
                    <div class="container-fluid">
                        <a class="navbar-brand d-none d-md-inline" href="#">Patient History</a>
                        <div class="d-flex justify-content-lg-end justify-content-md-end justify-content-center collapse navbar-collapse" id="navbarSupportedContent">

                        </div>
                    </div>
                </nav>
                <div class="row p-lg-5 p-3 text-lg-start text-center m-0">
                    <h1 class="col-lg-6 col-md-12">Patient History</h1>
                </div>
                <div class="row m-0">
                    <!-- profile -->
                    <div class="col-lg-4 col-12 px-5">
                        <!-- profile picture -->
                        <div class="d-flex align-items-center justify-content-center pb-3">
                            <img src="../images/<?php echo $patientProfile; ?>" style="object-fit: cover;" height="140px" width="140px" alt="" class="rounded-circle">
                        </div>
                        <!-- patient name -->
                        <div class="d-flex align-items-center justify-content-center pb-3">
                            <h4 class="fw-bold"><?php echo $patientFullName; ?></h4>

                        </div>
                        <!-- patient info -->
                        <div class="d-flex align-items-center justify-content-center pb-3">
                            <table class="table table-hover px-4">
                                <tbody>
                                    <tr>
                                        <td>Age: </td>
                                        <td><?php echo $patientAge; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Sex: </td>
                                        <td><?php echo $patientGender; ?></td>
                                    </tr>
                                    <tr>
                                        <td>ID: </td>
                                        <td><?php echo $patientID; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Grade: </td>
                                        <td><?php echo $patientGrade; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Status: </td>
                                        <td><?php echo $patientStatus; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Contact No.: </td>
                                        <td><?php echo $patientContactNo; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Contact Person: </td>
                                        <td><?php echo $patientContactPerson; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Height (cm): </td>
                                        <td><?php echo $patientHeight; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Weight (kg): </td>
                                        <td><?php echo $patientWeight; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- edit button -->
                        <div class="pb-5">
                            <a href="edit-patient-procedure.php?IdNumber=<?php echo urlencode($row['IdNumber']);?>" class="button text-decoration-none text-white px-5 py-1" style="background-color: #000066; border-radius: 25px;">EDIT</a>
                        </div>
                    </div>
                    <!-- history card -->
                    <div class="col-lg-8 col-12 px-5 ">
                        <div class="col-12 border border-2 border-dark rounded shadow-bottom shadow" style="min-height: 50vh;">
                            <!-- navbar for card -->
                            <nav class="nav navbar navbar-expand-lg border-bottom border-2">
                                <div class="container-fluid">
                                    <div class="navbar-collapse justify-content-center" id="navbarNavAltMarkup">
                                        <div class="navbar nav-pills justify-content-center">
                                            <a class="nav-link px-lg-3 text-white" style="background-color: #000066;" href="procedure-history.php?IdNumber=<?php echo urlencode($row['IdNumber']); ?>">Procedure History</a>
                                            <a class="nav-link px-lg-3 text-dark" href="medical-history.php?IdNumber=<?php echo urlencode($row['IdNumber']); ?>">Past Medical History</a>
                                            <a class="nav-link px-lg-3 text-dark" href="prescription.php?IdNumber=<?php echo urlencode($row['IdNumber']); ?>">Prescription</a>

                                        </div>
                                    </div>
                                </div>
                            </nav>
                            <!-- card information -->
                            <div class="row p-5" style="max-height: 300px; overflow-y: auto;">
                                <!-- Fetch Precription history from the database and display here -->
                                <?php
                                $procedureHistoryQuery = "SELECT title, description, date_added FROM procedure_history_records WHERE IdNumber = ?";
                                $stmt = mysqli_prepare($conn, $procedureHistoryQuery);
                                mysqli_stmt_bind_param($stmt, "s", $selectedIdNumber);
                                mysqli_stmt_execute($stmt);
                                $procedureHistoryResult = mysqli_stmt_get_result($stmt);
                                // Check if there is record, else print no available record
                                if (mysqli_num_rows($procedureHistoryResult) > 0) {
                                    while ($procedureHistoryRow = mysqli_fetch_assoc($procedureHistoryResult)) {
                                        $title = $procedureHistoryRow['title'];
                                        $description = $procedureHistoryRow['description'];
                                        $date = date('F j, Y', strtotime($procedureHistoryRow['date_added']));
                                        echo "<span class='fs-5 pb-2'>$date: <a href='pdfs/$selectedIdNumber/$description.pdf' target='_blank'>$title</a> </span><br>";
                                    }
                                } else {
                                    echo "<span class='fs-5'>No available record</span>";
                                }
                                ?>
                            </div>
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

</body>

</html>
