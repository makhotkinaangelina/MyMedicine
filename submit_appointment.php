<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();
    $userID = $_SESSION['user_id'];
    $doctorID = $_POST['doctor_id'];
    $date = date('Y-m-d', strtotime($_POST['date_' . $doctorID]));
    $time = $_POST['time_' . $doctorID];
    $comment = $_POST['comment_' . $doctorID];

    // Получение ID пациента
    $stmt = $pdo->prepare("SELECT ID FROM patients WHERE userID = :userID");
    $stmt->bindParam(':userID', $userID);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $patientID = $row['ID'];
        
        try {
            // Вставка записи о приеме
            $stmt = $pdo->prepare("INSERT INTO appointments (patientID, doctorID, Date, Time, description, Status) VALUES (:patientID, :doctorID, :date, :time, :comment, 'В обработке')");
            $stmt->bindParam(':patientID', $patientID);
            $stmt->bindParam(':doctorID', $doctorID);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':time', $time);
            $stmt->bindParam(':comment', $comment);
        
            if ($stmt->execute()) {
                // Получение информации о враче
                $doctorQuery = $pdo->prepare("SELECT Name, SpecializationID FROM doctors WHERE ID = :doctorID");
                $doctorQuery->bindParam(':doctorID', $doctorID);
                $doctorQuery->execute();
                $doctorRow = $doctorQuery->fetch(PDO::FETCH_ASSOC);

                if ($doctorRow) {
                    $doctorName = $doctorRow['Name'];

                    // Получение специализации врача
                    $specializationQuery = $pdo->prepare("SELECT Category FROM Categories WHERE CategoryID = :specializationID");
                    $specializationID = $doctorRow['SpecializationID'];
                    $specializationQuery->bindParam(':specializationID', $specializationID);
                    $specializationQuery->execute();
                    $specializationRow = $specializationQuery->fetch(PDO::FETCH_ASSOC);
                    $doctorSpecialization = $specializationRow ? $specializationRow['Category'] : 'Неизвестная специализация';

                    $actionDescription = "Запись на прием к врачу $doctorName ($doctorSpecialization) на дату $date, время $time";

                    // Запись действия в таблицу user_actions
                    $actionStmt = $pdo->prepare("INSERT INTO user_actions (UserID, ActionDescription) VALUES (:userID, :actionDescription)");
                    $actionStmt->bindParam(':userID', $userID);
                    $actionStmt->bindParam(':actionDescription', $actionDescription);
                    $actionStmt->execute();
                }

                // Обновление расписания врача
                $scheduleQuery = $pdo->prepare("SELECT Schedule FROM doctors WHERE ID = :doctorID");
                $scheduleQuery->bindParam(':doctorID', $doctorID);
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
                    $updateQuery->bindParam(':doctorID', $doctorID);
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
            echo 'Ошибка базы данных: ' . $e->getMessage();
        }
    } else {
        header("Location: my_profile.php");
        exit; 
    }
}
?>