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
        include 'header.php';
        include 'db.php';

        $minVisits = 3;

        $userID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin']; // Предполагаем, что вы храните статус администратора в сессии

        if (isset($_SESSION['user_id']) && !$isAdmin) {
            $query_select_patientID = "SELECT ID FROM patients WHERE UserID = ?";
            $stmt_select_patientID = $pdo->prepare($query_select_patientID);
            $stmt_select_patientID->execute([$userID]);
            $patientID_row = $stmt_select_patientID->fetch(PDO::FETCH_ASSOC);
            
            if ($patientID_row) {
                $patientID = $patientID_row['ID'];

                $query_frequent_categories = "SELECT c.Category, COUNT(a.DoctorID) as NumDoctors
                    FROM categories c
                    JOIN doctors d ON c.CategoryID = d.SpecializationID
                    JOIN appointments a ON d.ID = a.DoctorID
                    WHERE a.PatientID = ?
                    GROUP BY c.Category
                    HAVING COUNT(a.DoctorID) >= ?
                    ORDER BY NumDoctors DESC
                    LIMIT 3";
                
                $stmt_frequent_categories = $pdo->prepare($query_frequent_categories);
                $stmt_frequent_categories->execute([$patientID, $minVisits]);
                $frequent_categories = $stmt_frequent_categories->fetchAll();
            } else {
                $frequent_categories = []; 
            }
        } else {
            $frequent_categories = []; 
        }

        $query_all_categories = "SELECT * FROM categories";
        $stmt_all_categories = $pdo->query($query_all_categories);
        $all_categories = $stmt_all_categories->fetchAll();
    ?>

<div class="album py-5 bg-body-tertiary">
    <div class="container">
        <?php if (!empty($frequent_categories)): ?>
            <h2>Часто посещаемые категории:</h2>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
                <?php foreach ($frequent_categories as $category): ?>
                    <div class="col">
                        <a href="appointments.php?specialization=<?= urlencode($category['Category']) ?>">
                            <div class="btn btn-sm btn-outline-secondary btn-doctor">
                                <text name="<?= strtolower(str_replace(' ', '', $category['Category'])) ?>" x="40%" y="50%" fill="#eceeef" dy=".3em" style="font-size: 24px;"><?= $category['Category'] ?></text>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2>Все категории:</h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
            <?php foreach ($all_categories as $category): ?>
                <div class="col">
                    <a href="appointments.php?specialization=<?= urlencode($category['Category']) ?>">
                        <div class="btn btn-sm btn-outline-secondary btn-doctor">
                            <text name="<?= strtolower(str_replace(' ', '', $category['Category'])) ?>" x="40%" y="50%" fill="#eceeef" dy=".3em" style="font-size: 24px;"><?= $category['Category'] ?></text>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>
</html>