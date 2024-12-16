<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctor_id'])) {
    $doctorId = (int)$_POST['doctor_id'];
    $name = htmlspecialchars(trim($_POST['name']));
    $specializationID = (int)$_POST['specializationID'];
    $dateBirth = htmlspecialchars(trim($_POST['date_birth']));
    $experienceStart = htmlspecialchars(trim($_POST['experience_start']));
    $schedule = htmlspecialchars(trim($_POST['schedule']));

    $stmt_specialization = $pdo->prepare("SELECT Category FROM Categories WHERE categoryid = :specializationID");
    $stmt_specialization->bindParam(':specializationID', $specializationID, PDO::PARAM_INT);
    $stmt_specialization->execute();
    $specializationResult = $stmt_specialization->fetch();
    $specialization = $specializationResult ? htmlspecialchars($specializationResult['Category']) : 'Неизвестная специализация';

    try {
        $stmt = $pdo->prepare("UPDATE Doctors SET Name = :name, specializationid = :specializationid, DateBirth = :dateBirth, ExperienceStart = :experienceStart, Schedule = :schedule WHERE ID = :doctorId");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':specializationid', $specializationID, PDO::PARAM_INT);
        $stmt->bindParam(':dateBirth', $dateBirth);
        $stmt->bindParam(':experienceStart', $experienceStart);
        $stmt->bindParam(':schedule', $schedule);
        $stmt->bindParam(':doctorId', $doctorId, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: appointments.php?specialization=" . urlencode($specialization));
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Ошибка при обновлении данных: " . $e->getMessage();
        error_log($errorMessage, 3, "error.log");
        header("Location: appointments.php?specialization=" . urlencode($specialization) . "&error=" . urlencode($errorMessage));
        exit();
    }
} else {
    http_response_code(400);
    exit();
}