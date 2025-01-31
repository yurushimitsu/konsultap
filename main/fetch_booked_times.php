<?php
// Include your database configuration file
require '../config.php';

// Check if the date parameter is set
if (isset($_POST['date'])) {
    $date = $_POST['date'];

    // Prepare the query to fetch booked times for the selected date with status 'accept'
    $query = "SELECT appointment_time, status FROM appointments WHERE appointment_date = ? AND status = 'accept'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Fetch results into an array
    $booked_times = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $booked_times[] = $row;
    }

    // Close statement and database connection
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    // Output booked times as JSON
    echo json_encode($booked_times);
} else {
    // Return an error response if date parameter is not provided
    http_response_code(400); // Bad request
    echo json_encode(array('error' => 'Date parameter is missing'));
}
?>
