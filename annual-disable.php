<?php 
require 'config.php';

// // Array of table names
$tables = ['students', 'faculty', 'medprac'];

// Loop through each table and disable active users
foreach ($tables as $table) {
    $disableQuery = "UPDATE $table SET status = 'disabled', disabled_at = CURDATE() WHERE status = 'active'";
    mysqli_query($conn, $disableQuery);
}

// $conn->query("UPDATE `students` SET `Gender` = 'test' WHERE `students`.`id` = 9");
echo 'successfully disabled all active users';
?>