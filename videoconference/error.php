<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if the user is logged in and has one of the allowed roles
if (!isset($_SESSION['IdNumber']) || empty($_SESSION['IdNumber']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['medprac', 'faculty', 'student'])) {
    echo "Invalid role or not logged in.";
    exit;
}

// Custom error message for exceeding participant limit
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Access Denied</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            padding: 50px;
        }
        h1 {
            color: #ff0000;
        }
        p {
            font-size: 18px;
            color: #555;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Access Denied</h1>
        <p>Sorry, the conference room is full. Only two participants are allowed in this video conference.</p>
        <p>If you're trying to rejoin, please close any other tabs or devices where the video conference is open.</p>
    </div>
</body>
</html>";
?>
