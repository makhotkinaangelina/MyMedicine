<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['doctorID']) && isset($_POST['date']) && isset($_POST['old_time']) && isset($_POST['new_time'])) {
    $doctorID = $_POST['doctorID'];
    $date = $_POST['date'];
    $oldTime = $_POST['old_time'];
    $newTime = $_POST['new_time'];

    $stmt_select = $pdo->prepare("SELECT schedule FROM doctors WHERE id = :doctorID");
    $stmt_select->execute(['doctorID' => $doctorID]);
    $row = $stmt_select->fetch();
    $currentSchedule = json_decode($row['schedule'], true);

    if (isset($currentSchedule[$date])) {
        $currentSlots = $currentSchedule[$date];
        $key = array_search($oldTime, $currentSlots);
        
        if ($key !== false) {
            $currentSlots[$key] = $newTime;
            $currentSchedule[$date] = $currentSlots;

            $updatedSchedule = json_encode($currentSchedule);
            $stmt_update = $pdo->prepare("UPDATE doctors SET schedule = :schedule WHERE id = :doctorID");
            $stmt_update->execute(['schedule' => $updatedSchedule, 'doctorID' => $doctorID]);

            $response = ['message' => 'Schedule time updated successfully'];
            echo json_encode($response);
        } else {
            $response = ['error' => 'Old time not found in schedule'];
            echo json_encode($response);
        }
    } else {
        $response = ['error' => 'Date not found in schedule'];
        echo json_encode($response);
    }
} else {
    $response = ['error' => 'Invalid data provided'];
    echo json_encode($response);
}
header("Location: doctor.php");
