<?php
session_start();
require '../config.php';

     // Function to format appointment time
     function formatAppointmentTime($start_time) {
        // Assuming each appointment lasts for one hour
        $end_time = date('H:i', strtotime('+1 hour', strtotime($start_time)));
        return date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time));
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

    // User is logged in, continue fetching user details
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


    // Fetch appointments with status "accept" from the appointments table
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $queryAppointments = "SELECT a.id, a.appointment_date, a.appointment_time, a.appointment_type, a.created_at, a.IdNumber, IFNULL(s.fullName, f.fullName) AS fullName, a.reason
    FROM appointments a
    LEFT JOIN students s ON a.IdNumber = s.IdNumber
    LEFT JOIN faculty f ON a.IdNumber = f.IdNumber
    WHERE a.status = 'accept' AND a.consult_done = 0";
    
    // Add search condition if search term is provided
    if (!empty($search)) {
        $queryAppointments .= " AND (s.fullName LIKE '%$search%' OR f.fullName LIKE '%$search%' OR a.IdNumber LIKE '%$search%')";
    }
    
    // Add the ORDER BY clause to the query
    $queryAppointments .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC, a.created_at ASC";
    
    $stmtAppointments = mysqli_prepare($conn, $queryAppointments);
    mysqli_stmt_execute($stmtAppointments);
    $resultAppointments = mysqli_stmt_get_result($stmtAppointments);
    
    // PAGINATION START
    // start value
    $start = 0;
    // number of rows to display per page
    $rowPerPage = 10;
    
    // fetch total of rows for pagination (with search condition)
    $totalQuery = $queryAppointments; // Use the same query with the search condition
    $resultTotal = mysqli_query($conn, $totalQuery);
    $num_rows = mysqli_num_rows($resultTotal); // Get the total number of rows
    
    // calculate number of data to display per page
    $pages = ceil($num_rows / $rowPerPage);
    
    // if user clicks on a page, set new starting point
    if (isset($_GET['page'])) {
        $page = (int)$_GET['page'] - 1;
        $start = $page * $rowPerPage;
    } else {
        $page = 0; // Default to first page
    }
    
    $queryRecords = $queryAppointments . " LIMIT $start, $rowPerPage"; // limit number of rows to display per page
    $stmtRecords = mysqli_prepare($conn, $queryRecords);
    mysqli_stmt_execute($stmtRecords);
    $resultRecords = mysqli_stmt_get_result($stmtRecords);

    // for active page
    if (isset($_GET['page'])) {
        $id = $_GET['page'];
    } else {
        $id = 1;
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

    <title>KonsulTap︱Consultation List</title>
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
        
        .page-link {
            color: #000066;
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
                            <a href="patient-list.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi-people"></i> <span class="d-none d-md-inline">Patient List</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="consultation-list.php" class="nav-link py-3 fw-bold fs-5 text active">
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
                        <a class="navbar-brand d-none d-md-inline" href="#">Consultation List</a>
                        <div class="d-flex justify-content-lg-end justify-content-md-end justify-content-center collapse navbar-collapse" id="navbarSupportedContent">
                            <!-- search form -->
                            <form method="GET" class="d-flex">
                                <input type="text" class="form-control rounded-pill" name="search" placeholder="Search by name or id number" value="<?php echo htmlentities($search); ?>" aria-label="Search" aria-describedby="basic-addon2">
                                <button class="bg-transparent border-0" type="submit"><i class="bi bi-search"></i></button>
                            </form>
                        </div>
                    </div>
                </nav>
                <div class="row p-lg-5 p-3 text-lg-start text-center m-0">
                    <h1 class="col-lg-6 col-md-12">Consultation List</h1>
<?php if (mysqli_num_rows($resultRecords) > 0) { ?>
                </div>
           <div class="table-responsive px-5">
    <!-- consultation list table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Full Name</th>
                    <th>Date Of Consultation</th>
                    <th>Date Applied</th>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $numberCtr = $start + 1; // Start counter from the page's starting point (pagination) ?>
                <?php while ($rowAppointment = mysqli_fetch_assoc($resultRecords)) { ?>
                    <tr>
                       <td><?php echo sprintf("%04d", $numberCtr++); ?></td>
                       <td><?php echo $rowAppointment['fullName']; ?></td>
                        <td><?php echo date('F j, Y', strtotime($rowAppointment['appointment_date'])); ?></td>
                        <td><?php echo date('F j, Y', strtotime($rowAppointment['created_at'])); ?></td>
                        <td><?php echo formatAppointmentTime($rowAppointment['appointment_time']); ?></td>
                        <td><?php echo $rowAppointment['appointment_type']; ?></td>
                        <td><?php echo $rowAppointment['reason']; ?></td> <!-- Output reason -->
                        
                        <td>
                           <a href="consultation-information.php?id=<?php echo $rowAppointment['id']; ?>&IdNumber=<?php echo urlencode($rowAppointment['IdNumber']); ?>&appointment_date=<?php echo urlencode($rowAppointment['appointment_date']); ?>" class='btn text-light' style='background-color: #000066;'><i class='bi bi-pencil-square'></i></a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
</div>
                        <div class="pb-5">
                            <div class="col-12 text-center">
                                <div class="pb-3">
                                    <?php
                                    if (!isset($_GET['page'])) {
                                        $page = 1;
                                    } else {
                                        $page = (int)$_GET['page'];
                                    }
                                    ?>
                                    Showing <?php echo $page ?> of <?php echo $pages ?> pages
                                </div>

                                <nav aria-label="Page navigation example">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item"><a class="page-link" href="?page=1">First</a></li>
                                        <li class="page-item d-none d-md-block">
                                            <?php if ($page > 1) { ?>
                                                <a class="page-link" href="?page=<?php echo $page - 1 ?>"><span aria-hidden="true">&laquo;</span></a>
                                            <?php } else { ?>
                                                <span class="page-link" aria-hidden="true">&laquo;</span>
                                            <?php } ?>
                                        </li>

                                        <?php
                                        // Calculate the start and end pages to display
                                        $start_page = max(1, $page - 2); // Show up to 2 pages before
                                        $end_page = min($pages, $start_page + 4); // Ensure 5 pages in total

                                        // Adjust start page if we're at the end
                                        if ($end_page - $start_page < 4) {
                                            $start_page = max(1, $end_page - 4); // Adjust start to ensure 5 pages are shown
                                        }

                                        // Display the page numbers
                                        for ($counter = $start_page; $counter <= $end_page; $counter++) { ?>
                                            <li class="page-item">
                                                <a class="page-link <?php echo $counter == $page ? 'active' : ''; ?>" href="?page=<?php echo $counter; ?>"><?php echo $counter; ?></a>
                                            </li>
                                        <?php } ?>

                                        <li class="page-item d-none d-md-block">
                                            <?php if ($page < $pages) { ?>
                                                <a class="page-link" href="?page=<?php echo $page + 1 ?>"><span aria-hidden="true">&raquo;</span></a>
                                            <?php } else { ?>
                                                <span class="page-link" aria-hidden="true">&raquo;</span>
                                            <?php } ?>
                                        </li>
                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $pages; ?>">Last</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
<?php } else {?>
<p class="text-center fs-4 pt-3">No appointments found</p>
    <?php } ?>

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