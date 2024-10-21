<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["num_time_intervals"])) {
    $doctorID = $_POST["doctorID"];
    $newDate = $_POST["new_date"];
    $numTimeIntervals = intval($_POST["num_time_intervals"]);
    $numTimeIntervals = min(max($numTimeIntervals, 1), 5);

    $schedule = [];
    for ($i = 0; $i < $numTimeIntervals; $i++) {
        $currentTime = strtotime("+{$i} hours", strtotime("00:00"));
        $nearestTime = date("H:00", $currentTime);
        $schedule[] = $nearestTime . '-' . date("H:00", strtotime("+1 hour", $currentTime));
    }

    $stmt = $pdo->prepare("SELECT Schedule FROM doctors WHERE ID = :doctorID");
    $stmt->execute(['doctorID' => $doctorID]);
    $doctorSchedule = json_decode($stmt->fetchColumn(), true);

    if ($doctorSchedule === null) {
        $doctorSchedule = [];
    }

    $doctorSchedule[$newDate] = $schedule;

    $stmtUpdate = $pdo->prepare("UPDATE doctors SET Schedule = :schedule WHERE ID = :doctorID");
    $stmtUpdate->execute([
        'schedule' => json_encode($doctorSchedule),
        'doctorID' => $doctorID
    ]);

    echo "Новая дата с временными промежутками успешно добавлена в расписание доктора.";
} else {
    echo "Неверный запрос.";
}

header("Location: doctor.php");
