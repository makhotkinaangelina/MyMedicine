<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments info</title>
    <link rel="stylesheet" href="users_appointments_info.css">
</head>

<body>
    <?php
    include 'header.php';
    require_once 'db.php';
    require_once 'functions.php';

    $error = "";
    $search = "";

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appointment'])) {
        $appointment_id = $_POST['appointment_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->execute([$appointment_id]);

            header("Location: users_appointments_info.php");
            exit();
        } catch (PDOException $e) {
            $error = "Ошибка при удалении записи: " . $e->getMessage();
        }
    }

    if ($error) {
        echo '<p class="error-message">' . $error . '</p>';
    }

    try {
        $query = "SELECT appointments.id, patientID, patients.name as patient_name, doctors.name, categories.category, doctorID, Date, Time, Description, Status 
                  FROM appointments
                  JOIN patients ON patients.id = patientID
                  JOIN doctors ON doctors.id = doctorID
                  JOIN categories ON categories.categoryid = doctors.specializationid";

        if (!empty($search)) {
            $query .= " WHERE patients.name LIKE :search 
                        OR doctors.name LIKE :search 
                        OR categories.category LIKE :search 
                        OR Date LIKE :search 
                        OR Time LIKE :search 
                        OR Description LIKE :search 
                        OR Status LIKE :search";
        }

        $stmt = $pdo->prepare($query);
        if (!empty($search)) {
            $stmt->execute(['search' => "%$search%"]);
        } else {
            $stmt->execute();
        }

        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Ошибка при извлечении записей: " . $e->getMessage();
    }
    ?>

    <h2>Записи</h2>
    <form method="GET" action="" class="form-floating">
        <input type="text" name="search" maxlength="200" value="<?php echo htmlspecialchars($search); ?>" placeholder="Поиск...">
        <button type="submit" class="btn-primary">Найти</button>
    </form>

    <?php if (empty($appointments) && empty($search)): ?>
        <p>Записей пока нет...</p>
    <?php elseif (!empty($search) && empty($appointments)): ?>
        <p>Ничего не найдено...</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Пациент</th>
                    <th>Врач</th>
                    <th>Категория врача</th>
                    <th>Дата</th>
                    <th>Время</th>
                    <th>Описание</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['name']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['category']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Date']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Time']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Description']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Status']); ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить эту запись?');">
                                <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                <button type="submit" name="delete_appointment" class="delete-button">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>

</html>