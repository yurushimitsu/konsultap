<?php
session_start();
require '../config.php';

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

// User is logged in and verified, continue fetching user details
$idNumber = $_SESSION['IdNumber'];

// Fetch user details from the database based on the IdNumber
$query = "SELECT fullName, Email, profile_picture FROM medprac WHERE IdNumber = ?";
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

// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Fetch the number of consultations for the current month
$query = "SELECT COUNT(*) as count FROM appointments WHERE MONTH(appointment_date) = ? AND YEAR(appointment_date) = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $currentMonth, $currentYear);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$monthlyConsultations = $row['count'];

// Fetch the total number of students and faculty
$query = "SELECT COUNT(*) as count FROM students";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalStudents = $row['count'];

$query = "SELECT COUNT(*) as count FROM faculty";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalFaculty = $row['count'];

$totalStaff = $totalStudents + $totalFaculty;

// Fetch top 5 reasons for the current month, excluding "leave" and "others" in appointment_type
// Fetch data for both current and previous months
$query = "
    SELECT reason, 
           MONTH(appointment_date) AS month,
           YEAR(appointment_date) AS year,
           COUNT(*) AS count
    FROM appointments
    WHERE reason NOT IN ('leave', 'event')
    AND appointment_type NOT IN ('others', 'leave')
    AND (MONTH(appointment_date) = ? OR MONTH(appointment_date) = ?)
    AND YEAR(appointment_date) = ?
    GROUP BY reason, month, year
    ORDER BY count DESC";

$stmt = mysqli_prepare($conn, $query);
$previousMonth = $currentMonth == 1 ? 12 : $currentMonth - 1; // Handle year transition
mysqli_stmt_bind_param($stmt, "iii", $currentMonth, $previousMonth, $currentYear);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$reasonCounts = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Key by reason and month for easy aggregation later
    $reasonKey = $row['reason'];
    if (!isset($reasonCounts[$reasonKey])) {
        $reasonCounts[$reasonKey] = [0, 0];  // [currentMonthCount, previousMonthCount]
    }

    if ($row['month'] == $currentMonth) {
        $reasonCounts[$reasonKey][0] = $row['count'];  // Set current month count
    } else {
        $reasonCounts[$reasonKey][1] = $row['count'];  // Set previous month count
    }
}

// Now, sort the reasons by the combined total count (sum of both months)
uasort($reasonCounts, function ($a, $b) {
    return array_sum($b) - array_sum($a);  // Sort by combined count descending
});

// Get the top 5 reasons
$top5Reasons = array_slice($reasonCounts, 0, 5);

// Prepare the data for the chart
$reasonsTop5 = [];
$countsCurrentTop5 = [];
$countsPreviousTop5 = [];

foreach ($top5Reasons as $reason => $counts) {
    $reasonsTop5[] = $reason;
    $countsCurrentTop5[] = $counts[0];  // Current month count
    $countsPreviousTop5[] = $counts[1];  // Previous month count
}

// Fetch other reasons and count them under "Others"
// Fetching appointments
$query = "
    SELECT reason, COUNT(*) as count
    FROM appointments
    WHERE reason NOT IN ('leave', 'event')
    AND appointment_type NOT IN ('others', 'leave')
    AND MONTH(appointment_date) = ? 
    AND YEAR(appointment_date) = ?
    GROUP BY reason
    ORDER BY count DESC
    LIMIT 5";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $currentMonth, $currentYear);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


// Fetching cancelled appointments
$query = "
    SELECT reason, COUNT(*) as count
    FROM cancel_appointments
    WHERE MONTH(appointment_date_cancelled) = ? 
    AND YEAR(appointment_date_cancelled) = ?
    GROUP BY reason
    ORDER BY count DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $currentMonth, $currentYear);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


$cancelReasons = [];
$cancelCounts = [];

while ($row = mysqli_fetch_assoc($result)) {
    $cancelReasons[] = $row['reason'];
    $cancelCounts[] = $row['count'];
}


// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Fetch all IdNumbers from appointments for the specified month and year
$query = "SELECT IdNumber FROM appointments WHERE MONTH(appointment_date) = ? AND YEAR(appointment_date) = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $currentMonth, $currentYear);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$studentMaleConsultations = 0;
$studentFemaleConsultations = 0;
$facultyMaleConsultations = 0;
$facultyFemaleConsultations = 0;

