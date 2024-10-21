<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    require_once 'db.php'; 

    if (isset($_POST['nickname'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
        $nickname = $_POST['nickname'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($password !== $confirmPassword) {
            $error = "Пароли не совпадают";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                $error = "Пользователь с таким email уже существует";
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE nickname = ?");
                    $stmt->execute([$nickname]);
                    $existingNickname = $stmt->fetch();
    
                    if ($existingNickname) {
                        $error = "Пользователь с таким nickname уже существует";
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (nickname, email, password) VALUES (?, ?, ?)");
                        $stmt->execute([$nickname, $email, $hashedPassword]);
                        $newUserId = $pdo->lastInsertId();
                        $_SESSION['user_id'] = $newUserId;
                        $_SESSION['user_role'] = 'patient';
                        header("Location: main.php");
                        exit;
                    }
                }
                catch (PDOException $e) {
                    $error_message = "Ошибка при выполнении запроса: " . $e->getMessage();  
                }
            }
        }
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
        <div class="form-floating">
            <label for="nickname">Ваш nickname:</label>
            <input type="text" class="form-control" id="nickname" name="nickname" maxlength="30" placeholder="Nickname" required value="<?php echo isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : '' ?>">
        </div>
        <div class="form-floating">
            <label for="email">email:</label>
            <input type="email" class="form-control" id="email" name="email" maxlength="50" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
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
        <div class="error-message"><?php echo $error; ?></div>
    <?php } ?>
</div>
</body>
</html>