<?php
session_start();
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['videoconference_id']) && isset($_POST['action']) && $_POST['action'] === 'decrement') {
        $videoconference_id = $_POST['videoconference_id'];

        // Decrement the active participants count
        $query = "UPDATE videoconference SET active_participants = active_participants - 1 WHERE videoconference_id = ? AND active_participants > 0";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $videoconference_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "Participant count updated successfully";
        } else {
            echo "Failed to update participant count";
        }
    }
}
?>
