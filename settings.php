<!DOCTYPE html>
<html>
<head>
    <title>My HTML Project</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <div class="container">
        <?php 
        session_start(); 
        include 'header.php';
        include 'db.php'; 

        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            echo "<div class='alert alert-danger'>Access Denied</div>";
            exit; 
        }

        try {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $weights = $_POST['weights'];
                $totalWeight = array_sum($weights);

                foreach ($weights as $id => $weight) {
                    if ($weight >= 0) {
                        $stmt = $pdo->prepare("UPDATE criteries SET weight = :weight WHERE id = :id");
                        $stmt->bindParam(':weight', $weight);
                        $stmt->bindParam(':id', $id);
                        $stmt->execute();
                    }
                }
                echo "<div class='alert alert-success'>Веса успешно обновлены!</div>";
            }

            $stmt = $pdo->query("SELECT * FROM criteries");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>

        <h2>Управление весами критериев</h2>
        <form method="POST">
            <table class="table">
                <thead>
                    <tr>
                        <th>Критерий</th>
                        <th>Вес</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['criterion_name']); ?></td>
                            <td>
                                <input type="number" step="0.01" min="0" max="1" maxlength="8" name="weights[<?php echo $row['id']; ?>]" value="<?php echo htmlspecialchars($row['weight']); ?>" required>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Обновить веса</button>
        </form>
    </div>

</body>
</html>