// Check each IdNumber to see if it's in the students or faculty table and get gender
while ($row = mysqli_fetch_assoc($result)) {
    $idNumber = $row['IdNumber'];

    // Check if IdNumber is in the students table and fetch gender
    $queryStudent = "SELECT Gender FROM students WHERE IdNumber = ?";
    $stmtStudent = mysqli_prepare($conn, $queryStudent);
    mysqli_stmt_bind_param($stmtStudent, "s", $idNumber);
    mysqli_stmt_execute($stmtStudent);
    $resultStudent = mysqli_stmt_get_result($stmtStudent);
    $rowStudent = mysqli_fetch_assoc($resultStudent);

    if ($rowStudent) {
        // Increment student consultations based on gender
        if ($rowStudent['Gender'] === 'Male') {
            $studentMaleConsultations++;
        } elseif ($rowStudent['Gender'] === 'Female') {
            $studentFemaleConsultations++;
        }
    } else {
        // If not found in students, check in faculty and fetch gender
        $queryFaculty = "SELECT Gender FROM faculty WHERE IdNumber = ?";
        $stmtFaculty = mysqli_prepare($conn, $queryFaculty);
        mysqli_stmt_bind_param($stmtFaculty, "s", $idNumber);
        mysqli_stmt_execute($stmtFaculty);
        $resultFaculty = mysqli_stmt_get_result($stmtFaculty);
        $rowFaculty = mysqli_fetch_assoc($resultFaculty);

        if ($rowFaculty) {
            // Increment faculty consultations based on gender
            if ($rowFaculty['Gender'] === 'Male') {
                $facultyMaleConsultations++;
            } elseif ($rowFaculty['Gender'] === 'Female') {
                $facultyFemaleConsultations++;
            }
        }
    }
}

// Prepare data for the line chart
$consultationTypes = ['Student Male', 'Student Female', 'Faculty Male', 'Faculty Female'];
$consultationCounts = [
    $studentMaleConsultations,
    $studentFemaleConsultations,
    $facultyMaleConsultations,
    $facultyFemaleConsultations
];





$queryConsultDone = "
    SELECT a.appointment_type, COUNT(a.IdNumber) as count, s.Gender as student_gender, f.Gender as faculty_gender
    FROM appointments a
    LEFT JOIN students s ON a.IdNumber = s.IdNumber
    LEFT JOIN faculty f ON a.IdNumber = f.IdNumber
    WHERE a.consult_done = 1
    GROUP BY a.appointment_type, s.Gender, f.Gender";

$resultConsultDone = $conn->query($queryConsultDone);

// Prepare an array to store counts for each combination of appointment_type and gender
$consultDoneCounts = [];
$appointmentTypes = [];

// Initialize structure to hold counts
$genderCategories = ['Student Male', 'Student Female', 'Faculty Male', 'Faculty Female'];

while ($row = $resultConsultDone->fetch_assoc()) {
    $appointmentType = $row['appointment_type'];

    // Check if this appointment type is already in the list, if not, add it
    if (!in_array($appointmentType, $appointmentTypes)) {
        $appointmentTypes[] = $appointmentType;
        // Initialize counts for this appointment type and gender categories
        $consultDoneCounts[$appointmentType] = array_fill_keys($genderCategories, 0);
    }

    // Determine the gender and increment the correct count
    if (!empty($row['student_gender'])) {
        $gender = ($row['student_gender'] == 'Male') ? 'Student Male' : 'Student Female';
        $consultDoneCounts[$appointmentType][$gender] += $row['count'];
    } elseif (!empty($row['faculty_gender'])) {
        $gender = ($row['faculty_gender'] == 'Male') ? 'Faculty Male' : 'Faculty Female';
        $consultDoneCounts[$appointmentType][$gender] += $row['count'];
    }
}

// Fetch total canceled appointments
$queryCancel = "SELECT COUNT(*) as count FROM cancel_appointments";
$resultCancel = $conn->query($queryCancel);
$cancelCount = $resultCancel->fetch_assoc()['count'];

// Prepare the data for the chart
$consultDoneCountsJson = json_encode($consultDoneCounts); // Convert to JSON for JS
$appointmentTypesJson = json_encode($appointmentTypes);
$cancelCountJson = json_encode($cancelCount);




// Convert PHP arrays to JSON for Chart.js
$reasonsTop5Json = json_encode($reasonsTop5);
$countsCurrentTop5Json = json_encode($countsCurrentTop5);
$countsPreviousTop5Json = json_encode($countsPreviousTop5);

$cancelReasonsJson = json_encode($cancelReasons);
$cancelCountsJson = json_encode($cancelCounts);

$consultationCountsJson = json_encode($consultationCounts);



