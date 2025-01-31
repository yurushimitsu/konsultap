<?php
require 'excelextension.php';

// Check if the user is not logged in, redirect to login page
if (!isset($_SESSION['IdNumber']) || empty($_SESSION['IdNumber'])) {
    header("Location: ../login.php");
    exit;
}

// Check if the user has completed OTP verification
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    // Redirect to OTP verification page
    header("Location: ../verification.php");
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

    <title>KonsulTap</title>
    <link rel="icon" href="images/logo_icon.png" type="image/icon type">

    <style>
        .active {
            background-color: #9191ad !important;
        }

        .text {
            color: black;
            font-weight: bold;
            font-size: 20px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- sidebar -->
            <div class="col-sm-auto sticky-top shadow p-0" style="background-color: #B1B1D0;">
                <div class="d-flex flex-sm-column flex-row flex-nowrap align-items-center sticky-top p-0" style="background-color: #B1B1D0;">
                    <span class="d-none d-sm-inline">
                        <a href="#" class="d-flex align-items-center justify-content-center px-4 py-3 mb-md-0 me-md-auto">
                            <span class="d-md-none"><img src="images/logo_icon.png" width="35px" alt=""></span>
                            <span class="d-none d-md-inline"><img src="images/logo.png" width="150" alt=""></span>
                        </a>
                    </span>
                    <hr class="text-dark d-none d-md-inline mt-0" style="height: 2px; min-width: 100%;">
                    <!-- admin profile -->
                    <div class="row px-3 text-dark">
                        <div class="col-lg-3 d-flex align-items-center justify-content-center py-3">
                            <a href="admin-profile.php">
                                <?php if (isset($fullName)) { ?>
                                    <?php
                                    // Check if profile_picture is set, otherwise use default
                                    if (!empty($profilePicture)) {
                                        echo "<img src='images/" . $profilePicture . "' width='35px' alt='' class='rounded-circle'>";
                                    } else {
                                        echo "<img src='images/default_profile_picture.jpg' width='35px' alt='' class='rounded-circle'>";
                                    }
                                    ?>
                                <?php } ?>
                            </a>

                        </div>
                        <div class="col-lg-9 text-lg-start text-md-center py-3 d-none d-md-inline">
                            <div class="row">
                                <?php if (isset($fullName)) { ?>
                                    <p class="mt-4 pb-3"><?php echo $fullName; ?></p>
                                <?php } ?>

                            </div>
                            <div class="row">
                                <h5 class="fw-bold">Administrator</h5>
                            </div>
                        </div>
                    </div>
                    <!-- sidebar nav -->
                    <ul class="nav nav-pills nav-flush flex-sm-column flex-row flex-nowrap mb-auto mb-0 px-lg-3">
                        <li class="nav-item">
                            <a href="admin/admincrud.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="fs-4 bi bi-list-task"></i> <span class="d-none d-md-inline">Manage User</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../exceluploader.php" class="nav-link py-3 fw-bold fs-5 text active">
                                <i class="fs-4 bi bi-upload"></i> <span class="d-none d-md-inline">Upload Data</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin/logout.php" class="nav-link py-3 fw-bold fs-5 text">
                                <i class="bi bi-box-arrow-left"></i> <span class="d-none d-md-inline">Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- content -->
            <main role="main" class="col-md-10">
                <div class="col position-relative px-0 min-vh-100">
                    <div class="row p-lg-5 p-3 text-lg-start text-center m-0">
                        <h1 class="col-lg-6 col-md-12">Upload Excel</h1>
                    </div>
                    <div class="col-12 px-lg-5">
                        <form id="uploadForm" action="excelextension" method="POST" enctype="multipart/form-data">
                            <div class="col-12 border border-2 border-dark rounded shadow-bottom shadow" style="min-height: 50vh;">
                                <!-- upload excel file -->
                                <div class="file-input-container p-5" style="display: flex; justify-content: center; align-items: center;">
                                    <label for="file-upload" class="custom-file-upload">
                                        <i class="bi bi-upload" style="font-size: 10rem;"></i>
                                    </label>
                                    <!-- Ensure that the name attribute is set to "import_file" -->
                                    <input id="file-upload" name="import_file" type="file" style="display:none;" onchange="updateFileName(this)" accept=".xls, .xlsx">
                                </div>
                                <!-- file name placeholder -->
                                <div class="file-name-container">
                                    <h2 id="fileNamePlaceholder" class="text-center">Upload Excel file for new students</h2>
                                </div>
                                <!-- submit button -->
                                <div class="upload-btn-container p-5">
                                    <button type="submit" class="button border-0 text-white px-5 py-2" style="background-color: #000066; border-radius: 25px;">UPLOAD</button>
                                </div>
                            </div>
                        </form>

                        <?php
                if(isset($_SESSION['message']))
                {
                    echo "<h4>".$_SESSION['message']."</h4>";
                    unset($_SESSION['message']);
                }
                ?>
                    </div>
                </div>
                <script>
                    function updateFileName(input) {
                        const fileName = input.files[0].name;
                        document.getElementById('fileNamePlaceholder').innerText = fileName;
                    }
                </script>
            </main>
        </div>
    </div>
</body>

</html>
