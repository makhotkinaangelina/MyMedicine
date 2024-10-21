<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'db.php';

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['Password']) {
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
        $error_message = "Ошибка при выполнении запроса: " . $e->getMessage();
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
        <div class="form-floating">
            <label for="email">email:</label>
            <input type="email" class="form-control" id="email" name="email" maxlength="50" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? $_POST['email'] : '' ?>">
        </div>
        <div class="form-floating">
            <label for="password">Пароль:</label>
            <input type="password" class="form-control" id="password" name="password" maxlength="255" placeholder="Password" required value="<?php echo isset($_POST['password']) ? $_POST['password'] : '' ?>">
        </div>
        <button class="btn btn-primary w-100 py-2" type="submit">Войти</button>
    </form>

    <?php if(isset($error_message)) { ?>
        <div class='error-message'><?php echo $error_message; ?></div>
    <?php } ?>
</div>

</body>
</html>