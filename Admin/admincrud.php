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

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$userTypeFilter = isset($_GET['user_type']) ? mysqli_real_escape_string($conn, $_GET['user_type']) : '';

$queries = [
    'students' => "SELECT id, fullName, IdNumber, Email, profile_picture, status FROM students WHERE (disabled_at IS NULL OR disabled_at > CURDATE() - INTERVAL 4 YEAR)",
    'faculty' => "SELECT id, fullName, IdNumber, Email, profile_picture, status FROM faculty WHERE (disabled_at IS NULL OR disabled_at > CURDATE() - INTERVAL 4 YEAR)",
    'medprac' => "SELECT id, fullName, IdNumber, Email, profile_picture, status FROM medprac WHERE (disabled_at IS NULL OR disabled_at > CURDATE() - INTERVAL 4 YEAR)",
    'itc' => "SELECT id, fullName, IdNumber, Email, profile_picture, status FROM itc WHERE (disabled_at IS NULL OR disabled_at > CURDATE() - INTERVAL 4 YEAR)"
];

// $results = [];

// foreach ($queries as $type => $query) {
//     if (!empty($search)) {
//         $query .= " WHERE fullName LIKE '%$search%' OR IdNumber LIKE '%$search%'";
//     }
//     $results[$type] = mysqli_query($conn, $query);
// }

    // PAGINATION START
    // Start value
    $start = 0;
    // Number of rows to display per page
    $rowPerPage = 10;

    $totalRecords = 0; // Initialize total records
    $allResults = []; // Array to hold all results

    // Fetch total of rows and all records
    foreach ($queries as $type => $query) {
        if (!empty($search)) {
            $query .= " AND (fullName LIKE '%$search%' OR IdNumber LIKE '%$search%')";
        }
        $records = mysqli_query($conn, $query);
        
        while ($row = mysqli_fetch_assoc($records)) {
            $allResults[] = $row; // Collect all records
        }
    }

    // Total records after combining
    $totalRecords = count($allResults);

    // Calculate number of pages
    $pages = ceil($totalRecords / $rowPerPage);

    // If user clicks on a page, set new starting point
    if (isset($_GET['page'])) {
        $page = (int)$_GET['page'] - 1; // Convert to int for safety
        $start = $page * $rowPerPage;
    }

    // Fetch records for the current page
    $limitResult = array_slice($allResults, $start, $rowPerPage); // Limit combined results

    // For active page
    $id = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$title = "All User Records";

    $query = "SELECT fullName, profile_picture, status FROM itc WHERE IdNumber = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $idNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $fullName = $row['fullName'];
            $profilePicture = !empty($row['profile_picture']) ? $row['profile_picture'] : 'default_profile_picture.jpg';
            $status = $row['status'];
        } else {
            $fullName = $profilePicture = $status = null;
        }
    } else {
        $fullName = $profilePicture = $status = null;
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
        
        .page-link {
            color: #000066;
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
                        <div class="d-flex justify-content-lg-end justify-content-md-end justify-content-center collapse navbar-collapse" id="navbarSupportedContent">
                            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2">
                                <div class="ml-auto">
                                    <form class="form-inline" method="GET">
                                        <div class="input-group">
                                            <input type="text" class="form-control rounded-pill" name="search" placeholder="Search by name or id number" value="<?php echo htmlentities($search); ?>" aria-label="Search" aria-describedby="basic-addon2">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary rounded-pill border-0" type="submit">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                </nav>
                <div class="row p-lg-5 p-3 text-lg-start text-center m-0">
                    <h1 class="col-lg-6 col-md-12">User List</h1>
                </div>
                
                
                
                <!-- user list table -->
                <div class="row px-lg-5 p-3 text-lg-start text-center m-0">
                    <div class="col-md-12">
                        
                        <div class="row">
                            <div class="text-lg-end text-center px-3 pb-3 d-flex justify-content-lg-end justify-content-center" style="gap: 10px;">
                                <!-- upload button -->
                                <div>
                                    <button type="button" class="button border-0 text-decoration-none text-white px-lg-5 px-3 py-1" data-bs-toggle="modal" data-bs-target="#medCertModal" style="background-color: #76A6E5; border-radius: 25px;">Import Excel Data</a>
                                </div>
                                <!-- Modal For Upload Data -->
                                <div class="modal fade" id="medCertModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable modal-lg">
                                        <div class="modal-content">
                                            <!-- form for med cert qr generator -->
                                            <form id="uploadForm" action="../excelextension" method="post" enctype="multipart/form-data">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">Import File</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body px-5">
                                                    <div class="mb-3 row justify-content-center">
                                                        <div class="col-9 border border-1 border-dark shadow-bottom shadow" style="border-radius: 15px;">
                                                            <div class="file-input-container" style="display: flex; justify-content: center; align-items: center;">
                                                                <label for="file-upload" class="custom-file-upload">
                                                                    <i class="bi bi-upload" style="font-size: 8rem;"></i>
                                                                </label>
                                                                <!-- Ensure that the name attribute is set to "import_file" -->
                                                                <input id="file-upload" name="import_file" type="file" style="display:none;" onchange="updateFileName('file-upload', 'file')" accept=".xls, .xlsx">
                                                            </div>
                                                            <!-- file name placeholder -->
                                                            <div class="file-name-container">
                                                                <h2 id="file" class="text-center fs-4">Upload .xls, .csv, or .xlsx file for new students</h2>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <!-- cancel button -->
                                                    <button type="button" class="btn text-light" style="background-color: #76a6e5;" data-bs-dismiss="modal">Cancel</button>
                                                    <!-- submit button -->
                                                    <button type="submit" name="submit" id="btnSubmit" class="btn text-light" style="background-color: #000066;">Submit</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- create button -->
                                <div>
                                    <button onclick="window.location.href='create-user.php'" class="button border-0 text-decoration-none text-white px-5 py-1" style="background-color: #000066; border-radius: 25px;">CREATE</a>
                                </div>
                                <!-- alert on upload -->
                                <?php
                                    if(isset($_SESSION['message']))
                                    {
                                        // alert if upload is successful
                                        if ($_SESSION['message'] == 'success') {
                                            echo '
                                            <script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.js"></script>
                                            <script>
                                            $(this).ready(function () {
                                                Swal.fire({
                                                icon: "success",
                                                title: "Successfully Imported",
                                                timer: 3000,
                                                showCloseButton: true,
                                                showConfirmButton: false
                                                })
                                            })
                                            </script>
                                            ';
    
                                        // alert if file is invalid
                                        } elseif ($_SESSION['message'] == 'invalid') {
                                            echo '
                                            <script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.js"></script>
                                            <script>
                                            $(this).ready(function () {
                                                Swal.fire({
                                                icon: "warning",
                                                title: "Invalid File",
                                                timer: 3000,
                                                showCloseButton: true,
                                                showConfirmButton: false
                                                })
                                            })
                                            </script>
                                            ';
                                        } elseif ($_SESSION['message'] == 'idNumber') {
                                            echo '
                                            <script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.js"></script>
                                            <script>
                                            $(this).ready(function () {
                                                Swal.fire({
                                                icon: "warning",
                                                title: "Error Column in user ID Number",
                                                timer: 3000,
                                                showCloseButton: true,
                                                showConfirmButton: false
                                                })
                                            })
                                            </script>
                                            ';
                                        } elseif ($_SESSION['message'] == 'gradeLevel') {
                                            echo '
                                            <script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.js"></script>
                                            <script>
                                            $(this).ready(function () {
                                                Swal.fire({
                                                icon: "warning",
                                                title: "Error Column in user Grade Level",
                                                timer: 3000,
                                                showCloseButton: true,
                                                showConfirmButton: false
                                                })
                                            })
                                            </script>
                                            ';
                                        }
    
                                        // echo $_SESSION['message'];
                                        // Clear the session message after displaying the alert
                                        unset($_SESSION['message']);
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <h2><?php echo htmlspecialchars($title); ?></h2>
                        <?php if (count($limitResult) > 0) { ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Full Name</th>
                                        <th>ID Number</th>
                                        <th>Email</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $counter = $start + 1; // Start counter from the page's starting point (pagination)
                                    foreach ($limitResult as $row) {
                                        // Retrieve status from the r
                                        $status = isset($row['status']) ? $row['status'] : null;

                                        // Debug output to check status values
                                        echo "<tr>";
                                        echo "<td>" . sprintf("%04d", $counter++) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['fullName']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['IdNumber']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
                                        echo "<td>";

                                        // Edit button
                                        echo "<a href='editpage.php?IdNumber=" . urlencode($row['IdNumber']) . "' class='btn text-light' style='background-color: #000066;'><i class='bi bi-pencil-square'></i></a> &nbsp;";

                                

                                        // // Disable button (only shown if status is active)
                                        // if ($status === 'active') {
                                        //     echo "<a href='disable.php?IdNumber=" . urlencode($row['IdNumber']) . "' class='btn text-light' style='background-color: #76a6e5;' onclick=\"return confirm('Are you sure you want to disable this account?');\"><i class='bi bi-ban'></i></a>";
                                        // } 

                                        // // Activate button (only shown if status is disabled)
                                        // if ($status === 'disabled') {
                                        //     echo "<a href='activate.php?IdNumber=" . urlencode($row['IdNumber']) . "' class='btn text-light' style='background-color: #28a745;' onclick=\"return confirm('Are you sure you want to activate this account?');\"><i class='bi bi-check-circle'></i></a>";
                                        // } 
                                        
                                        // Disable button (only shown if status is active)
                                        if ($status === 'active') {
                                            echo "<a href='disable.php?IdNumber=" . urlencode($row['IdNumber']) . "' class='btn text-light' style='background-color: #76a6e5;' onclick=\"confirmAction(event, 'disable.php?IdNumber=" . urlencode($row['IdNumber']) . "', 'Are you sure you want to disable this account?');\"><i class='bi bi-ban'></i></a>";
                                        } 

                                        // Activate button (only shown if status is disabled)
                                        if ($status === 'disabled') {
                                            echo "<a href='activate.php?IdNumber=" . urlencode($row['IdNumber']) . "' class='btn text-light' style='background-color: #28a745;' onclick=\"confirmAction(event, 'activate.php?IdNumber=" . urlencode($row['IdNumber']) . "', 'Are you sure you want to activate this account?');\"><i class='bi bi-check-circle'></i></a>";
                                        } 

                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row pb-5">
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

                                        <li class="page-item">
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
                        <?php } else { ?>
                            <div class="text-center fs-4">No Available Record</div>
                        <?php } ?>
                    
                    
                        
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
function confirmAction(event, url, message) {
    event.preventDefault(); // Prevent the default link behavior
    Swal.fire({
        title: "Are you sure?",
        text: message,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, proceed!"
    }).then((result) => {
        if (result.isConfirmed) {
            // If confirmed, redirect to the provided URL
            window.location.href = url;
        }
    });
}
</script>

<script>
    //change label text to selected file
    function updateFileName(inputId, fileNameId) {
        const input = document.getElementById(inputId);
        const fileName = input.files[0] ? input.files[0].name : "Attach File";
        document.getElementById(fileNameId).innerText = fileName;
    }

     // Reset file input and file name on modal close
     document.getElementById('medCertModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('file-upload').value = "";
        document.getElementById('file').innerText = "Upload Excel file for new students";
    });
</script>