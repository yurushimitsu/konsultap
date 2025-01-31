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

// Check if IdNumber parameter is set in the URL
if (isset($_GET['IdNumber'])) {
    // Retrieve the IdNumber to be reset from the URL
    $IdNumber = $_GET['IdNumber'];

    // Define an array of table names
    $tables = ['students', 'faculty', 'medprac', 'itc'];

    // Iterate through each table to find the user
    foreach ($tables as $table) {
        // Prepare and execute the SQL query to check if IdNumber exists in the current table
        $query = "SELECT IdNumber FROM $table WHERE IdNumber = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $IdNumber);
        $stmt->execute();
        $stmt->store_result();

        // If the IdNumber exists in the current table, update the password and redirect
        if ($stmt->num_rows > 0) {
            $stmt->close();

            // Prepare and execute the SQL query to update the password to default (IdNumber)
            $query = "UPDATE $table SET password = ? WHERE IdNumber = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $IdNumber, $IdNumber); // Set password as IdNumber
            $stmt->execute();

            // Redirect to the admin CRUD page after updating
            header("Location: admincrud.php");
            exit();
        }
        $stmt->close();
    }

    // If IdNumber is not found in any table, display an error message
    echo "IdNumber not found";
    exit();
} else {
    // If IdNumber parameter is missing, display an error message and exit
    echo "IdNumber parameter missing";
    exit();
}
?>
