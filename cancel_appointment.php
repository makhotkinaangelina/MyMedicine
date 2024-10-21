<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'db.php';
    session_start();
    if (isset($_POST['appointment_id'])) {
        $appointment_id = $_POST['appointment_id'];

        $stmt_select = $pdo->prepare("SELECT * FROM appointments WHERE id = :appointment_id");
        $stmt_select->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
        $stmt_select->execute();
        $appointment = $stmt_select->fetch(PDO::FETCH_ASSOC);

        $stmt_delete = $pdo->prepare("DELETE FROM appointments WHERE id = :appointment_id");
        $stmt_delete->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
        
    if ($stmt_delete->execute()) {
        echo "Запись успешно отменена.";

        $doctor_id = $appointment['DoctorID'];
        $date = $appointment['Date'];
        $time = $appointment['Time'];

        $stmt_doctor = $pdo->prepare("SELECT Schedule FROM Doctors WHERE ID = :doctor_id");
        $stmt_doctor->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
        $stmt_doctor->execute();
        $doctor = $stmt_doctor->fetch(PDO::FETCH_ASSOC);

        $schedule = json_decode($doctor['Schedule'], true);

        if (isset($schedule[$date])) {
            $schedule[$date][] = $time;
            sort($schedule[$date]); 
        } else {
            $schedule[$date] = [$time];
        }

        ksort($schedule);

        $schedule_json = json_encode($schedule);

        $stmt_update = $pdo->prepare("UPDATE Doctors SET Schedule = :schedule WHERE ID = :doctor_id");
        $stmt_update->bindParam(':schedule', $schedule_json);
        $stmt_update->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
        $stmt_update->execute();
    } else {
        echo "Ошибка отмены записи.";
    }
    } else {
        echo "Идентификатор записи не найден.";
    }
} 

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === "doctor") {
    header("Location: doctor.php");
}
else {
    header("Location: my_profile.php");
}
