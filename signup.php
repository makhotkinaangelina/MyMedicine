<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    require_once 'db.php'; 
    require_once 'models/UserModel.php';
    $userModel = new UserModel($pdo);

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ошибка: недопустимый CSRF токен.");
    }

    if (isset($_POST['nickname'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
        $nickname = htmlspecialchars($_POST['nickname'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($password !== $confirmPassword) {
            $error = "Пароли не совпадают";
        } else {
            if ($userModel->getByEmail($email)) {
                $error = "Пользователь с таким email уже существует";
            } else {
                if ($userModel->getByNickname($nickname)) {
                    $error = "Пользователь с таким nickname уже существует";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    if ($userModel->register($email, $hashedPassword, $nickname)) {
                        $_SESSION['user_id'] = $pdo->lastInsertId();
                        $_SESSION['user_role'] = 'patient';
                        header("Location: main.php");
                        exit;
                    } else {
                        $error = "Ошибка при регистрации.";
                    }
                }
            }
        }
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
    <link rel="stylesheet" href="signup.css">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <h2>Регистрация</h2>
    
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        
        <div class="form-floating">
            <label for="nickname">Ваш nickname:</label>
            <input type="text" class="form-control" id="nickname" name="nickname" maxlength="30" placeholder="Nickname" required value="<?php echo isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname'], ENT_QUOTES, 'UTF-8') : '' ?>">
        </div>
        <div class="form-floating">
            <label for="email">email:</label>
            <input type="email" class="form-control" id="email" name="email" maxlength="50" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>">
        </div>
        <div class="form-floating">
            <label for="password">Пароль:</label>
            <input type="password" class="form-control" id="password" name="password" maxlength="255" placeholder="Password" required>
        </div>
        <div class="form-floating">
            <label for="confirm_password">Подтвердите пароль:</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" maxlength="255" placeholder="Confirm Password" required>
        </div>

        <button class="btn btn-primary w-100 py-2 signup-btn" type="submit">Зарегистрироваться</button>
    </form>

    <?php if (isset($error)) { ?>
        <div class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>
</div>
</body>
</html>
