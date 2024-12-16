<?php
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    $status = isset($_POST['accept']) ? 'Одобрено' : 'Отклонено';
    $appointment_id = $_POST['appointment_id'];
    $user_id = $_SESSION['user_id'];

    $stmt_select = $pdo->prepare("SELECT *, u.email AS PatientEmail
                                   FROM appointments 
                                   JOIN patients p on appointments.PatientId = p.ID
                                   JOIN users u ON u.id = p.UserId
                                   WHERE appointments.id = :appointment_id");
    $stmt_select->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
    $stmt_select->execute();
    $appointment = $stmt_select->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        echo "Ошибка: информация о записи не найдена.";
        exit;
    }

    $query_update_status = "UPDATE appointments SET Status = ? WHERE ID = ?";
    $stmt_update_status = $pdo->prepare($query_update_status);
    $stmt_update_status->execute([$status, $appointment_id]);

    if ($stmt_update_status->rowCount() > 0) {
        $doctor_id = $appointment['DoctorID'];
        $date = $appointment['Date'];
        $time = $appointment['Time'];

        $stmt_doctor = $pdo->prepare("SELECT d.*, c.Category 
                                       FROM Doctors d
                                       JOIN categories c ON c.categoryId = d.SpecializationId
                                       WHERE d.ID = :doctor_id");
        $stmt_doctor->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);
        $stmt_doctor->execute();
        $doctor = $stmt_doctor->fetch(PDO::FETCH_ASSOC);

        if ($status === 'Отклонено') {
            if (!$doctor) {
                echo "Ошибка: информация о враче не найдена для DoctorID: $doctor_id.";
                exit;
            }

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
        }

        $patient_email = $appointment['PatientEmail'];
        $doctor_name = $doctor['Name'];
        $doctor_specialization = $doctor['Category'];

        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            $sender_email = 'gelyamakh@gmail.com'; 
            $mail->Username = $sender_email; 
            $mail->Password = 'mbjt fnll rkof arwp'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom($sender_email, 'MyMedicine');
            $mail->addAddress($patient_email);

            $mail->isHTML(true);
            $mail->Subject = 'MyMedicine: Врач рассмотрел Вашу заявку!';
            $mail->Body = "Уважаемый пациент,<br><br>
                           Статус вашей записи на прием к врачу <strong>$doctor_name</strong> 
                           (Специализация: <strong>$doctor_specialization</strong>) изменен на <strong>$status</strong>.<br>
                           Дата: <strong>$date</strong><br>
                           Время: <strong>$time</strong><br><br>
                           Спасибо за использование нашей системы!";

            if ($mail->send()) {
                echo "Сообщение успешно отправлено.<br>";
                echo "От: $sender_email<br>";
                echo "Кому: $patient_email<br>";
                echo "Тема: {$mail->Subject}<br>";
                echo "Тело сообщения: {$mail->Body}<br>";
            } else {
                echo "Ошибка при отправке сообщения.";
            }
        } catch (Exception $e) {
            echo "Ошибка отправки сообщения: {$mail->ErrorInfo}";
        }

        echo '<script>
                window.location.href = "doctor.php";
              </script>';
    } else {
        echo "Ошибка при обновлении статуса записи.";
    }
}
