<?php
session_start();
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $videoconference_id = $_POST['videoconference_id'];
    $duration = $_POST['duration'];
    
    // Assuming you have a table `video_calls` with columns for id, duration, and videoconference_id
    $sql = "UPDATE videoconference SET call_duration = ? WHERE videoconference_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $duration, $videoconference_id);
    
    if ($stmt->execute()) {
        echo "Call duration saved successfully";
    } else {
        echo "Error saving call duration: " . $stmt->error;
    }
}
?>