?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>

    <!-- sweetalert -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

      <script src="../timeout.js"></script> 



    <title>KonsulTap︱Dashboard</title>
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

        ul li:hover .nav-link {
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

    .responsive-chart {
    width: 100% !important; /* Force the chart to take the full width of the container */
    height: auto !important; /* Allow height to adjust automatically */
}
.chart-size {
    width: 100% !important; /* Force the chart to take the full width of the container */
    height: 400px !important; /* Set a specific height for both charts */
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
                            <a href="dashboard.php" class="nav-link py-3 fw-bold fs-5 text active">
                                <i class="fs-4 bi-house"></i> <span class="d-none d-md-inline">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="patient-list.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi-people"></i> <span class="d-none d-md-inline">Patient List</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="consultation-list.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-clipboard"></i> <span class="d-none d-md-inline">Consultation List</span>
                            </a>
                        </li>

                        <?php
                            $notifCtr = $conn->query("SELECT * FROM appointments WHERE is_unread = 1 AND status != 'denied'"); // select all notifications
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

            <!-- dashboard -->
            <div class="col-sm p-0 min-vh-100">
                <div class="container">
                    <div class="row pt-5 pb-5">
                        <div class="col-lg-6 pb-3">
                        <div class="card text-center">
                            <i class="fs-1 bi bi-clipboard"></i>
                            <h4>Monthly Consultations</h4>
                            <h1><?php echo $monthlyConsultations; ?></h1>
                        </div>
                        </div>
                        <div class="col-lg-6 pb-3">
                        <div class="card text-center">
                            <i class="fs-1 bi-people"></i>
                            <h4>Students/Staffs</h4>
                            <h1><?php echo $totalStaff; ?></h1>
                        </div>
                        </div>
                    </div>

                    
               <!-- Chart Section -->
                <!-- Chart Section -->
            <div class="row pb-5">
                <div class="col-lg-6 col-md-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Appointment Reasons</h5>
                            <canvas id="reasonsChart" width="400" height="400" style="width: 200px; height: 200px;"></canvas>
                            </div>
                    </div>
                </div>

                <div class="col-lg-6 col-md-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Consultation Counts</h5>
                            <canvas id="consultationChart" width="400" height="400" style="width: 200px; height: 200px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
                
                 <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Cancelled Appointments vs Consult Done </h5>
                            <canvas id="consultDoneChart" class="responsive-chart"></canvas> <!-- Removed fixed width and height -->
                        </div>
                    </div>
                </div>
                
                </br>

              
                    <div class="row pb-5">
                        <div class="col-lg-12 text-center">
                        <button class="btn btn-primary" onclick="downloadExcel()">Download Data as Excel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    const reasonsTop5 = <?php echo $reasonsTop5Json; ?>;
    const countsCurrentTop5 = <?php echo $countsCurrentTop5Json; ?>;
    const countsPreviousTop5 = <?php echo $countsPreviousTop5Json; ?>;
    
    const cancelReasons = <?php echo $cancelReasonsJson; ?>;
    const cancelCounts = <?php echo $cancelCountsJson; ?>;
    
    const consultationCounts = <?php echo $consultationCountsJson; ?>;
    const consultationTypes = ['Student Male', 'Student Female', 'Faculty Male', 'Faculty Female'];


    const ctx = document.getElementById('reasonsChart').getContext('2d');
const reasonsChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: reasonsTop5,  // Use the overall top 5 reasons
        datasets: [
            {
                label: 'Current Month',  // Dataset for the current month
                data: countsCurrentTop5,
                backgroundColor: '#3D5A9A',  // Color for current month bars
                borderColor: '#03045e',
                borderRadius: 5,
                borderWidth: 2,
                hoverOffset: 70,
                hoverBorderWidth: 4,
                // Set the bar width (to create space between bars of different months)
                barThickness: 30,  
            },
            {
                label: 'Previous Month',  // Dataset for the previous month
                data: countsPreviousTop5,
                backgroundColor: '#0094D8',  // Color for previous month bars
                borderColor: '#0077b6',
                borderRadius: 5,
                borderWidth: 2,
                hoverOffset: 70,
                hoverBorderWidth: 4,
                // Set the bar width (same as the current month)
                barThickness: 30,
            }
        ]
    },
    options: {
        responsive: true,
        devicePixelRatio: 2,
        animation: {
            animateScale: true,
            animateRotate: true
        },
        interaction: {
            mode: 'nearest',
            intersect: true
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        family: "'Helvetica', 'Arial', sans-serif",
                        size: 14,
                        weight: 'bold'
                    },
                    padding: 10,
                    boxWidth: 20,
                    color: '#333'
                }
            },
            title: {
                display: true,
                text: 'Top 5 Appointment Reasons: Current vs Previous Month',
                font: {
                    family: "'Helvetica', 'Arial', sans-serif",
                    size: 18,
                    weight: 'bold'
                },
                padding: {
                    top: 10,
                    bottom: 20
                }
            },
            datalabels: {
                color: '#000',
                font: {
                    family: "'Helvetica', 'Arial', sans-serif",
                    weight: 'bold',
                    size: 14
                },
                padding: 5
            }
        },
        scales: {
            x: {
                // Group bars next to each other
                stacked: false,  
                title: {
                    display: true,
                    text: 'Reasons'
                },
                // Define the spacing between the groups of bars
                ticks: {
                    beginAtZero: true
                }
            },
            y: {
                stacked: false,  // No stacking, so bars are next to each other
                title: {
                    display: true,
                    text: 'Count'
                }
            }
        }
    }
});



