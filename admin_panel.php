<!DOCTYPE html>
<html>

<head>
    <title>Admin Panel</title>
    <link rel="stylesheet" href="admin_panel.css">
</head>

<body>
    <?php
    include 'header.php';
    require_once 'db.php';
    require_once 'models/CategoryModel.php';
    require_once 'functions.php';

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        echo "<div class='alert alert-danger'>Access Denied</div>";
        exit;
    }

    $categoryModel = new CategoryModel($pdo);
    $categories = get_categories($pdo);
    $error = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_category'])) {
            $categoryName = $_POST['category_name'];
            try {
                $categoryModel->addCategory($categoryName);
                header("Location: admin_panel.php");
                exit();
            } catch (PDOException $e) {
                $error = "Ошибка при добавлении категории: " . $e->getMessage();
            }
        } elseif (isset($_POST['edit_category'])) {
            $categoryId = $_POST['edit_category_id'];
            $categoryName = $_POST['edit_category_name'];
            try {
                $categoryModel->updateCategory($categoryId, $categoryName);
                header("Location: admin_panel.php");
                exit();
            } catch (PDOException $e) {
                $error = "Ошибка при обновлении категории: " . $e->getMessage();
            }
        } elseif (isset($_POST['delete_category'])) {
            $categoryId = $_POST['delete_category_id'];
            try {
                $categoryModel->deleteCategory($categoryId); 
                header("Location: admin_panel.php");
                exit();
            } catch (PDOException $e) {
                $error = "Ошибка при удалении категории: " . $e->getMessage();
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['name'], $_POST['specializationID'], $_POST['date_birth'], $_POST['experience_start'], $_POST['email'], $_POST['password'])) {
            $name = $_POST['name'];
            $categoryID = $_POST['specializationID'];
            $dateBirth = $_POST['date_birth'];
            $age = calculateAge($dateBirth);
            $experienceStart = $_POST['experience_start'];
            $experience = calculateExperience($experienceStart);
            $email = $_POST['email'];
            $password = $_POST['password'];

            try {
                $pdo->beginTransaction();

                $stmtUser = $pdo->prepare("INSERT INTO users (Nickname, Email, Password, role) VALUES (?, ?, ?, 'doctor')");
                $stmtUser->execute([$name, $email, $password]);

                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO doctors (ID, Name, SpecializationID, DateBirth, Age, ExperienceStart, Experience, Schedule, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $name, $categoryID, $dateBirth, $age, $experienceStart, $experience, '{}', $userId]);

                $pdo->commit();

                $_POST['name'] = '';
                $_POST['specializationID'] = '';
                $_POST['date_birth'] = '';
                $_POST['experience_start'] = '';
                $_POST['email'] = '';
                $_POST['password'] = '';

                echo '<p class="success-message">Врач успешно добавлен!</p>';

            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Ошибка: " . $e->getMessage();
            }
        }
    }
    if ($error) {
        echo '<p class="error-message">' . $error . '</p>';
    }
    ?>

    <div class="container">
        <form method="POST" class="doctor-form" action="">
            <h2>Добавление нового врача</h2>
            <div class="form-floating">
                <label for="name">Имя врача:</label>
                <input type="text" id="name" class="form-control" name="name" required maxlength="70"
                    value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>"><br>
            </div>
            <div class="form-floating">
                <label for="email">Email врача:</label>
                <input type="email" id="email" class="form-control" name="email" required
                    value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>"><br>
            </div>
            <div class="form-floating">
                <label for="password">Пароль для входа в учетную запись:</label>
                <input type="password" id="password" class="form-control" name="password" required
                    value="<?php echo isset($_POST['password']) ? $_POST['password'] : ''; ?>"><br>
            </div>
            <div class="form-floating">
                <label for="specializationID">Специализация:</label>
                <select name="specializationID" id="specializationID" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['CategoryID']; ?>"><?php echo $category['Category']; ?></option>
                    <?php endforeach; ?>
                </select><br>
            </div>
            <div class="form-floating">
                <label for="date_birth">Дата рождения:</label>
                <input type="date" id="date_birth" name="date_birth" required
                    value="<?php echo isset($_POST['date_birth']) ? $_POST['date_birth'] : ''; ?>"><br>
            </div>
            <div class="form-floating">
                <label for="experience_start">Дата начала работы:</label>
                <input type="date" id="experience_start" name="experience_start" required
                    value="<?php echo isset($_POST['experience_start']) ? $_POST['experience_start'] : ''; ?>"><br>
            </div>

            <button class="btn btn-primary w-100 py-2 add-btn" type="submit">Добавить врача</button>
        </form>
    </div>

    <div class="container">
        <h2>Добавление категории</h2>
        <form method="POST">
            <label for="category_name">Название категории:</label>
            <input type="text" id="category_name" name="category_name" required>
            <button class="btn btn-primary w-100 py-2 add-btn" type="submit" name="add_category">Добавить
                категорию</button>
        </form>
    </div>

    <div class="container">
        <h2>Изменение названия категории</h2>
        <form method="POST">
            <label for="edit_category_id">Выберите категорию для редактирования:</label>
            <select name="edit_category_id" id="edit_category_id" required>
                <?php
                foreach ($categories as $category) {
                    echo '<option value="' . $category['CategoryID'] . '">' . $category['Category'] . '</option>';
                }
                ?>
            </select><br>
            <label for="edit_category_name">Новое название категории:</label>
            <input type="text" id="edit_category_name" name="edit_category_name" required>
            <button class="btn btn-primary w-100 py-2 add-btn" type="submit" name="edit_category">Редактировать
                категорию</button>
        </form>
    </div>

    <div class="container">
        <h2>Удаление категории</h2>
        <form method="POST">
            <label for="delete_category_id">Выберите категорию для редактирования:</label>
            <select name="delete_category_id" id="delete_category_id" required>
                <?php
                foreach ($categories as $category) {
                    echo '<option value="' . $category['CategoryID'] . '">' . $category['Category'] . '</option>';
                }
                ?>
            </select><br>
            <button class="btn btn-primary w-100 py-2 add-btn" type="submit" name="delete_category">Удалить
                категорию</button>
        </form>
    </div>

</body>

</html>