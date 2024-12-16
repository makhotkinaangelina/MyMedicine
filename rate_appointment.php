<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_id = $_POST['appointment_id'];
    $doctor_id = $_POST['doctor_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    if (empty($rating) || $rating < 1 || $rating > 5) {
        $_SESSION['rating_error'] = "Пожалуйста, выберите оценку от 1 до 5.";
    } else {
        try {
            $query_rating = "INSERT INTO ratings (appointment_id, doctor_id, rating, comment) VALUES (?, ?, ?, ?)";
            $stmt_rating = $pdo->prepare($query_rating);
            $stmt_rating->execute([$appointment_id, $doctor_id, $rating, $comment]);
            $_SESSION['rating_success'] = "Спасибо за вашу оценку!";
            header("Location: my_profile.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['rating_error'] = "Ошибка при сохранении оценки: " . $e->getMessage();
        }
    }

    header("Location: my_profile.php");
    exit;
}