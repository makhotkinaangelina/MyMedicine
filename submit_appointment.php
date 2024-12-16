<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();
    
    $userID = $_SESSION['user_id'];
    $doctorID = (int)$_POST['doctor_id'];
    $date = date('Y-m-d', strtotime($_POST['date_' . $doctorID]));
    $time = htmlspecialchars(trim($_POST['time_' . $doctorID]));
    $comment = htmlspecialchars(trim($_POST['comment_' . $doctorID]));

    $stmt = $pdo->prepare("SELECT ID FROM patients WHERE userID = :userID");
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $patientID = $row['ID'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO appointments (patientID, doctorID, Date, Time, description, Status) VALUES (:patientID, :doctorID, :date, :time, :comment, 'В обработке')");
            $stmt->bindParam(':patientID', $patientID, PDO::PARAM_INT);
            $stmt->bindParam(':doctorID', $doctorID, PDO::PARAM_INT);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':time', $time);
            $stmt->bindParam(':comment', $comment);
        
            if ($stmt->execute()) {
                $doctorQuery = $pdo->prepare("SELECT Name, SpecializationID FROM doctors WHERE ID = :doctorID");
                $doctorQuery->bindParam(':doctorID', $doctorID, PDO::PARAM_INT);
                $doctorQuery->execute();
                $doctorRow = $doctorQuery->fetch(PDO::FETCH_ASSOC);

                if ($doctorRow) {
                    $doctorName = htmlspecialchars($doctorRow['Name']);
                    $specializationQuery = $pdo->prepare("SELECT Category FROM Categories WHERE CategoryID = :specializationID");
                    $specializationID = $doctorRow['SpecializationID'];
                    $specializationQuery->bindParam(':specializationID', $specializationID, PDO::PARAM_INT);
                    $specializationQuery->execute();
                    $specializationRow = $specializationQuery->fetch(PDO::FETCH_ASSOC);
                    $doctorSpecialization = $specializationRow ? htmlspecialchars($specializationRow['Category']) : 'Неизвестная специализация';
                    $actionDescription = "Запись на прием к врачу $doctorName ($doctorSpecialization) на дату $date, время $time";
                }

                $scheduleQuery = $pdo->prepare("SELECT Schedule FROM doctors WHERE ID = :doctorID");
                $scheduleQuery->bindParam(':doctorID', $doctorID, PDO::PARAM_INT);
                $scheduleQuery->execute();
                $row = $scheduleQuery->fetch();
                $schedule = json_decode($row['Schedule'], true);

                if (isset($schedule[$date])) {
                    $updatedSlots = array_diff($schedule[$date], [$time]);
                    $schedule[$date] = array_values($updatedSlots);

                    if (empty($schedule[$date])) {
                        unset($schedule[$date]);
                    }

                    $updatedSchedule = json_encode($schedule);
        
                    $updateQuery = $pdo->prepare("UPDATE doctors SET Schedule = :schedule WHERE ID = :doctorID");
                    $updateQuery->bindParam(':schedule', $updatedSchedule);
                    $updateQuery->bindParam(':doctorID', $doctorID, PDO::PARAM_INT);
                    $updateQuery->execute();
        
                    header("Location: my_profile.php");
                    exit; 
                } else {
                    echo 'Выбранное время уже занято';
                }
            } else {
                echo 'Ошибка при записи';
            }
        } catch (PDOException $e) {
            echo 'Ошибка базы данных: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        header("Location: my_profile.php");
        exit; 
    }
}