const ctxConsultation = document.getElementById('consultationChart').getContext('2d');

// Define colors for each category
const backgroundColors = ['#0094D8', '#A4D4E7', '#FF5733', '#FFC300'];

// Calculate total consultations for percentage calculation
const totalConsultations = consultationCounts.reduce((acc, count) => acc + count, 0);

// Calculate percentages
const percentages = consultationCounts.map(count => ((count / totalConsultations) * 100).toFixed(2));

const consultationChart = new Chart(ctxConsultation, {
    type: 'bar',
    data: {
        labels: consultationTypes,  // ['Student Male', 'Student Female', 'Faculty Male', 'Faculty Female']
        datasets: [{
            label: 'Number of Consultations',
            data: consultationCounts,  // Data in numbers
            borderColor: '#3D5A9A',
            backgroundColor: backgroundColors,  // Different colors for each category
            borderWidth: 1.5,
            borderRadius: 5,
            hoverBorderColor: '#3D5A9A',  // Same as the original color
            hoverBackgroundColor: backgroundColors,  // Keep the same colors on hover
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        size: 12,
                       
                    },
                    color: '#3D5A9A',
                    generateLabels: function(chart) {
                        const originalLabels = Chart.defaults.plugins.legend.labels.generateLabels(chart);
                        return originalLabels.filter(label => label.text !== 'Number of Consultations').concat([
                            { text: 'Student Male', fillStyle: '#0094D8', hidden: false, lineWidth: 1 },
                            { text: 'Student Female', fillStyle: '#A4D4E7', hidden: false, lineWidth: 1 },
                            { text: 'Faculty Male', fillStyle: '#FF5733', hidden: false, lineWidth: 1 },
                            { text: 'Faculty Female', fillStyle: '#FFC300', hidden: false, lineWidth: 1 }
                        ]);
                    }
                }
            },
            title: {
                display: true,
                text: 'Consultation Counts by Gender for Students and Faculty',
                font: {
                    size: 18,
                    weight: 'bold' // Kept bold for emphasis
                },
                color: '#3D5A9A'
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        const count = consultationCounts[tooltipItem.dataIndex];
                        const percentage = percentages[tooltipItem.dataIndex];
                        return `${consultationTypes[tooltipItem.dataIndex]}: ${count} consultations (${percentage}%)`;
                    }
                },
                backgroundColor: 'rgba(0, 148, 216, 0.8)',
                titleColor: '#FFFFFF',
                bodyColor: '#FFFFFF'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(61, 90, 154, 0.2)',
                },
                ticks: {
                    color: '#3D5A9A',
                    font: {
                        size: 12
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#3D5A9A',
                    font: {
                        size: 12
                    }
                }
            }
        },
        interaction: {
            mode: 'nearest',
            intersect: true,
        },
        elements: {
            bar: {
                borderRadius: 5,
                // Add scaling effect on hover
                hover: {
                    animation: {
                        duration: 400,
                        easing: 'easeInOutQuart',
                    },
                    scale: {
                        x: 1.1,
                        y: 1.1
                    }
                }
            }
        },
        animations: {
            // Animate initial loading of the chart
            tension: {
                duration: 1000,
                easing: 'easeInOutQuart',
                from: 0.4,
                to: 0,
                loop: true
            }
        }
    }
});


const consultDoneCounts = <?php echo $consultDoneCountsJson; ?>;
const appointmentTypes = <?php echo $appointmentTypesJson; ?>;
const cancelledAppointmentsCounts = [<?php echo $cancelCountJson; ?>];

// Initialize empty arrays for chart data
let studentMaleCounts = [];
let studentFemaleCounts = [];
let facultyMaleCounts = [];
let facultyFemaleCounts = [];

