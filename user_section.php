<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SESSION['user_id'])) {
    include 'db.php';

    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT nickname, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if($user) {
            $user_name = htmlspecialchars($user['nickname']); 
            echo "<p>Добро пожаловать, $user_name!</p>";

            if ($user['role'] === 'patient') {
                echo '<a href="my_profile.php" class="btn btn-outline-primary">My Profile</a>';
            } elseif ($user['role'] === 'admin') {
                echo '<a href="history.php" class="btn btn-outline-success">User\'s history </a>';
                echo '<a href="admin_panel.php" class="btn btn-outline-success">Admin Panel</a>';
            }
        
            echo '<a href="main.php?logout=true" class="btn btn-outline-danger">Logout</a>';
        } else {
            echo "<p>Добро пожаловать!</p>";
        }
    } catch (PDOException $e) {
        echo "Ошибка при выполнении запроса: " . $e->getMessage();
    }
} else { ?>
    <a href="login.php" class="btn btn-outline-primary me-2 login-btn">Login</a>
    <a href="signup.php" class="btn btn-primary signup-btn">Sign-up</a>
<?php }

if(isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_unset();
    session_destroy();

    header("Location: main.php");
    exit;
}
