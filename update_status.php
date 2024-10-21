<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentID = $_POST['appointment_id'];
    $status = isset($_POST['accept']) ? 'Одобрено' : 'Отклонено';

    $query_update_status = "UPDATE appointments SET Status = ? WHERE ID = ?";
    $stmt_update_status = $pdo->prepare($query_update_status);
    $stmt_update_status->execute([$status, $appointmentID]);

    if ($stmt_update_status->rowCount() > 0) {
        if ($status === 'Отклонено') {
            echo 'status is reject';
            echo '<script>
                    window.onload = function() {
                        const form = document.createElement("form");
                        form.method = "post";
                        form.action = "cancel_appointment.php";
    
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = "appointment_id";
                        input.value = "' . $appointmentID . '";
    
                        form.appendChild(input);
                        document.body.appendChild(form);
    
                        form.submit();
                    }
                </script>';
        } else {
            echo $status;
            echo '<script>
                    window.location.href = "doctor.php";
                </script>';
        }
        exit();
    } else {
        echo "Ошибка при обновлении статуса записи.";
    }
}
