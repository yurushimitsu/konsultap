<?php
// fetch_booked_times.php
require '../config.php';

// Fetch fully booked dates
$bookedDatesQuery = "
    SELECT appointment_date
    FROM appointments
    WHERE status = 'accept'
    GROUP BY appointment_date
    HAVING COUNT(DISTINCT appointment_time) = 8";
$bookedDatesResult = mysqli_query($conn, $bookedDatesQuery);

$fullyBookedDates = [];
while ($row = mysqli_fetch_assoc($bookedDatesResult)) {
    $fullyBookedDates[] = $row['appointment_date'];
}
?>

<script>
    var fullyBookedDates = <?php echo json_encode($fullyBookedDates); ?>;
</script>

?>