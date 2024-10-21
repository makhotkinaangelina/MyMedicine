<?php
require_once 'db.php';

if (isset($_POST['doctor_id'])) {
    $doctorId = $_POST['doctor_id'];
    
    $stmt = $pdo->prepare("DELETE FROM Doctors WHERE id = :doctorId");
    $stmt->execute(['doctorId' => $doctorId]);
    
    http_response_code(200);
    exit();
} else {
    http_response_code(400);
    exit();
}
