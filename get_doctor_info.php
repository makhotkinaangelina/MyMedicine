<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['doctor_id'])) {
    $doctorId = $_POST['doctor_id'];

    try {
        $stmt = $pdo->prepare("CALL GetDoctorById(:doctorId)");
        $stmt->bindParam(':doctorId', $doctorId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $doctorData = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            header('Content-Type: application/json');
            echo json_encode($doctorData);
        } else {
            echo json_encode(["error" => "Ошибка выполнения процедуры для врача с идентификатором $doctorId"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Произошла ошибка: " . $e->getMessage()]);
    }
}
