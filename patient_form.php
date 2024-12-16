<?php
session_start();

define('MIN_WIDTH', 800);  
define('MIN_HEIGHT', 600); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'db.php';

    $name = $_POST['name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($name) || empty($dob) || empty($phone) || empty($gender)) {
        $_SESSION['error'] = "Все поля формы должны быть заполнены.";
        header("Location: my_profile.php");
        exit;
    }

    $currentPhoto = null;
    try {
        $query_check = "SELECT Photo FROM patients WHERE userid = ?";
        $stmt_check = $pdo->prepare($query_check);
        $stmt_check->execute([$user_id]);
        $result_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
        if ($result_check) {
            $currentPhoto = $result_check['Photo'];
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Ошибка при получении текущего фото: " . $e->getMessage();
        header("Location: my_profile.php");
        exit;
    }

    $photo = $currentPhoto;

    if (isset($_POST['delete_photo'])) {
        $photo = null;
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileSize = $_FILES['photo']['size'];
    
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
        $allowedfileTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpPath);
        finfo_close($finfo);
    
        if (in_array($fileExtension, $allowedfileExtensions)) {
            if (in_array($mimeType, $allowedfileTypes)) {
                if ($fileSize < 20000000) {
                    $imageInfo = @getimagesize($fileTmpPath);
                    if ($imageInfo === false) {
                        $_SESSION['error'] = "Файл повреждён и не может быть обработан как изображение.";
                        header("Location: my_profile.php");
                        exit;
                    } else {
                        list($width, $height) = $imageInfo;
        
                        if ($width < MIN_WIDTH || $height < MIN_HEIGHT) {
                            $_SESSION['error'] = "Недопустимое разрешение изображения. Минимальные размеры: " . MIN_WIDTH . "x" . MIN_HEIGHT . " пикселей.";
                            header("Location: my_profile.php");
                            exit;
                        }
        
                        $photo = file_get_contents($fileTmpPath);
                    }
                } else {
                    $_SESSION['error'] = "Файл слишком большой. Максимальный размер: 20MB.";
                    header("Location: my_profile.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Фактический тип файла не является изображением.";
                header("Location: my_profile.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Недопустимое расширение файла. Допустимые типы: " . implode(', ', $allowedfileExtensions) . ".";
            header("Location: my_profile.php");
            exit;
        }
    }

    try {
        $query = "UPDATE patients SET Name = ?, DateBirth = ?, PhoneNumber = ?, Sex = ?, Photo = ? WHERE userid = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$name, $dob, $phone, $gender, $photo, $user_id]);

        $_SESSION['success'] = "Данные успешно обновлены.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Ошибка при сохранении данных в базе данных: " . $e->getMessage();
    }

    header("Location: my_profile.php");
    exit;
}
?>