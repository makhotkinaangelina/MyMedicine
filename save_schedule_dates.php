<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newDates = $_POST['new_dates'];
    $newTimes = $_POST['new_times'];

foreach ($newDates as $key => $date) {
    $timeStart = $newTimes[$key * 2];
    $timeEnd = $newTimes[$key * 2 + 1];

    $stmt = $pdo->prepare("UPDATE Doctors SET Schedule = JSON_SET(Schedule, '$.dates', JSON_ARRAY_APPEND(Schedule->'$.dates', '$', JSON_OBJECT('date', :date, 'timeStart', :timeStart, 'timeEnd', :timeEnd))) WHERE ID = :doctor_id");
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':timeStart', $timeStart);
    $stmt->bindParam(':timeEnd', $timeEnd);
    $stmt->bindParam(':doctor_id', $doctorId);
    $stmt->execute();
}

    $response = ['message' => 'New schedule dates saved successfully'];
    echo json_encode($response);
} else {
    http_response_code(405); 
}
