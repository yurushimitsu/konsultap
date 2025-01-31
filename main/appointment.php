<?php
session_start();
require '../config.php';

// Check if the user is logged in
if (!isset($_SESSION['role']) || !isset($_SESSION['IdNumber'])) {
    header("Location: ../index.php");
    exit;
}

// Check if the user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: ../verification.php"); // Redirect to verification page if not verified
    exit;
}


$role = $_SESSION['role'];
$idNumber = $_SESSION['IdNumber'];

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if preferred_time is set and not empty
    if (
        isset($_POST['date']) && !empty($_POST['date']) &&
        isset($_POST['preferred_time']) && !empty($_POST['preferred_time']) &&
        isset($_POST['appointment_type']) && !empty($_POST['appointment_type']) &&
        isset($_POST['reason']) && !empty($_POST['reason']) &&
        isset($_POST['medication']) && !empty($_POST['medication'])
    ) {
        // Retrieve the selected date from the form submission
        $date = $_POST['date'];
        $preferred_time = $_POST['preferred_time'];
        $appointment_type = $_POST['appointment_type'];
        $reason = $_POST['reason'];
        $medication = $_POST['medication'];

        // Insert into appointments table with status set to 'pending'
        $query = "INSERT INTO appointments (appointment_date, appointment_time, appointment_type, reason, medication, IdNumber, created_at, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending')";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            die("Error preparing statement: " . mysqli_error($conn));
        }

        // Bind parameters
        mysqli_stmt_bind_param($stmt, "ssssss", $date, $preferred_time, $appointment_type, $reason, $medication, $idNumber);

        // Execute statement
        if (mysqli_stmt_execute($stmt)) {
            // Insert notification
            $message = "Booked an appointment";
            $is_unread = '1';
            $conn->query("INSERT INTO `notification`(`message`, `date`, `is_unread`) VALUES ('$message','$date','$is_unread')");

            // Display success message and redirect
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <script> 
            $(this).ready(function () {
                Swal.fire({
                    icon: "success",
                    title: "Appointment Successfully Requested",
                    showCloseButton: true,
                    showConfirmButton: false
                })
            })  

            setTimeout(function name(params) {
                document.location = "appointment.php";
            }, 2000);
            </script>';
        } else {
            echo "Error inserting appointment: " . mysqli_error($conn); // Debug statement
        }

        // Close statement
        mysqli_stmt_close($stmt);
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
    <!-- <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
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

        .nav-link:hover {
            color: black !important;
            background: #0B99A7 !important;
        }

        .active {
            background: #0B99A7 !important;
        }

        .title {
            font-size: 90px;
            color: #000066;
        }

        .text-shadow {
            color: white;
            font-size: 25px;
            text-shadow: 0px 4px 4px black;
        }

        .card:hover {
            transform: scale(1.1);
        }

        .card {
            transition: transform 0.2s;
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
    <title>KonsulTap︱Appointment Scheduling</title>
    <link rel="icon" href="../images/logo_icon.png" type="image/icon type">
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
            <a class="nav-link text-light active" href="appointment.php">SCHEDULE AN <br><span class="fs-3 fw-bolder">APPOINTMENT</span></a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="consultations.php">CONSULTATIONS</a>
            <a class="nav-link text-light d-flex align-items-center justify-content-center py-4 fs-5 fw-bold" href="records-appointments.php">RECORDS</a>
        </nav>
    </div>

    <!-- body -->
    <div class="container d-flex align-items-center justify-content-center">
        <div class="col-lg-12 text-center py-4 ">
            <h1 class="pb-2">Set Appointment Schedule</h1>
            <!-- calendar -->
            <div class="bg-light p-4 px-lg-5 pb-lg-5 pt-lg-4" style="border-radius: 25px; min-height: 45rem;">
                <!-- color legend -->
                <span class="">
                    <span class="color-box d-inline-block" style="background-color: #000066;"></span><span class="ps-1 pe-3">Appointment</span>
                    <span class="color-box d-inline-block" style="background-color: #FFA500;"></span><span class="ps-1 pe-3">Pending</span>
                    <span class="color-box d-inline-block" style="background-color: #76a6e5;"></span><span class="ps-1">Event</span>
                </span>
                <div class="d-flex justify-content-center bg-light pt-4 pt-lg-0" style="border-radius: 25px; min-height: 45rem;" id="calendar"></div>
            </div>
        </div>
    </div>
</body>

</html>

<script>
     
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var isMobile = window.innerWidth <= 768; // Responsive header toolbar
        var headerToolbar = {
            left: 'title',
            right: (isMobile ? 'prev next' : 'prev today next'),
        };
        var calendar = new FullCalendar.Calendar(calendarEl, {
            // calendar settings
            initialView: 'dayGridMonth',
            themeSystem: 'bootstrap5',
            timezone: 'Asia/Manila',
            showNonCurrentDates: false,
            dayHeaders: true,
            dayHeaderFormat: {
                weekday: 'short'
            },
            weekends: false,
            selectable: true,
            selectHelper: true,
            fixedWeekCount: false,
            headerToolbar: headerToolbar,
            // selectable range to only 1 month
            validRange: function(nowDate) {
                var endDay = nowDate.setMonth(nowDate.getMonth() + 12);
                var today = nowDate.setMonth(nowDate.getMonth() - 12);

                return {
                    start: today,
                    end: endDay,
                };
            },
            <!-- Displaying events in FullCalendar -->


          events: [
    <?php
    // Array to store events grouped by date
    $events_by_date = array();

    // Fetch events with status 'event' for all users
    $event_result = $conn->query("SELECT * FROM appointments WHERE status = 'event'");

    // Store dates with 'event' status in an array
    $dates_with_event = array();
    while ($event_row = mysqli_fetch_assoc($event_result)) {
        $date = $event_row['appointment_date'];
        $events_by_date[$date][] = array(
            'title' => 'Event: ' . $event_row['reason'] . ' on ' . (new DateTime($event_row['appointment_date']))->format('F j, Y'),
            'start' => $event_row['appointment_date'],
            'status' => 'event', // Set status to 'event'
            'color' => '#76a6e5',
            'id' => $event_row['id'], // Add appointment ID here
            'extendedProps' => array(
                'appointment_date' => (new DateTime($event_row['appointment_date']))->format('F j, Y'),
                'appointment_type' => $event_row['appointment_type'],
                'reason' => $event_row['reason'],
                'medication' => $event_row['medication'],
                'appointment_time' => 'Whole Day' // Include appointment_time
            )
        );
        $dates_with_event[$date] = true;
    }

    // Fetch events with status 'accept' for all users
    $accept_result = $conn->query("SELECT * FROM appointments WHERE status = 'accept' AND IdNumber = '$idNumber'");

    while ($row = mysqli_fetch_assoc($accept_result)) {
        $date = $row['appointment_date'];

        // Only add 'accept' events if no 'event' exists on the same date
        if (!isset($dates_with_event[$date])) {
            // Determine color based on appointment type
            $color = ($row['appointment_type'] == 'online' || $row['appointment_type'] == 'onsite') ? '#000066' : '#76a6e5';

            // Add event to the array
            $events_by_date[$date][] = array(
                'title' => 'Appointment on: ' . (new DateTime($row['appointment_time']))->format('g:i A') . ' on ' . (new DateTime($row['appointment_date']))->format('F j, Y'),
                'start' => $row['appointment_date'],
                'status' => 'accept',
                'color' => $color,
                'id' => $row['id'], // Add appointment ID here
                'extendedProps' => array(
                    'appointment_date' => (new DateTime($row['appointment_date']))->format('F j, Y'),
                    'appointment_type' => $row['appointment_type'],
                    'reason' => $row['reason'],
                    'medication' => $row['medication'],
                    'appointment_time' => (new DateTime($row['appointment_time']))->format('g:i A') // Include appointment_time
                )
            );
        }
    }

    // Fetch events depending on status for the logged-in user
    $idNumber = mysqli_real_escape_string($conn, $idNumber);
    $pending_result = $conn->query("SELECT * FROM appointments WHERE status = 'pending' AND IdNumber = '$idNumber'");

    while ($row = mysqli_fetch_assoc($pending_result)) {
        $date = $row['appointment_date'];

        // Only add 'pending' events if no 'event' exists on the same date
        if (!isset($dates_with_event[$date])) {
            // Determine color based on appointment type
            $color = ($row['appointment_type'] == 'online' || $row['appointment_type'] == 'onsite') ? '#FFA500' : '#76a6e5';

            // Add event to the array
            $events_by_date[$date][] = array(
                'title' => 'PENDING: ' . $row['reason'] . ' on ' . (new DateTime($row['appointment_date']))->format('F j, Y'),
                'start' => $row['appointment_date'],
                'status' => 'pending',
                'color' => $color,
                'id' => $row['id'], // Add appointment ID here
                'extendedProps' => array(
                    'appointment_type' => $row['appointment_type'],
                    'reason' => $row['reason'],
                    'medication' => $row['medication'],
                    'appointment_time' => (new DateTime($row['appointment_time']))->format('g:i A') // Include appointment_time
                )
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




// Handle event click (including delete action for pending appointments)
// Handle event click (including delete action for pending appointments)
eventClick: function(info) {
    $(document).ready(function() {
        // Check if the event status is 'pending' for delete action
        if (info.event.extendedProps.status === 'pending') {
            Swal.fire({
                title: info.event.title,
                text: 'Do you want to cancel this appointment?',
                icon: 'warning',
                input: "textarea",
                inputLabel: "Reason",
                inputPlaceholder: "Type your reason here...",
                inputAttributes: {
                    "aria-label": "Type your message here",
                    "maxlength": "200"
                },
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Close',
                confirmButtonText: 'Yes, proceed',
                didOpen: () => {
                    const input = Swal.getInput();
                    const counter = document.createElement('div');
                    counter.id = 'charCounter';
                    counter.style.position = 'absolute';
                    counter.style.bottom = '5px';  // Position it at the bottom of the textarea
                    counter.style.right = '10px';  // Right-aligned
                    counter.style.fontSize = '0.8em';
                    counter.style.color = 'gray';
                    counter.style.zIndex = '10';  // Ensure it’s on top of the text
                    counter.textContent = `0 / 200 characters`;

                    // Append counter to the input container (the textarea)
                    input.parentNode.style.position = 'relative';  // Ensure container is positioned relative
                    input.parentNode.appendChild(counter);

                    // Add an input event listener to update the counter in real-time
                    input.addEventListener('input', () => {
                        counter.textContent = `${input.value.length} / 200 characters`;
                    });
                },
                preConfirm: () => {
                    const inputValue = Swal.getInput().value;
                    if (!inputValue) {
                        Swal.showValidationMessage('Please enter a reason');
                        return false;
                    }
                    if (inputValue.length > 200) {
                        Swal.showValidationMessage('Reason must be 200 characters or less');
                        return false;
                    }
                    return inputValue;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form to delete_appointment.php
                    var form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'delete_appointment';
                    
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

                    // Submit form after 2 seconds to show alert
                    setTimeout(() => {
                        form.submit(); 
                    }, 2000);
                }
            });
        } else {
            // Display consultation details
            Swal.fire({
                title: 'Consultation Details',
                html: '<b>Reason:</b> ' + info.event.extendedProps.reason + '<br><b>Date:</b> ' + info.event.extendedProps.appointment_date + '<br><b>Time:</b> ' + info.event.extendedProps.appointment_time + '<br><b>Medication:</b> ' + info.event.extendedProps.medication
                + '<br><br><span class="fs-6 fw-lighter fst-italic">If you want to cancel the appointment, kindly reach out to the medical email.</span>',
                icon: 'info',
                confirmButtonColor: '#000066'
            });
        }
    });
},



            dayRender: function(date, cell) {
                var clickedDate = date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);
                var isEventDay = false;

                // Loop through the events array to check if the clicked date has an event with status 'event'
                calendar.getEvents().forEach(function(event) {
                    if (event.startStr === clickedDate && event.extendedProps.status === 'event') {
                        isEventDay = true;
                    }
                });

                if (isEventDay) {
                    // If the status is 'event', disable clicking for the day
                    $(cell).addClass('fc-day-disabled');
                }
            },
            
            // onclick function
          // onclick function
          dateClick: function(info) {
    var date = info.dateStr;
    var isEventDay = false;

    // Check if the clicked date has the status 'event'
    calendar.getEvents().forEach(function(event) {
        if (event.startStr === date && event.extendedProps.status === 'event') {
            isEventDay = true;
        }
    });

    if (isEventDay) {
        // If the status is 'event', show a message indicating that the date is not available
        Swal.fire({
            icon: 'info',
            title: 'This date is not available for appointment scheduling.',
            text: 'Please choose another date.',
            confirmButtonColor: "#000066",
        });
    } else {
        // AJAX request to fetch booked times for the selected date
        $.ajax({
            url: 'fetch_booked_times',
            type: 'POST',
            data: { date: date },
            dataType: 'json',
            success: function(response) {
                // Array of time slots
                var timeSlots = [
                    { value: '08:00:00', label: '8:00AM - 9:00AM' },
                    { value: '09:00:00', label: '9:00AM - 10:00AM' },
                    { value: '10:00:00', label: '10:00AM - 11:00AM' },
                    { value: '11:00:00', label: '11:00AM - 12:00PM' },
                    { value: '13:00:00', label: '1:00PM - 2:00PM' },
                    { value: '14:00:00', label: '2:00PM - 3:00PM' },
                    { value: '15:00:00', label: '3:00PM - 4:00PM' },
                    { value: '16:00:00', label: '4:00PM - 5:00PM' }
                ];

                // Extract booked times into an array
                var bookedTimes = response.map(function(bookedTime) {
                    return bookedTime.appointment_time;
                });

                // Check if all time slots are booked
                var allBooked = timeSlots.every(function(slot) {
                    return bookedTimes.includes(slot.value);
                });

                if (allBooked) {
                    // All time slots are booked, show error message or handle accordingly
                    Swal.fire({
                        icon: 'error',
                        title: 'All time slots are booked',
                        text: 'Please select another date.',
                        confirmButtonColor: "#000066",
                    });
                } else {
                    // Enable all options initially
                    $('#preferred_time').empty(); // Clear existing options

                    // Loop through timeSlots array to create options
                    timeSlots.forEach(function(slot) {
                        var option = $('<option>', {
                            value: slot.value,
                            text: slot.label
                        });

                        // Disable option if it matches a booked time
                        if (bookedTimes.includes(slot.value)) {
                            option.prop('disabled', true);
                        }

                        // Append option to select element
                        $('#preferred_time').append(option);
                    });

                    // Update modal and show it
                    $('#modalTitle').html('Set an appointment on: ' + date);
                    $('#modal').modal('show');
                    $('#date').val(date);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error: ' + status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error fetching booked times',
                    text: 'Please try again later.',
                    confirmButtonColor: "#000066",
                });
            }
        });
    }
},


        // Other FullCalendar configurations...
    });

    calendar.render();

    // Function to format time to match options' value format (e.g., '08:00' => '8:00')
});
</script>



<!-- Modal -->
<form action="appointment" method="post">
    <div class="modal fade" id="modal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title fw-lighter fst-italic" id="modalIitle">Note: Only clickable times are available</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- selected date read only -->
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" name="date" id="date" value="" placeholder="" readonly>
                                <label for="date" class="form-label">Date Selected<i class="text-danger">*</i></label>
                            </div>
                        </div>
                        <!-- preferred time input -->
                        <!-- preferred time input -->
                        <div class="col-6">
                        <div class="form-floating mb-3">
                        <select class="form-select" id="preferred_time" name="preferred_time" required>
                            <option value="">Preferred Time</option>
                        </select>
                        <label for="preferred_time">Select Time <i class="text-danger">*</i></label>
                        <small>Clinic Hours: 8:00 AM To 5:00 PM</small>
                    </div>
                    </div>


                        <!-- appointment type input -->
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="type" name="appointment_type" required>
                                    <option value="">Appointment Type</option>
                                    <option value="online">Online</option>
                                    <option value="onsite">Onsite</option>
                                </select>
                                <label for="type">Select Appointment Type <i class="text-danger">*</i></label>
                            </div>
                        </div>
                        <!-- reasons textarea input -->
                        <div class="col-lg-12">
    <div class="form-floating mb-3" style="position: relative;">
        <textarea class="form-control pt-5 pt-lg-4" style="height: 15vh; padding-bottom: 30px;" name="reason" id="reasons" rows="3" maxlength="200" required oninput="updateCounter('reasons', 'reasonCount')"></textarea>
        <label for="reasons" class="form-label">What is/are your reasons for setting a consultation? <i class="text-danger">*</i></label>
        <small id="reasonCount" style="position: absolute; bottom: 10px; right: 10px; background: white; padding: 2px 5px; color: gray; font-size: 0.9em;">0 / 200 characters</small>
    </div>
</div>

<!-- Current medication textarea input -->
<div class="col-lg-12">
    <div class="form-floating mb-3" style="position: relative;">
        <textarea class="form-control pt-5 pt-lg-4" style="height: 15vh; padding-bottom: 30px;" name="medication" id="currentMed" rows="3" maxlength="200" required oninput="updateCounter('currentMed', 'medicationCount')"></textarea>
        <label for="currentMed" class="form-label">Are you currently taking any medicine? If yes, kindly state</label>
        <small id="medicationCount" style="position: absolute; bottom: 10px; right: 10px; background: white; padding: 2px 5px; color: gray; font-size: 0.9em;">0 / 200 characters</small>
    </div>
</div>

<script>
    function updateCounter(textareaId, counterId) {
        const textarea = document.getElementById(textareaId);
        const counter = document.getElementById(counterId);
        counter.textContent = `${textarea.value.length} / 200 characters`;
    }
</script>

                    </div>
                </div>
                <div class="modal-footer">
                    <!-- cancel button -->
                    <button type="button" class="btn text-light" style="background-color: #76a6e5;" data-bs-dismiss="modal">Cancel</button>
                    <!-- submit button -->
                    <button type="submit" name="submit" id="btnsubmit" class="btn text-light" style="background-color: #000066;" href="javascript:accept();">Submit</button>
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
</form>
