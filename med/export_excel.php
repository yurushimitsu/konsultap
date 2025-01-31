<?php
require '../config.php'; // Database connection

// Set headers for Excel file download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="appointment_data_by_gender.xls"');
header('Cache-Control: max-age=0');

// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Initialize gender consultation counts
$studentMaleConsultations = 0;
$studentFemaleConsultations = 0;
$facultyMaleConsultations = 0;
$facultyFemaleConsultations = 0;

// Fetch the top 5 appointment reasons excluding "leave" and "others"
$query = "
    SELECT reason, IdNumber, COUNT(*) as count
    FROM appointments
    WHERE reason NOT IN ('leave', 'event')
    AND appointment_type NOT IN ('others', 'leave')
    AND MONTH(appointment_date) = ? 
    AND YEAR(appointment_date) = ?
    GROUP BY reason, IdNumber
    ORDER BY count DESC
    ";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $currentMonth, $currentYear);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Write headers for the Excel file
echo "Appointment Reasons (By Gender)\n";
echo "Reason\tCount\tMale (Students)\tFemale (Students)\tMale (Faculty)\tFemale (Faculty)\n";

// Check each IdNumber to see if it's in the students or faculty table and get gender
while ($row = mysqli_fetch_assoc($result)) {
    $idNumber = $row['IdNumber'];
    $reason = $row['reason'];
    $count = $row['count'];

    // Reset gender counts for each reason
    $maleStudents = 0;
    $femaleStudents = 0;
    $maleFaculty = 0;
    $femaleFaculty = 0;

    // Check if IdNumber is in the students table and fetch gender
    $queryStudent = "SELECT Gender FROM students WHERE IdNumber = ?";
    $stmtStudent = mysqli_prepare($conn, $queryStudent);
    mysqli_stmt_bind_param($stmtStudent, "s", $idNumber);
    mysqli_stmt_execute($stmtStudent);
    $resultStudent = mysqli_stmt_get_result($stmtStudent);
    $rowStudent = mysqli_fetch_assoc($resultStudent);

    if ($rowStudent) {
        // Increment student consultations based on gender
        if ($rowStudent['Gender'] === 'Male') {
            $maleStudents++;
            $studentMaleConsultations++;
        } elseif ($rowStudent['Gender'] === 'Female') {
            $femaleStudents++;
            $studentFemaleConsultations++;
        }
    } else {
        // If not found in students, check in faculty and fetch gender
        $queryFaculty = "SELECT Gender FROM faculty WHERE IdNumber = ?";
        $stmtFaculty = mysqli_prepare($conn, $queryFaculty);
        mysqli_stmt_bind_param($stmtFaculty, "s", $idNumber);
        mysqli_stmt_execute($stmtFaculty);
        $resultFaculty = mysqli_stmt_get_result($stmtFaculty);
        $rowFaculty = mysqli_fetch_assoc($resultFaculty);

        if ($rowFaculty) {
            // Increment faculty consultations based on gender
            if ($rowFaculty['Gender'] === 'Male') {
                $maleFaculty++;
                $facultyMaleConsultations++;
            } elseif ($rowFaculty['Gender'] === 'Female') {
                $femaleFaculty++;
                $facultyFemaleConsultations++;
            }
        }
    }

    // Output the data for each reason
    echo $reason . "\t" . $count . "\t" . $maleStudents . "\t" . $femaleStudents . "\t" . $maleFaculty . "\t" . $femaleFaculty . "\n";
}

// Fetch canceled appointments for the same month and year
$query = "
    SELECT reason, IdNumber, COUNT(*) as count
    FROM cancel_appointments
    WHERE MONTH(appointment_date_cancelled) = ? 
    AND YEAR(appointment_date_cancelled) = ?
    GROUP BY reason, IdNumber
    ORDER BY count DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $currentMonth, $currentYear);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Add a line break before writing canceled appointments data
echo "\nCancelled Appointments (By Gender)\n";
echo "Reason\tCount\tMale (Students)\tFemale (Students)\tMale (Faculty)\tFemale (Faculty)\n";

// Check gender for canceled appointments and print them
while ($row = mysqli_fetch_assoc($result)) {
    $idNumber = $row['IdNumber'];
    $reason = $row['reason'];
    $count = $row['count'];

    // Reset gender counts for each reason
    $maleStudents = 0;
    $femaleStudents = 0;
    $maleFaculty = 0;
    $femaleFaculty = 0;

    // Check if IdNumber is in the students table and fetch gender
    $queryStudent = "SELECT Gender FROM students WHERE IdNumber = ?";
    $stmtStudent = mysqli_prepare($conn, $queryStudent);
    mysqli_stmt_bind_param($stmtStudent, "s", $idNumber);
    mysqli_stmt_execute($stmtStudent);
    $resultStudent = mysqli_stmt_get_result($stmtStudent);
    $rowStudent = mysqli_fetch_assoc($resultStudent);

    if ($rowStudent) {
        if ($rowStudent['Gender'] === 'Male') {
            $maleStudents++;
            $studentMaleConsultations++;
        } elseif ($rowStudent['Gender'] === 'Female') {
            $femaleStudents++;
            $studentFemaleConsultations++;
        }
    } else {
        // If not found in students, check in faculty and fetch gender
        $queryFaculty = "SELECT Gender FROM faculty WHERE IdNumber = ?";
        $stmtFaculty = mysqli_prepare($conn, $queryFaculty);
        mysqli_stmt_bind_param($stmtFaculty, "s", $idNumber);
        mysqli_stmt_execute($stmtFaculty);
        $resultFaculty = mysqli_stmt_get_result($stmtFaculty);
        $rowFaculty = mysqli_fetch_assoc($resultFaculty);

        if ($rowFaculty) {
            if ($rowFaculty['Gender'] === 'Male') {
                $maleFaculty++;
                $facultyMaleConsultations++;
            } elseif ($rowFaculty['Gender'] === 'Female') {
                $femaleFaculty++;
                $facultyFemaleConsultations++;
            }
        }
    }

    // Output the data for each canceled appointment
    echo $reason . "\t" . $count . "\t" . $maleStudents . "\t" . $femaleStudents . "\t" . $maleFaculty . "\t" . $femaleFaculty . "\n";
}
?>
