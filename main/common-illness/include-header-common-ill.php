<nav class="navbar navbar-expand-lg navbar-dark shadow-5-strong" style="background-color: rgba(105, 194, 246, 0.6);">
    <div class="container-fluid">
        <!-- logo -->
        <a class="navbar-brand ps-4" href="dashboard.php">
            <img src="../../images/logomain.png" alt="" width="150" height="24">
        </a>
        <!-- profile -->
        <div class="">
            <a href="../user-notifications.php" class="btn text-light rounded-circle position-relative" style="background-color: #000066;">
                <i class="bi bi-bell-fill"></i>

                <?php
                    $notifCtr = $conn->query("SELECT * FROM appointments WHERE is_unread = 1 AND IdNumber = $idNumber"); // select all notifcations
                    $ctr = mysqli_num_rows($notifCtr); // count all the notification
                    $notifRow = mysqli_fetch_assoc($notifCtr); // fetch all notification
                ?>
                <?php if (!empty($ctr)) {?>
                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill counter"><?php echo $ctr?></span>
                <?php } ?>
            </a>
            <a href="../profile.php"><img src="../../images/<?php echo $profilePicture; ?>" width="45px" alt="" class="rounded-circle px-1"></a>
            <a href="../logout.php" class="btn text-light rounded-circle" style="background-color: #000066;"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>