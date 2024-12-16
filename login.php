<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'db.php';
    require_once 'models/UserModel.php'; 

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ошибка: недопустимый CSRF токен.");
    }

    $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
    $password = $_POST['password'];

    try {
        $userModel = new UserModel($pdo);
        $user = $userModel->getByEmail($email);

        if ($user && (password_verify($password, $user['Password']) || $password == $user['Password'])) {
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['user_role'] = $user['role'];
                
            if ($user['role'] == 'doctor') {
                header("Location: doctor.php");
                exit();
            } else {
                header("Location: main.php");
                exit();
            }
        } else {
            $error_message = "Неверный email или пароль. Попробуйте снова.";
        }
    } catch (PDOException $e) {
        $error_message = "Ошибка при выполнении запроса: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
} else {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>MyMedicine</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <h2>Вход в систему</h2>
    
    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <div class="form-floating">
            <label for="email">email:</label>
            <input type="email" class="form-control" id="email" name="email" maxlength="50" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>">
        </div>
        <div class="form-floating">
            <label for="password">Пароль:</label>
            <input type="password" class="form-control" id="password" name="password" maxlength="255" placeholder="Password" required>
        </div>
        <button class="btn btn-primary w-100 py-2" type="submit">Войти</button>
    </form>

    <?php if (isset($error_message)) { ?>
        <div class='error-message'><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>
</div>

</body>
</html>
