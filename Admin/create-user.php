<?php
require '../config.php';
session_start();

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

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$userTypeFilter = isset($_GET['user_type']) ? mysqli_real_escape_string($conn, $_GET['user_type']) : '';



$queries = [];
$queries['students'] = "SELECT id, fullName, IdNumber, Email, profile_picture FROM students";
$queries['faculty'] = "SELECT id, fullName, IdNumber, Email, profile_picture FROM faculty";
$queries['medprac'] = "SELECT id, fullName, IdNumber, Email, profile_picture FROM medprac";
$queries['itc'] = "SELECT id, fullName, IdNumber, Email, profile_picture FROM itc";

$results = [];

foreach ($queries as $type => $query) {
    if (!empty($search)) {
        $query .= " WHERE fullName LIKE '%$search%' OR IdNumber LIKE '%$search%'";
    }
    $results[$type] = mysqli_query($conn, $query);
}

$title = "All User Records";


// Check if the user is logged in
if (isset($_SESSION['IdNumber'])) {
    $idNumber = $_SESSION['IdNumber'];

    $query = "SELECT fullName FROM itc WHERE IdNumber = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $idNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $fullName = $row['fullName'];
        } else {
            echo "No rows returned by the query.<br>";
        }
    } else {
        echo "Failed to execute the query.<br>";
    }
} else {
    echo "IdNumber session variable is not set.";
}

