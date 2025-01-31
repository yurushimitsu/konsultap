<?php
require '../config.php'; 
session_start();
session_destroy(); // Destroy all session data

// Redirect user to login page
header('Location: ../index.php');
exit();
?>