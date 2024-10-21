<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История действий пользователей</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="appointments.css">
</head>
<body>
<?php
    include 'header.php';
    require_once 'db.php';

    $query = "
    SELECT 
        ua.ActionTime, 
        u.Nickname AS UserName, 
        ua.ActionDescription 
    FROM 
        user_actions ua
    JOIN 
        users u ON ua.UserID = u.ID
    ORDER BY 
        ua.ActionTime DESC";

    try {
        $stmt = $pdo->query($query);
    } catch (PDOException $e) {
        die('Ошибка выполнения запроса: ' . $e->getMessage());
    }
?>

<h1>История действий пользователей</h1>

<table>
    <thead>
        <tr>
            <th>Время</th>
            <th>Пользователь</th>
            <th>Описание действия</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <tr>
            <td data-label="Время"><?php echo htmlspecialchars($row['ActionTime']); ?></td>
            <td data-label="Пользователь"><?php echo htmlspecialchars($row['UserName']); ?></td>
            <td data-label="Описание действия"><?php echo htmlspecialchars($row['ActionDescription']); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>