// Check if the user has completed OTP verification
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    // Redirect to OTP verification page
    header("Location: ../verification.php");
    exit;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullnameForm = htmlspecialchars($_POST['fullnameForm']);
    $idNumber = htmlspecialchars($_POST['idNumber']);
    $type = htmlspecialchars($_POST['type']);
    $gradeLevel = isset($_POST['gradeLevel']) ? htmlspecialchars($_POST['gradeLevel']) : null;
    $genderForm = htmlspecialchars($_POST['gender']);
    $email = htmlspecialchars($_POST['email']);
    $height = isset($_POST['height']) ? htmlspecialchars($_POST['height']) : null;
    $weight = isset($_POST['weight']) ? htmlspecialchars($_POST['weight']) : null;
    $phoneNumber = htmlspecialchars($_POST['phoneNumber']);
    $contactPerson = htmlspecialchars($_POST['contactPerson']);
    $dateBirth = date('F d Y', strtotime(htmlspecialchars($_POST['dateBirth']))); //format date to month-date-year

    $hashedPass = password_hash($idNumber, PASSWORD_BCRYPT); // Hashed password, default pass is ID Number

    if ($type == "student") {
        $conn->query("INSERT INTO `students`(`fullName`, `IdNumber`, `GradeLevel`, `Gender`, `Email`, `Height`, `Weight`, `Phone`, `ContactPerson`, `dateofbirth`, `Password`, `role`, `status`) 
        VALUES ('$fullnameForm','$idNumber','$gradeLevel','$genderForm','$email','$height','$weight','$phoneNumber','$contactPerson','$dateBirth','$hashedPass','$type','active')");

        // Display success message and redirect
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script> 
        $(this).ready(function () {
            Swal.fire({
                icon: "success",
                title: "Student Successfully Added",
                showCloseButton: true,
                showConfirmButton: false
            })
        })  

        setTimeout(function name(params) {
            document.location = "create-user.php";
        }, 3000);
        </script>';
    }
    else if ($type == "faculty") {
        $conn->query("INSERT INTO `faculty`(`fullName`, `IdNumber`, `Gender`, `Email`, `Height`, `Weight`, `Phone`, `ContactPerson`, `dateofbirth`, `Password`, `role`, `status`) 
        VALUES ('$fullnameForm','$idNumber','$genderForm','$email','$height','$weight','$phoneNumber','$contactPerson','$dateBirth','$hashedPass','$type','active')");

        // Display success message and redirect
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script> 
        $(this).ready(function () {
            Swal.fire({
                icon: "success",
                title: "Faculty Successfully Added",
                showCloseButton: true,
                showConfirmButton: false
            })
        })  

        setTimeout(function name(params) {
            document.location = "create-user.php";
        }, 3000);
        </script>';
    }
    else if ($type == "medPrac") {
        $conn->query("INSERT INTO `medprac`(`fullName`, `IdNumber`, `Gender`, `Email`, `Height`, `Weight`, `Phone`, `ContactPerson`, `dateofbirth`, `Password`, `role`, `status`) 
        VALUES ('$fullnameForm','$idNumber','$genderForm','$email','$height','$weight','$phoneNumber','$contactPerson','$dateBirth','$hashedPass','medical practitioner','active')");

        // Display success message and redirect
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script> 
        $(this).ready(function () {
            Swal.fire({
                icon: "success",
                title: "Medical Practitioner Successfully Added",
                showCloseButton: true,
                showConfirmButton: false
            })
        })  

        setTimeout(function name(params) {
            document.location = "create-user.php";
        }, 3000);
        </script>';
    }
    else if ($type == "itc") {
        $conn->query("INSERT INTO `itc`(`fullName`, `IdNumber`, `Gender`, `Email`, `Height`, `Weight`, `Phone`, `ContactPerson`, `dateofbirth`, `Password`, `role`, `status`) 
        VALUES ('$fullnameForm','$idNumber','$genderForm','$email','$height','$weight','$phoneNumber','$contactPerson','$dateBirth','$hashedPass','$type','active')");

        // Display success message and redirect
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script> 
        $(this).ready(function () {
            Swal.fire({
                icon: "success",
                title: "ITC Successfully Added",
                showCloseButton: true,
                showConfirmButton: false
            })
        })  

        setTimeout(function name(params) {
            document.location = "create-user.php";
        }, 3000);
        </script>';
    }
    else {
        echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script> 
        $(this).ready(function () {
            Swal.fire({
                icon: "warning",
                title: "There seems to be an error",
                showCloseButton: true,
                showConfirmButton: false
            })
        })  

        setTimeout(function name(params) {
            document.location = "create-user.php";
        }, 3000);
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

    <title>KonsulTap︱User Management</title>
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
            <!-- sidebar -->
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
                                <!-- <img src="../images/<?php echo $profilePicture; ?>" width="35px" alt="" class="rounded-circle"> -->
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
                            <a href="admincrud.php" class="nav-link py-3 fw-bold fs-5 text active">
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
            <div class="col position-relative px-0 min-vh-100">
                <nav class="navbar navbar-expand-lg navbar-light bg-transparent border border-bottom">
                    <div class="container-fluid">
                        <a class="navbar-brand d-none d-md-inline" href="#">Create User</a>
                        <div class="d-flex justify-content-lg-end justify-content-md-end justify-content-center collapse navbar-collapse" id="navbarSupportedContent">
                        </div>
                    </div>
                </nav>

                <div class="container">
                    <div class="row">
                        <div class="col-12 p-lg-5 p-3">
                            <div class="col-12 border border-2 border-dark rounded shadow-bottom shadow">
                                <nav class="nav navbar navbar-expand-lg border-bottom border-2" style="min-height: 4rem; background-color:#006699">
                                    <div class="container-fluid">
                                        <div class="navbar-collapse justify-content-center" id="navbarNavAltMarkup">
                                            <div class="navbar nav-pills justify-content-center">
                                            </div>
                                        </div>
                                    </div>
                                </nav>

                                <form action="" method="post" id="userForm">
                                    <div class="mb-3 pt-3 px-4 row">
                                        <div class="form-floating mb-3 col-lg-4">
                                            <input type="text" class="form-control" id="fullnameForm" name="fullnameForm" placeholder="Fullname" required>
                                            <label for="fullnameForm" class="ps-4">Fullname <i class="text-danger">*</i></label>
                                        </div>
                                        <div class="form-floating mb-3 col-lg-4">
                                            <input type="number" class="form-control" id="idNumber" name="idNumber" placeholder="ID Number" required>
                                            <label for="idNumber" class="ps-4">ID Number <i class="text-danger">*</i></label>
                                        </div>
                                        <div class="form-floating mb-3 col-lg-4">
                                            <select class="form-select" aria-label="Default select example" id="type" name="type" required>
                                                <option value="" selected>Select User Type</option>
                                                <option value="student">Student</option>
                                                <option value="faculty">Faculty</option>
                                                <option value="medPrac">Medical Practitioner</option>
                                                <option value="itc">ITC</option>
                                            </select>
                                            <label for="type" class="ps-4">User Type <i class="text-danger">*</i></label>
                                        </div>
                                    </div>

                                    <!-- Section for Student -->
                                    <div id="studentSection" class="form-section d-none">
                                        <div class="mb-3 px-4 row">
                                            <!-- <div class="form-floating mb-3 col-4">
                                                <input type="number" class="form-control" id="gradeLevel" name="gradeLevel" placeholder="Grade Level">
                                                <label for="gradeLevel" class="ps-4">Grade Level <i class="text-danger">*</i></label>
                                            </div> -->
                                            <div class="form-floating mb-3 col-lg-4">
                                                <select class="form-select" aria-label="Default select example" id="gradeLevel" name="gradeLevel" required>
                                                    <option value="" selected>Select Grade Level</option>
                                                    <option value="1">Grade 1</option>
                                                    <option value="2">Grade 2</option>
                                                    <option value="3">Grade 3</option>
                                                    <option value="4">Grade 4</option>
                                                    <option value="5">Grade 5</option>
                                                    <option value="6">Grade 6</option>
                                                    <option value="7">Grade 7</option>
                                                    <option value="8">Grade 8</option>
                                                    <option value="9">Grade 9</option>
                                                    <option value="10">Grade 10</option>
                                                    <option value="11">Grade 11</option>
                                                    <option value="12">Grade 12</option>
                                                </select>
                                                <label for="type" class="ps-4">Grade Level <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <select class="form-select" aria-label="Default select example" id="gender" name="gender">
                                                    <option value="" selected>Select Gender</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                </select>
                                                <label for="gender" class="ps-4">Gender <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="email" class="form-control" id="email" name="email" placeholder="Email">
                                                <label for="email" class="ps-4">Email <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="number" class="form-control" id="height" name="height" placeholder="Height">
                                                <label for="height" class="ps-4">Height (cm)</label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="number" class="form-control" id="weight" name="weight" placeholder="Weight">
                                                <label for="weight" class="ps-4">Weight (kg)</label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="number" class="form-control" id="phoneNumber" name="phoneNumber" placeholder="Phone Number">
                                                <label for="phoneNumber" class="ps-4">Phone Number <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="text" class="form-control" id="contactPerson" name="contactPerson" placeholder="Contact Person">
                                                <label for="contactPerson" class="ps-4">Contact Person <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="date" class="form-control" id="dateBirth" name="dateBirth" placeholder="Date of Birth">
                                                <label for="dateBirth" class="ps-4">Date of Birth <i class="text-danger">*</i></label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section for Faculty, Medical Practitioner, and ITC -->
                                    <div id="commonSection" class="form-section d-none">
                                        <div class="mb-3 px-4 row">
                                            <div class="form-floating mb-3 col-lg-4">
                                                <select class="form-select" aria-label="Default select example" id="gender" name="gender">
                                                    <option value="" selected>Select Gender</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                </select>
                                                <label for="gender" class="ps-4">Gender <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="email" class="form-control" id="email" name="email" placeholder="Email">
                                                <label for="email" class="ps-4">Email <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="number" class="form-control" id="height" name="height" placeholder="Height">
                                                <label for="height" class="ps-4">Height </label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="number" class="form-control" id="weight" name="weight" placeholder="Weight">
                                                <label for="weight" class="ps-4">Weight </label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="number" class="form-control" id="phoneNumber" name="phoneNumber" placeholder="Phone Number">
                                                <label for="phoneNumber" class="ps-4">Phone Number <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="text" class="form-control" id="contactPerson" name="contactPerson" placeholder="Contact Person">
                                                <label for="contactPerson" class="ps-4">Contact Person <i class="text-danger">*</i></label>
                                            </div>
                                            <div class="form-floating mb-3 col-lg-4">
                                                <input type="date" class="form-control" id="dateBirth" name="dateBirth" placeholder="Date of Birth">
                                                <label for="dateBirth" class="ps-4">Date of Birth <i class="text-danger">*</i></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 p-4 row justify-content-center">
                                        <div class="col-12 text-center">
                                            <!-- Clear button -->
                                            <button type="button" id="clearButton" class="btn text-light" style="background-color: #76a6e5;">Clear</button>
                                            <!-- Submit button -->
                                            <button type="submit" name="submit" id="btnSubmit" class="btn text-light" style="background-color: #000066;">Submit</button>
                                        </div>
                                    </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('userForm');
    const typeSelect = document.getElementById('type');
    const studentSection = document.getElementById('studentSection');
    const commonSection = document.getElementById('commonSection');

    function updateSections() {
        const selectedType = typeSelect.value;

        // Handle student section visibility and required fields
        if (selectedType === 'student') {
            studentSection.classList.remove('d-none');
            commonSection.classList.add('d-none');
            studentSection.querySelectorAll('input, select').forEach(field => {
                if (field.id !== 'height' && field.id !== 'weight') {
                    field.removeAttribute('disabled');
                    field.setAttribute('required', 'required');
                } else {
                    field.removeAttribute('disabled');
                }
            });
            commonSection.querySelectorAll('input, select').forEach(field => {
                field.setAttribute('disabled', 'disabled');
                field.removeAttribute('required');
            });
        } else if (['faculty', 'medPrac', 'itc'].includes(selectedType)) {
            studentSection.classList.add('d-none');
            commonSection.classList.remove('d-none');
            studentSection.querySelectorAll('input, select').forEach(field => {
                field.setAttribute('disabled', 'disabled');
                field.removeAttribute('required');
            });
            commonSection.querySelectorAll('input, select').forEach(field => {
                if (field.id !== 'height' && field.id !== 'weight') {
                    field.removeAttribute('disabled');
                    field.setAttribute('required', 'required');
                } else {
                    field.removeAttribute('disabled');
                }
            });
        } else {
            studentSection.classList.add('d-none');
            commonSection.classList.add('d-none');
            studentSection.querySelectorAll('input, select').forEach(field => {
                field.setAttribute('disabled', 'disabled');
                field.removeAttribute('required');
            });
            commonSection.querySelectorAll('input, select').forEach(field => {
                field.setAttribute('disabled', 'disabled');
                field.removeAttribute('required');
            });
        }
    }

    function clearForm() {
        form.reset();
        updateSections(); // Reset sections to default state
    }

    typeSelect.addEventListener('change', updateSections);
    clearButton.addEventListener('click', clearForm);

    // Initial call to handle pre-selected values or default
    updateSections();
});
</script>

