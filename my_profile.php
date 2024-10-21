<!DOCTYPE html>
<html>
<head>
    <title>MyMedicine Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="my_profile.css">
</head>
    <body>
        
    <?php
    include 'header.php';

    $name = $dob = $phone = $gender = $error = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once 'db.php';

        $name = $_POST['name'];
        $dob = $_POST['dob'];
        $phone = $_POST['phone'];
        $gender = $_POST['gender'];
        $user_id = $_SESSION['user_id'];

        if (empty($name) || empty($dob) || empty($phone) || empty($gender)) {
            $error = "Все поля формы должны быть заполнены.";
        } else {
            try {
                $query_check = "SELECT * FROM patients WHERE userid = ?";
                $stmt_check = $pdo->prepare($query_check);
                $stmt_check->execute([$user_id]);
                $result_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
                if ($result_check) {
                    $query = "UPDATE patients SET Name = ?, DateBirth = ?, PhoneNumber = ?, Sex = ? WHERE userid = ?";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$name, $dob, $phone, $gender, $user_id]);
                    $success_message = "Данные успешно обновлены.";
                } else {
                    $query = "INSERT INTO patients (userid, Name, DateBirth, PhoneNumber, Sex) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$user_id, $name, $dob, $phone, $gender]);
                    $success_message = "Данные успешно добавлены.";
                }
            } catch (PDOException $e) {
                $error = "Ошибка при сохранении данных в базе данных: " . $e->getMessage();
            }
        }
    }

    $user_id = $_SESSION['user_id'];
    $query_patients = "SELECT * FROM patients WHERE userid = ?";
    $stmt_patients = $pdo->prepare($query_patients);

    if ($stmt_patients === false) {
        echo 'Ошибка подготовки запроса: ' . $pdo->errorInfo()[2];
    } else {
        $stmt_patients->execute([$user_id]);
        $result_patients = $stmt_patients->fetchAll(PDO::FETCH_ASSOC);

        if (count($result_patients) > 0) {
            $row = $result_patients[0]; 
            $name = $row['Name'];
            $dob = $row['DateBirth'];
            $phone = $row['PhoneNumber'];
            $gender = $row['Sex'];

            if ($error) {
                echo '<p class="error-message">' . $error . '</p>';
            }
            echo '<div class="container">
            <h2>Карточка пациента:</h2>
            <p><strong>ФИО:</strong> ' . $name . '</p>
            <p><strong>Дата рождения:</strong> ' . $dob . '</p>
            <p><strong>Номер телефона:</strong> ' . $phone . '</p>
            <p><strong>Пол:</strong> ' . $gender . '</p>
            
            <button id="edit-btn" class="btn btn-primary">Редактировать</button>
            
            <form id="edit-form" action="" method="post" style="display: none;">
                <input type="hidden" name="user_id" value="' . $row['UserID'] . '">
                <label for="name">ФИО:</label>
                <input type="text" id="name" name="name" value="' . $name . '" required><br><br>
                
                <label for="dob">Дата рождения:</label>
                <input type="date" id="dob" name="dob" value="' . $dob . '" required><br><br>
    
                <label for="phone">Номер телефона (Беларусь):</label>
                <input type="tel" id="phone" name="phone" value="' . $phone . '" pattern="\+375[0-9]{9}" placeholder="+375XXXXXXXXX" required><br><br>
    
                <label for="gender">Пол:</label>
                <select id="gender" name="gender" required>
                    <option value="мужской" ' . ($gender === 'мужской' ? 'selected' : '') . '>мужской</option>
                    <option value="женский" ' . ($gender === 'женский' ? 'selected' : '') . '>женский</option>
                </select><br><br>
    
                <button type="submit" class="btn btn-primary" name="submit">Обновить</button>
            </form>
        </div>';

        try {
            $query_select_patientID = "SELECT ID FROM patients WHERE UserID = ?";
            $stmt_select_patientID = $pdo->prepare($query_select_patientID);
            $stmt_select_patientID->execute([$user_id]);
            $patientID_row = $stmt_select_patientID->fetch(PDO::FETCH_ASSOC);
        
            if ($patientID_row) {
                $patientID = $patientID_row['ID'];

                $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
        
                $query_appointments = "SELECT appointments.id, doctors.Name, categories.Category, appointments.Status, appointments.Date, appointments.Time, appointments.Description  
                                       FROM appointments 
                                       JOIN doctors on doctors.id = appointments.doctorid
                                       JOIN categories on categories.categoryid = doctors.specializationid
                                       WHERE PatientID = :patientID";

                if (!empty($searchTerm)) {
                    $query_appointments .= " AND (doctors.Name LIKE :searchTerm 
                                            OR categories.Category LIKE :searchTerm 
                                            OR appointments.Status LIKE :searchTerm
                                            OR appointments.Date LIKE :searchTerm 
                                            OR appointments.Time LIKE :searchTerm
                                            OR appointments.Description LIKE :searchTerm
                                            )" ;
                }

                $query_appointments .= " ORDER BY appointments.Date";

                $stmt_appointments = $pdo->prepare($query_appointments);
                $stmt_appointments->bindValue(':patientID', $patientID);

                if (!empty($searchTerm)) {
                    $stmt_appointments->bindValue(':searchTerm', "%$searchTerm%");
                }

                $stmt_appointments->execute();
                $result_appointments = $stmt_appointments->fetchAll(PDO::FETCH_ASSOC);

                if ($result_appointments) {
                    echo "<h1>Записи пациента</h1>";
                    
                    echo "<form method='get' class='search-form'>";
                    echo "<input type='text' name='search' placeholder='Поиск записей...' value='" . $searchTerm . "'>";
                    echo "<button type='submit' class='btn btn-primary'>Поиск</button>";
                    echo "</form>";

                    echo "<table>";
                    echo "<tr><th>Дата</th><th>Время</th><th>Комментарий</th><th>Имя доктора</th><th>Специализация</th><th>Статус</th><th>Действия</th></tr>";

                    foreach ($result_appointments as $appointment) {
                        echo "<tr id=appointment_$appointment[id]>";
                        echo "<td>{$appointment['Date']}</td>";
                        echo "<td>{$appointment['Time']}</td>";
                        echo "<td>{$appointment['Description']}</td>";
                        echo "<td>{$appointment['Name']}</td>"; 
                        echo "<td>{$appointment['Category']}</td>";
                        echo "<td>{$appointment['Status']}</td>"; 

                        if ($appointment['Status'] !== 'Отклонено') {
                            echo "<td><form method='post' action='cancel_appointment.php'>"; 
                            echo "<input type='hidden' name='appointment_id' value='{$appointment['id']}'>";
                            echo "<button type='submit' class='btn btn-cancel btn-danger'>Отменить</button>";
                            echo "</form></td>";
                        }
                    
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                } else {
                    if (!empty($searchTerm)) {
                        echo "<h1>Записи пациента</h1>";
                    
                        echo "<form method='get' class='search-form'>";
                        echo "<input type='text' name='search' placeholder='Поиск записей...' value='" . $searchTerm . "'>";
                        echo "<button type='submit' class='btn btn-primary'>Поиск</button>";
                        echo "</form>";

                        echo "<div class='no-records'>Ничего не найдено...</div>";
                    }
                    else {
                        echo "<div class='no-records'>Вы еще не записаны ни на один прием.</div>";
                    }
                }
            } else {
                echo "Пациент не найден";
            }

        } catch (PDOException $e) {
            $error = "Ошибка при выполнении запроса: " . $e->getMessage();
        }

        }
        else {
            echo '<div class="container">
                <h2>Пожалуйста, заполните карточку пациента!</h2>';
            if ($error) {
                echo '<p class="error-message">' . $error . '</p>';
            }
            echo '<form action="" method="post">
                    <label for="name">ФИО:</label>
                    <input type="text" id="name" name="name" value="' . $name . '" required><br><br>
                    
                    <label for="dob">Дата рождения:</label>
                    <input type="date" id="dob" name="dob" value="' . $dob . '" required><br><br>

                    <label for="phone">Номер телефона (Беларусь):</label>
                    <input type="tel" id="phone" name="phone" value="' . $phone . '" pattern="\+375[0-9]{9}" placeholder="+375XXXXXXXXX" required><br><br>

                    <label for="gender">Пол:</label>
                    <select id="gender" name="gender" required>
                        <option value="мужской" ' . ($gender === 'мужской' ? 'selected' : '') . '>мужской</option>
                        <option value="женский" ' . ($gender === 'женский' ? 'selected' : '') . '>женский</option>
                    </select><br><br>

                    <button type="submit" class="btn btn-primary" name="submit">Сохранить</button>
                </form>
            </div>';
        }
    }
    ?>
    <script>
        document.getElementById('edit-btn').addEventListener('click', function() {
            document.getElementById('edit-btn').style.display = 'none';
            document.getElementById('edit-form').style.display = 'block';
        });
    </script>
    </body>
</html>