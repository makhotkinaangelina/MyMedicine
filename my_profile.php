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

    define('BASE_PHOTO_PATH', 'photo_doctors/base.jpg');
    define('BASE_PHOTO_DIR', 'photo_doctors');

    $user_id = $_SESSION['user_id'];

    try {
        $query_patients = "SELECT * FROM patients WHERE userid = ?";
        $stmt_patients = $pdo->prepare($query_patients);
        $stmt_patients->execute([$user_id]);
        $result_patients = $stmt_patients->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo '<p class="error-message">Ошибка при получении данных пациента: ' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }

    if (count($result_patients) > 0) {
        $row = $result_patients[0];
        $name = $row['Name'];
        $dob = $row['DateBirth'];
        $phone = $row['PhoneNumber'];
        $gender = $row['Sex'];
        $photo = $row['photo'];

        if (isset($_SESSION['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_SESSION['error']) . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p class="success-message">' . htmlspecialchars($_SESSION['success']) . '</p>';
            unset($_SESSION['success']);
        }

        echo '<div class="container">
        <h2>Карточка пациента:</h2>';

        function displayBasePhoto()
        {
            if (is_dir(BASE_PHOTO_DIR)) {
                if (is_readable(BASE_PHOTO_DIR)) {
                    if (file_exists(BASE_PHOTO_PATH)) {
                        if (is_readable(BASE_PHOTO_PATH)) {
                            $basePhotoData = base64_encode(file_get_contents(BASE_PHOTO_PATH));
                            echo '<img src="data:image/jpeg;base64,' . htmlspecialchars($basePhotoData) . '" alt="Базовое фото" style="max-width: 200px; height: auto;"><br>';
                        } else {
                            echo '<p style="color: red;">Ошибка: базовое фото недоступно для чтения. Проверьте права доступа.</p>';
                        }
                    } else {
                        echo '<p style="color: red;">Ошибка: базовое фото не найдено. Проверьте наличие файла base.jpg.</p>';
                    }
                } else {
                    echo '<p style="color: red;">Ошибка: доступ к папке с базовыми фотографиями закрыт. Проверьте настройки доступа.</p>';
                }
            } else {
                echo '<p style="color: red;">Ошибка: папка с базовыми фотографиями не существует. Проверьте путь к папке.</p>';
            }
        }

        if (isset($photo) && !empty($photo)) {
            $base64Image = base64_encode($photo);
            $imageInfo = @getimagesizefromstring($photo);
            
            if ($imageInfo !== false) {
                echo '<img src="data:image/jpeg;base64,' . htmlspecialchars($base64Image) . '" alt="Фото пациента" style="max-width: 200px; height: auto;"><br><br>';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $tempFile = tempnam(sys_get_temp_dir(), 'img');
                file_put_contents($tempFile, $photo);
                
                $mimeType = finfo_file($finfo, $tempFile);
                finfo_close($finfo);
                unlink($tempFile); 
        
                if ($mimeType === false || strpos($mimeType, 'image/') === false) {
                    echo '<p style="color: red;">Ошибка: загруженный файл не является изображением.</p>';
                } else {
                    echo '<p style="color: red;">Ошибка: изображение повреждено.</p>';
                }
                displayBasePhoto(); 
            }
        } else {
            displayBasePhoto();
        }

        echo '<p><strong>ФИО:</strong> ' . htmlspecialchars($name) . '</p>
              <p><strong>Дата рождения:</strong> ' . htmlspecialchars($dob) . '</p>
              <p><strong>Номер телефона:</strong> ' . htmlspecialchars($phone) . '</p>
              <p><strong>Пол:</strong> ' . htmlspecialchars($gender) . '</p>
              <button id="edit-btn" class="btn btn-primary" onclick="toggleEditForm()">Редактировать</button>
              </div>';

        echo '<div id="edit-form" style="display: none;">
              <form action="patient_form.php" method="post" enctype="multipart/form-data">
              <input type="hidden" name="user_id" value="' . htmlspecialchars($row['UserID']) . '">

              <label for="name">ФИО:</label>
              <input type="text" id="name" name="name" value="' . htmlspecialchars($name) . '" required><br><br>

              <label for="dob">Дата рождения:</label>
              <input type="date" id="dob" name="dob" value="' . htmlspecialchars($dob) . '" required><br><br>

              <label for="phone">Номер телефона (Беларусь):</label>
              <input type="tel" id="phone" name="phone" value="' . htmlspecialchars($phone) . '" pattern="\+375[0-9]{9}" placeholder="+375XXXXXXXXX" required><br><br>

              <label for="gender">Пол:</label>
              <select id="gender" name="gender" required>
                  <option value="мужской" ' . ($gender === 'мужской' ? 'selected' : '') . '>мужской</option>
                  <option value="женский" ' . ($gender === 'женский' ? 'selected' : '') . '>женский</option>
              </select><br><br>';

        if ($photo) {
            $imageInfo = @getimagesizefromstring($photo);
            if ($imageInfo !== false) {
                $base64Image = base64_encode($photo);
                echo '<img src="data:image/jpeg;base64,' . htmlspecialchars($base64Image) . '" alt="Фото пациента" style="max-width: 200px; height: auto;"><br>';
                echo '<button type="submit" name="delete_photo" value="1" class="btn btn-danger">Удалить текущее фото</button><br><br>';
            }
        }

        echo '<label for="photo">Загрузить новое фото:</label>
              <input type="file" id="photo" name="photo" accept="image/*"><br><br>

              <button type="submit" class="btn btn-primary" name="submit">Обновить</button>
              </form>
              </div>';

        echo '<script>
              function toggleEditForm() {
                  var form = document.getElementById("edit-form");
                  form.style.display = (form.style.display === "none") ? "block" : "none";
              }
              </script>';

        try {
            $query_select_patientID = "SELECT ID FROM patients WHERE UserID = ?";
            $stmt_select_patientID = $pdo->prepare($query_select_patientID);
            $stmt_select_patientID->execute([$user_id]);
            $patientID_row = $stmt_select_patientID->fetch(PDO::FETCH_ASSOC);

            if ($patientID_row) {
                $patientID = $patientID_row['ID'];
                $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

                $query_appointments = "SELECT appointments.id, doctors.Name as doctor_name, categories.Category, appointments.Status, appointments.Date, appointments.Time, appointments.Description, appointments.doctorid,
                ratings.rating as average_rating
                                   FROM appointments 
                                   JOIN doctors on doctors.id = appointments.doctorid
                                   JOIN categories on categories.categoryid = doctors.specializationid
                                   LEFT JOIN ratings on ratings.appointment_id = appointments.id
                                   WHERE PatientID = :patientID";

                if (!empty($searchTerm)) {
                    $query_appointments .= " AND (doctors.Name LIKE :searchTerm 
                                            OR categories.Category LIKE :searchTerm 
                                            OR appointments.Status LIKE :searchTerm
                                            OR appointments.Date LIKE :searchTerm 
                                            OR appointments.Time LIKE :searchTerm
                                            OR appointments.Description LIKE :searchTerm
                                            )";
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
                    echo "<input type='text' name='search' placeholder='Поиск записей...' value='" . htmlspecialchars($searchTerm) . "'>";
                    echo "<button type='submit' class='btn btn-primary'>Поиск</button>";
                    echo "</form>";

                    echo "<table>";
                    echo "<tr><th>Дата</th><th>Время</th><th>Комментарий</th><th>Имя доктора</th><th>Специализация</th><th>Статус</th><th>Действия</th></tr>";

                    foreach ($result_appointments as $appointment) {
                        echo "<tr id=appointment_$appointment[id]>";
                        echo "<td>{$appointment['Date']}</td>";
                        echo "<td>{$appointment['Time']}</td>";
                        echo "<td>{$appointment['Description']}</td>";
                        echo "<td>{$appointment['doctor_name']}</td>";
                        echo "<td>{$appointment['Category']}</td>";
                        echo "<td>{$appointment['Status']}</td>";

                        if ($appointment['Status'] === 'В обработке') {
                            echo "<td><form method='post' action='cancel_appointment.php'>";
                            echo "<input type='hidden' name='appointment_id' value='{$appointment['id']}'>";
                            echo "<button type='submit' class='btn btn-cancel btn-danger'>Отменить</button>";
                            echo "</form></td>";
                        } else if ($appointment['Status'] === 'Одобрено' && strtotime($appointment['Date']) < time()) {
                            if (empty($appointment['average_rating'])) {
                                echo "<td>
                                    <form method='post' action='rate_appointment.php'>
                                        <input type='hidden' name='appointment_id' value='{$appointment['id']}'>
                                        <input type='hidden' name='doctor_id' value='{$appointment['doctorid']}'>
                                        <label for='rating'>Оценка:</label>
                                        <select name='rating' required>
                                            <option value='1'>1</option>
                                            <option value='2'>2</option>
                                            <option value='3'>3</option>
                                            <option value='4'>4</option>
                                            <option value='5'>5</option>
                                        </select>
                                        <textarea name='comment' placeholder='Ваш комментарий'></textarea>
                                        <button type='submit' name='rate' class='btn btn-primary'>Оценить</button>
                                    </form>
                                    </td>";
                            } else {
                                echo "<td>Оценено: " . htmlspecialchars($appointment['average_rating']) . "</td>";
                            }
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    if (!empty($searchTerm)) {
                        echo "<h1>Записи пациента</h1>";

                        echo "<form method='get' class='search-form'>";
                        echo "<input type='text' name='search' placeholder='Поиск записей...' value='" . htmlspecialchars($searchTerm) . "'>";
                        echo "<button type='submit' class='btn btn-primary'>Поиск</button>";
                        echo "</form>";

                        echo "<div class='no-records'>Ничего не найдено...</div>";
                    } else {
                        echo "<div class='no-records'>Вы еще не записаны ни на один прием.</div>";
                    }
                }
            } else {
                echo "Пациент не найден";
            }

        } catch (PDOException $e) {
            echo '<p class="error-message">Ошибка при получении данных о записях: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }

    } else {
        echo '<div class="container">
        <h2>Пожалуйста, заполните карточку пациента!</h2>';
        if (isset($error)) {
            echo '<p class="error-message">' . htmlspecialchars($error) . '</p>';
        }
        echo '<form action="patient_form.php" method="post">
            <label for="name">ФИО:</label>
            <input type="text" id="name" name="name" value="" required><br><br>
            
            <label for="dob">Дата рождения:</label>
            <input type="date" id="dob" name="dob" value="" required><br><br>

            <label for="phone">Номер телефона (Беларусь):</label>
            <input type="tel" id="phone" name="phone" value="" pattern="\+375[0-9]{9}" placeholder="+375XXXXXXXXX" required><br><br>

            <label for="gender">Пол:</label>
            <select id="gender" name="gender" required>
                <option value="мужской">мужской</option>
                <option value="женский">женский</option>
            </select><br><br>

            <label for="photo">Фото:</label>
            <input type="file" id="photo" name="photo" accept="image/*"><br><br>

            <button type="submit" class="btn btn-primary" name="submit">Сохранить</button>
        </form>
    </div>';
    }

    if (isset($_SESSION['rating_error'])) {
        echo '<p class="error-message">' . htmlspecialchars($_SESSION['rating_error']) . '</p>';
        unset($_SESSION['rating_error']);
    }

    if (isset($_SESSION['rating_success'])) {
        echo '<p class="success-message">' . htmlspecialchars($_SESSION['rating_success']) . '</p>';
        unset($_SESSION['rating_success']);
    }
    ?>
    <script>
        document.getElementById('edit-btn').addEventListener('click', function () {
            document.getElementById('edit-btn').style.display = 'none';
            document.getElementById('edit-form').style.display = 'block';
        });
    </script>
</body>

</html>