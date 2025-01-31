<?php
session_start();
require '../config.php';

// Get the videoconference ID from the request
$videoconference_id = $_GET['videoconference_id'];

// Query to get the number of participants
$query = "SELECT active_participants FROM videoconference WHERE videoconference_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $videoconference_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $active_participants);
mysqli_stmt_fetch($stmt);

// Close the statement and connection
mysqli_stmt_close($stmt);
mysqli_close($conn);

// Return the active participants count as JSON
echo json_encode(['participantCount' => (int)$active_participants]);
?>