// Loop through each appointment type and extract counts for each gender category
appointmentTypes.forEach(type => {
    studentMaleCounts.push(consultDoneCounts[type]['Student Male']);
    studentFemaleCounts.push(consultDoneCounts[type]['Student Female']);
    facultyMaleCounts.push(consultDoneCounts[type]['Faculty Male']);
    facultyFemaleCounts.push(consultDoneCounts[type]['Faculty Female']);
});

// Add 0 for Cancelled Appointments in other datasets and the actual value in a separate dataset
studentMaleCounts.push(0);
studentFemaleCounts.push(0);
facultyMaleCounts.push(0);
facultyFemaleCounts.push(0);

// Add the actual Cancelled Appointments count in a separate dataset
const cancelledCountsForEachType = new Array(appointmentTypes.length).fill(0);
cancelledCountsForEachType.push(cancelledAppointmentsCounts[0]);

// Set labels for the chart (appointment types + Cancelled Appointments)
const labels = appointmentTypes.concat(['Cancelled Appointments']);

// Configure gradient blue shades for distinct bar designs
const ctxBar = document.getElementById('consultDoneChart').getContext('2d');
const gradientBlue1 = ctxBar.createLinearGradient(0, 0, 0, 400);
gradientBlue1.addColorStop(0, 'rgba(54, 162, 235, 1)');
gradientBlue1.addColorStop(1, 'rgba(54, 162, 235, 0.5)');

const gradientBlue2 = ctxBar.createLinearGradient(0, 0, 0, 400);
gradientBlue2.addColorStop(0, 'rgba(164, 212, 231, 1)');
gradientBlue2.addColorStop(1, 'rgba(164, 212, 231, 0.5)');

const gradientBlue3 = ctxBar.createLinearGradient(0, 0, 0, 400);
gradientBlue3.addColorStop(0, 'rgba(0, 123, 255, 1)');
gradientBlue3.addColorStop(1, 'rgba(0, 123, 255, 0.5)');

const gradientBlue4 = ctxBar.createLinearGradient(0, 0, 0, 400);
gradientBlue4.addColorStop(0, 'rgba(77, 183, 255, 1)');
gradientBlue4.addColorStop(1, 'rgba(77, 183, 255, 0.5)');

const consultDoneChart = new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Student Male',
                data: studentMaleCounts,
                backgroundColor: gradientBlue1, // Blue gradient for Student Male
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
            },
            {
                label: 'Student Female',
                data: studentFemaleCounts,
                backgroundColor: gradientBlue2, // Lighter blue for Student Female
                borderColor: 'rgba(164, 212, 231, 1)',
                borderWidth: 1,
            },
            {
                label: 'Faculty Male',
                data: facultyMaleCounts,
                backgroundColor: gradientBlue3, // Darker blue for Faculty Male
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1,
            },
            {
                label: 'Faculty Female',
                data: facultyFemaleCounts,
                backgroundColor: gradientBlue4, // Light blue for Faculty Female
                borderColor: 'rgba(77, 183, 255, 1)',
                borderWidth: 1,
            },
            {
                label: 'Cancelled Appointments',
                data: cancelledCountsForEachType,
                backgroundColor: 'rgba(255, 205, 86, 0.7)', // Yellow for Cancelled Appointments
                borderColor: 'rgba(255, 205, 86, 1)',
                borderWidth: 1,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: {
                display: true,
                text: 'Cancelled Appointments vs Consultations Done by Role, Gender, and Appointment Type',
                font: { size: 18 },
                color: '#666'
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        const dataset = tooltipItem.dataset;
                        const currentValue = dataset.data[tooltipItem.dataIndex];
                        
                        // Calculate the total value for the current bar (across all datasets)
                        let totalForBar = 0;
                        tooltipItem.chart.data.datasets.forEach(ds => {
                            totalForBar += ds.data[tooltipItem.dataIndex];
                        });
                        
                        const percentage = ((currentValue / totalForBar) * 100).toFixed(1) + '%';
                        return dataset.label + ': ' + currentValue + ' (' + percentage + ')';
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Appointments',
                    color: '#333',
                    font: { size: 14, weight: 'bold' }
                },
                grid: { color: 'rgba(200, 200, 200, 0.2)' }
            },
            y: {
                title: {
                    display: true,
                    text: 'Appointment Types & Cancelled Appointments',
                    color: '#333',
                    font: { size: 14, weight: 'bold' }
                },
                grid: { color: 'rgba(200, 200, 200, 0.2)' }
            }
        },
        elements: {
            bar: {
                borderRadius: 8, // Rounded corners
            }
        },
        animation: {
            duration: 1500, // Smooth animation for modern look
        }
    }
});

    function downloadExcel() {
    window.location.href = 'export_excel.php';
}





</script>

</body>

</html>