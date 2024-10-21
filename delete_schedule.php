<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['doctorID']) && isset($_POST['date']) && isset($_POST['time'])) {
    $doctorID = $_POST['doctorID'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    $stmt_select = $pdo->prepare("SELECT schedule FROM doctors WHERE id = :doctorID");
    $stmt_select->execute(['doctorID' => $doctorID]);
    $row = $stmt_select->fetch();
    $currentSchedule = json_decode($row['schedule'], true);

    if (isset($currentSchedule[$date])) {
        $currentSlots = $currentSchedule[$date];
        
        $key = array_search($time, $currentSlots);
        if ($key !== false) {
            unset($currentSlots[$key]);
        }

        if (empty($currentSlots)) {
            unset($currentSchedule[$date]);
        } else {
            $currentSchedule[$date] = array_values($currentSlots); // переиндексация массива после удаления
        }

        $updatedSchedule = json_encode($currentSchedule);
        $stmt_update = $pdo->prepare("UPDATE doctors SET schedule = :schedule WHERE id = :doctorID");
        $stmt_update->execute(['schedule' => $updatedSchedule, 'doctorID' => $doctorID]);
        $response = ['message' => 'Schedule time deleted successfully'];
        echo json_encode($response);
    } else {
        $response = ['error' => 'Date not found in schedule'];
        echo json_encode($response);
    }
} else {
    $response = ['error' => 'Invalid data provided'];
    echo json_encode($response);
}
header("Location: doctor.php");
