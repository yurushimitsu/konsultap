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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if event data is set and not empty
    if (
        isset($_POST['event_name']) && !empty($_POST['event_name']) &&
        isset($_POST['event_description']) && !empty($_POST['event_description']) &&
        isset($_POST['event_type']) && !empty($_POST['event_type']) &&
        isset($_POST['event_date']) && !empty($_POST['event_date'])
    ) {
        // Get the input data from the form
        $event_name = $_POST['event_name'];
        $event_description = $_POST['event_description']; // Make sure this matches the form field
        $event_type = $_POST['event_type'];
        $event_date = $_POST['event_date'];

        // Update existing appointments on the same date to 'denied'
        $conn->query("UPDATE appointments SET status = 'denied' WHERE appointment_date = '$event_date'");

        // Check if the inputted event is a duplicate
        $result = $conn->query("SELECT * FROM appointments WHERE reason = '$event_name' AND appointment_date = '$event_date'");
        $num_rows = mysqli_num_rows($result);

        if ($num_rows > 0) {
            // If the slot is not available, display a warning message and redirect
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
                <script> 
                $(this).ready(function () {
                    Swal.fire({
                        icon: "warning",
                        title: "There is already an event",
                        showCloseButton: true,
                        showConfirmButton: false
                    })
                })  
    
                setTimeout(function () {
                    document.location = "staff-calendar";
                }, 2000);
                </script>';
        } else {
            // Insert the new event into the appointments table with is_unread set to 0 and is_unreadusers set to 1
            $conn->query("INSERT INTO appointments (appointment_date, appointment_type, reason, description, medication, created_at, status, IdNumber, is_unread, is_unreadusers) VALUES ('$event_date', '$event_type', '$event_name', '$event_description', 'N/A', NOW(), 'event', '$idNumber', 0, 1)");

            // Update notifications for the same date
            $conn->query("UPDATE appointments SET is_unread = 0, is_unreadusers = 1 WHERE DATE(appointment_date) = DATE('$event_date')");

            // Display success message and redirect
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
                <script> 
                $(this).ready(function () {
                    Swal.fire({
                        icon: "success",
                        title: "Event Successfully Saved",
                        showCloseButton: true,
                        showConfirmButton: false
                    })
                })  
    
                setTimeout(function () {
                    document.location = "staff-calendar";
                }, 2000);
                </script>';
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- sweetalert -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- timeout -->
    <script src="../timeout.js"></script> 

    <!-- full calendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

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

        .nav-center {
            display: flex;
            align-items: center; /* Vertically centers the items */
        }

        .nav-center .bi {
            margin-right: 10px; /* Adjust space between icon and text */
        }
        
        .color-box {
            width: 15px; 
            height: 15px;
            border-radius: 50%;
        } 
        
        @media (max-width: 768px) {
            .fc-toolbar h2 {
                font-size: 20px !important; /* Adjust the size as needed */
            }

            .fc-toolbar button {
                font-size: 14px !important; /* Adjust the button font size as needed */
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
                            <a href="staff-calendar.php" class="nav-link py-3 fw-bold fs-5 text active">
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
            <div class="col position-relative p-lg-3 pt-3 min-vh-100">
                <div class="container px-lg-2 px-0">
                     <!-- calendar -->
                    <div class="text-center bg-light p-4 px-lg-5 pb-lg-5 pt-lg-4" style="border-radius: 25px; min-height: 45rem;">
                        <!-- color legend -->
                        <span class="text-center text-lg-start">
                            <span class="color-box d-inline-block" style="background-color: #000066;"></span><span class="ps-1 pe-3">Appointment</span>
                            <span class="color-box d-inline-block" style="background-color: #FFA500;"></span><span class="ps-1 pe-3">Pending</span>
                            <span class="color-box d-inline-block" style="background-color: #76a6e5;"></span><span class="ps-1">Event</span>
                        </span>
                        <div class="d-flex justify-content-center bg-light pt-4 pt-lg-0" style="border-radius: 25px; min-height: 45rem;" id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

<!-- script for fullcalendar api -> automatic calendar -->
<script>
   document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            themeSystem: 'bootstrap5',
            timezone: 'Asia/Manila',
            showNonCurrentDates: false,
            dayHeaders: true,
            selectable: true,
            selectHelper: true,
            fixedWeekCount: false,
            weekends: false, // Disable Saturday and Sunday
            events: [
                <?php
                // Array to store events grouped by date
                $events_by_date = array();

                // Fetch events with status 'event' from the database
                $event_result = $conn->query("SELECT appointments.*, IFNULL(students.fullName, faculty.fullName) AS fullName FROM appointments LEFT JOIN students ON appointments.IdNumber = students.IdNumber LEFT JOIN faculty ON appointments.IdNumber = faculty.IdNumber WHERE appointments.status = 'event'");

                // Store dates with 'event' status in an array
                $dates_with_event = array();
                while ($event_row = mysqli_fetch_assoc($event_result)) {
                    $date = $event_row['appointment_date'];
                    $events_by_date[$date][] = array(
                        'id' => $event_row['id'], // Assuming 'id' is available in the database
                        'start' => $event_row['appointment_date'],
                        'title' => 'Event: ' . $event_row['reason'] . ' On ' . $event_row['appointment_date'],
                        'color' => '#76a6e5'
                    );
                    $dates_with_event[$date] = true;
                }

                // Fetch events with status 'accept' or 'pending' from the database
                $accept_pending_result = $conn->query("SELECT appointments.*, IFNULL(students.fullName, faculty.fullName) AS fullName FROM appointments LEFT JOIN students ON appointments.IdNumber = students.IdNumber LEFT JOIN faculty ON appointments.IdNumber = faculty.IdNumber WHERE appointments.status IN ('accept', 'pending')");
                while ($row = mysqli_fetch_assoc($accept_pending_result)) {
                    $date = $row['appointment_date'];
                    $status = $row['status'];
                    $start_time = date('h:i a', strtotime($row['appointment_time']));
                    
                    // Calculate end time assuming 1-hour duration
                    $end_time = date('h:i a', strtotime($row['appointment_time'] . ' +1 hour'));

                    // Initialize event array for this date if not exists
                    if (!isset($events_by_date[$date])) {
                        $events_by_date[$date] = array();
                    }

                    // Check if the date already has 'event' status, if not add 'accept' or 'pending'
                    if (!isset($dates_with_event[$date])) {
                        // Determine title and color based on status
                        $title = '';
                        $color = '';
                        if ($status == 'accept') {
                            $title = 'Appointment For: ' . $row['fullName'] . ' on ' . $start_time . ' - ' . $end_time;
                            $color = '#000066';
                        } elseif ($status == 'pending') {
                            $title = 'Pending Appointment On ' . $row['appointment_date'];
                            $color = '#FFA500';
                        }

                        // Add event to the array
                        $events_by_date[$date][] = array(
                            'id' => $row['id'], // Assuming 'id' is available in the database
                            'start' => $row['appointment_date'],
                            'title' => $title,
                            'color' => $color
                        );
                    }
                }

                // Output events grouped by date
                foreach ($events_by_date as $date => $events) {
                    foreach ($events as $event) {
                        echo json_encode($event) . ",\n";
                    }
                }
                ?>
            ],

            eventClick: function(info) {
    $(document).ready(function() {
        // Check if the clicked event is of status 'event'
        if (info.event.title.startsWith('Event:')) {
            // Show event details without delete option
            Swal.fire({
                icon: 'info',
                title: info.event.title,
                confirmButtonText: 'Close'
            });
        } else {
            // Show confirmation dialog for appointments with delete option
            Swal.fire({
                title: info.event.title,
                text: 'Do you want to cancel this appointment?',
                icon: 'warning',
                input: "textarea",
                inputLabel: "Reason for Cancellation",
                inputPlaceholder: "Type your reason here...",
                inputAttributes: {
                    "aria-label": "Type your message here"
                },
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed',
                preConfirm: () => {
                    const inputValue = Swal.getInput().value;
                    if (!inputValue) {
                        Swal.showValidationMessage('Please enter a reason');
                        return false;
                    }
                    return inputValue;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a form to submit the data
                    var form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'delete_appointmentmed'; // Change the action to your new script

                    // Appointment ID input
                    var inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id';
                    inputId.value = info.event.id; // Use appointment ID from FullCalendar

                    // Reason input
                    var inputReason = document.createElement('input');
                    inputReason.type = 'hidden';
                    inputReason.name = 'reason';
                    inputReason.value = result.value; // Pass the reason to the server

                    // ID Number input
                    var inputIdNumber = document.createElement('input');
                    inputIdNumber.type = 'hidden';
                    inputIdNumber.name = 'idNumber';
                    inputIdNumber.value = "<?php echo $idNumber; ?>"; 

                    // Append inputs to the form
                    form.appendChild(inputId);
                    form.appendChild(inputReason);
                    form.appendChild(inputIdNumber);

                    document.body.appendChild(form);

                    // Show success alert after the form submission
                    Swal.fire({
                        icon: "success",
                        title: "Appointment Successfully Deleted",
                        showCloseButton: true,
                        showConfirmButton: false
                    });

                    // Submit form after 3 seconds to show alert
                    setTimeout(() => {
                        form.submit(); 
                    }, 2000);
                }
            });
        }
    });
},



            // Date click functionality
            dateClick: function(info) {
                var date = calendar.formatDate(info.dateStr, {
                    month: 'long',
                    year: 'numeric',
                    day: 'numeric'
                });
                document.getElementById("modalIitle").innerHTML = 'Set Event On: ' + date;
                $('#modal').modal('toggle');
                document.getElementById("event_date").value = info.dateStr;
            }
        });
        calendar.render();
    });
</script>


<!-- Modal -->
<form action="staff-calendar" method="post">
    <div class="modal fade" id="modal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalIitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row pb-3">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="event_name">Event name <i class="text-danger">*</i></label>
                                <input type="text" name="event_name" id="event_name" class="form-control" placeholder="Enter your event name">
                                <label for="event_description">Event Description <i class="text-danger">*</i></label>
                                <input type="text" name="event_description" id="event_description" class="form-control" placeholder="Enter event description">
                            </div>
                        </div>
                    </div>
                    <div class="row pb-3">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="event_type">Select Event Type <i class="text-danger">*</i></label>
                                <select class="form-select" id="event_type" name="event_type" required>
                                    <option value="">Event Type</option>
                                    <option value="Leave">Leave</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="event_date">Event Date <i class="text-danger">*</i></label>
                                <input type="date" name="event_date" id="event_date" class="form-control onlydatepicker" placeholder="Event Date" required>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">

                    <button type="button" class="btn text-light" style="background-color: #76a6e5;" data-bs-dismiss="modal">Cancel</button>

                    <button type="submit" name="submit" id="btnsubmit" class="btn text-light" style="background-color: #000066;">Save Event</button>
                </div>
            </div>
        </div>
    </div>
</form>
