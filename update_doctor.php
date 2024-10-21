<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctor_id'])) {
    $doctorId = $_POST['doctor_id'];
    $name = $_POST['name'];
    $specializationID = $_POST['specializationID'];
    $dateBirth = $_POST['date_birth'];
    $experienceStart = $_POST['experience_start'];
    $schedule = $_POST['schedule'];

    $stmt_specialization = $pdo->prepare("SELECT Category FROM Categories WHERE categoryid = :specializationID");
    $stmt_specialization->execute(['specializationID' => $specializationID]);
    $specializationResult = $stmt_specialization->fetch();
    $specialization = $specializationResult['Category'];

    try {
        $stmt = $pdo->prepare("UPDATE Doctors SET Name = :name, specializationid = :specializationid, DateBirth = :dateBirth, ExperienceStart = :experienceStart, Schedule = :schedule WHERE ID = :doctorId");
        $stmt->execute(['name' => $name, 'specializationid' => $specializationID, 'dateBirth' => $dateBirth, 'experienceStart' => $experienceStart, 'schedule' => $schedule, 'doctorId' => $doctorId]);
        header("Location: appointments.php?specialization=" . urlencode($specialization));
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Ошибка при обновлении данных: " . $e->getMessage();
        error_log($errorMessage, 3, "error.log");
        header("Location: appointments.php?specialization={$specialization}&error=" . urlencode($errorMessage));
        exit();
    }
} else {
    http_response_code(400);
    exit();
}
