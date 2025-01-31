<nav class="navbar navbar-expand-lg navbar-dark shadow-5-strong" style="background-color: rgba(105, 194, 246, 0.6);">
    <div class="container-fluid">
        <!-- logo -->
        <a class="navbar-brand ps-lg-4" href="dashboard.php">
            <img class="border-end border-1 border-dark" src="../images/lics-logo.png" alt="" width="40">
            <img src="../images/logomain.png" alt="" width="150" height="24">
        </a>
        <!-- profile -->
        <div class="">
            <a href="user-notifications.php" class="btn text-light rounded-circle position-relative" style="background-color: #000066;">
                <i class="bi bi-bell-fill"></i>

                <?php
                    $notifCtr = $conn->query("SELECT * FROM appointments WHERE is_unreadusers = 1 AND (status = 'accept' OR status = 'denied') AND IdNumber = $idNumber"); // select all notifcations
                    $ctr = mysqli_num_rows($notifCtr); // count all the notification
                    $notifRow = mysqli_fetch_assoc($notifCtr); // fetch all notification
                ?>
                <?php if (!empty($ctr)) {?>
                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill counter"><?php echo $ctr?></span>
                <?php } ?>
            </a>
            <a href="profile.php" class="justify-content-center align-items-center"><img src="../images/<?php echo $profilePicture; ?>" style="object-fit: cover;" width="39px" height="39px" alt="" class="rounded-circle"></a>
            <a href="logout.php" class="btn text-light rounded-circle" style="background-color: #000066;"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>