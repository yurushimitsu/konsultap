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

// Check if IdNumber is provided
if (isset($_GET['IdNumber'])) {
    $IdNumber = mysqli_real_escape_string($conn, $_GET['IdNumber']);

    // Define the tables to check
    $tables = ['students', 'faculty', 'medprac', 'itc'];
    $found = false;

    foreach ($tables as $table) {
        // Check if the IdNumber exists in the current table
        $query = "SELECT IdNumber FROM $table WHERE IdNumber = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $IdNumber);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            // Update the status column to activate the user
            $updateQuery = "UPDATE $table SET status = 'active', disabled_at = NULL WHERE IdNumber = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "s", $IdNumber);
            mysqli_stmt_execute($updateStmt);

            // Check if the update was successful
            if (mysqli_stmt_affected_rows($updateStmt) > 0) {
                echo "<script>alert('User with ID $IdNumber has been activated successfully.');</script>";
            } else {
                echo "<script>alert('Failed to activate the user. Please try again.');</script>";
            }

            $found = true;
            break;
        }
    }

    if (!$found) {
        echo "<script>alert('User with ID $IdNumber not found.');</script>";
    }

    // Redirect back to the admincrud.php after the alert
    echo "<script>window.location.href = 'admincrud.php';</script>";
    exit;
} else {
    echo "<script>alert('No ID number provided.'); window.location.href = 'admincrud.php';</script>";
    exit;
}
?>
