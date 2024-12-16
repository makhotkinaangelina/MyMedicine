<?php
session_start();
require_once 'db.php';

define('MIN_WIDTH', 0);
define('MIN_HEIGHT', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['doctorID']) && isset($_FILES['photo'])) {
        $doctorID = $_POST['doctorID'];
        $photo = $_FILES['photo'];

        if ($photo['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'photo_doctors/';

            try {
                $stmt = $pdo->prepare("SELECT PhotoURL FROM Doctors WHERE ID = :doctorID");
                $stmt->execute(['doctorID' => $doctorID]);
                $doctor = $stmt->fetch();

                $extension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

                if (!in_array($extension, $allowedExtensions)) {
                    $_SESSION['error'] = 'Ошибка: недопустимое расширение файла. Допустимые форматы - JPG, PNG, GIF.';
                    header("Location: doctor.php?doctorID=" . $doctorID);
                    exit();
                }

                if (!in_array(mime_content_type($photo['tmp_name']), $allowedTypes)) {
                    $_SESSION['error'] = 'Ошибка: фактический тип файла не является изображением.';
                    header("Location: doctor.php?doctorID=" . $doctorID);
                    exit();
                }

                if ($photo['size'] > 20 * 1024 * 1024) {
                    $_SESSION['error'] = 'Ошибка: файл слишком большой. Максимальный размер - 20 МБ.';
                    header("Location: doctor.php?doctorID=" . $doctorID);
                    exit();
                }

                if (getimagesize($photo['tmp_name']) === false) {
                    $_SESSION['error'] = 'Ошибка: загруженное изображение повреждено.';
                    header("Location: doctor.php?doctorID=" . $doctorID);
                    exit();
                }

                list($width, $height) = getimagesize($photo['tmp_name']);

                if ($width < MIN_WIDTH || $height < MIN_HEIGHT) {
                    $_SESSION['error'] = 'Ошибка: разрешение изображения слишком низкое. Минимальные размеры - ' . MIN_WIDTH . 'x' . MIN_HEIGHT . ' пикселей.';
                    header("Location: doctor.php?doctorID=" . $doctorID);
                    exit();
                }

                if (getimagesize($photo['tmp_name']) === false) {
                    $_SESSION['error'] = 'Ошибка: загруженное изображение повреждено.';
                    header("Location: doctor.php?doctorID=" . $doctorID);
                    exit();
                }

                if ($doctor && !empty($doctor['PhotoURL'])) {
                    $oldPhotoPath = $doctor['PhotoURL'];
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                $fileName = uniqid('doctor_', true) . '.' . $extension; // Пример: doctor_5f9f4d12345678.jpg
                $photoPath = $uploadDir . $fileName;
                $photoURL = 'photo_doctors/' . $fileName;

                if (move_uploaded_file($photo['tmp_name'], $photoPath)) {
                    $stmt = $pdo->prepare("UPDATE Doctors SET PhotoURL = :photoURL WHERE ID = :doctorID");
                    $stmt->execute(['photoURL' => $photoURL, 'doctorID' => $doctorID]);

                    header("Location: doctor.php?doctorID=" . $doctorID);
                    exit();
                } else {
                    $_SESSION['error'] = 'Ошибка при перемещении загруженного файла.';
                    header("Location: doctor.php?doctorID=" . $doctorID);
                    exit();
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Ошибка базы данных: ' . $e->getMessage();
                header("Location: doctor.php?doctorID=" . $doctorID);
                exit();
            }
        } else {
            $_SESSION['error'] = 'Ошибка загрузки файла: ' . $photo['error'];
            header("Location: doctor.php?doctorID=" . $doctorID);
            exit();
        }
    }

    if (isset($_POST['deleteDoctorID'])) {
        $deleteDoctorID = $_POST['deleteDoctorID'];

        try {
            $stmt = $pdo->prepare("SELECT PhotoURL FROM Doctors WHERE ID = :doctorID");
            $stmt->execute(['doctorID' => $deleteDoctorID]);
            $doctor = $stmt->fetch();

            if ($doctor && !empty($doctor['PhotoURL'])) {
                $photoPath = $doctor['PhotoURL'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }

                $stmt = $pdo->prepare("UPDATE Doctors SET PhotoURL = NULL WHERE ID = :doctorID");
                $stmt->execute(['doctorID' => $deleteDoctorID]);

                header("Location: doctor.php?doctorID=" . $deleteDoctorID);
                exit();
            } else {
                $_SESSION['error'] = 'Ошибка: фото не найдено.';
                header("Location: doctor.php?doctorID=" . $deleteDoctorID);
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Ошибка базы данных: ' . $e->getMessage();
            header("Location: doctor.php?doctorID=" . $deleteDoctorID);
            exit();
        }
    }
} else {
    echo 'Некорректный запрос.';
}
