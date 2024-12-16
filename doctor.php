<!DOCTYPE html>
<html>

<head>
    <title>Информация о враче</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="doctor.css">
</head>

<body>
    <?php
    include 'header.php';
    require_once 'db.php';

    define('PHOTO_DIRECTORY', 'photo_doctors');
    define('BASE_PHOTO_PATH', 'photo_doctors/base.jpg');

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor') {
        echo "<div class='alert alert-danger'>Access Denied</div>";
        exit; 
    }
    
    if (isset($_SESSION['user_id'])) {
        $userID = $_SESSION['user_id'];

        try {
            $stmt = $pdo->prepare("SELECT * 
                                    FROM Doctors D
                                    LEFT JOIN Categories C ON C.CategoryID = D.SpecializationID
                                    WHERE user_id = :userID");
            $stmt->execute(['userID' => $userID]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo 'Ошибка при выполнении запроса: ' . $e->getMessage();
        }

        if ($doctor) {
            $doctorID = $doctor['ID'];
            echo '<div class="doctor-info container mt-5">';
            // if (isset($_SESSION['error'])) {
            //     echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
            //     unset($_SESSION['error']); 
            // }
            if (!is_dir(PHOTO_DIRECTORY)) {
                $_SESSION['error'] = 'Ошибка: папка "' . PHOTO_DIRECTORY . '" не существует.';
            } elseif (!is_readable(PHOTO_DIRECTORY)) {
                $_SESSION['error'] = 'Ошибка: доступ к папке "' . PHOTO_DIRECTORY . '" закрыт.';
            } else {
                if (!empty($doctor['PhotoURL'])) {
                    $photoPath = $doctor['PhotoURL'];
                    echo '<div class="text-center mb-4">';
                    if (file_exists($photoPath)) {
                        if (is_readable($photoPath)) {
                            $imageInfo = @getimagesize($photoPath);
                            if ($imageInfo !== false) {
                                echo '<img src="' . htmlspecialchars($photoPath) . '" alt="Фото врача" class="doctor-photo img-fluid rounded-circle" id="doctorPhoto" style="width: 300px; height: 300px;">';
                            } else {
                                $_SESSION['error'] = 'Ошибка: фото врача повреждено.';
                                echo '<img src="' . BASE_PHOTO_PATH . '" alt="Базовое фото" class="doctor-photo img-fluid rounded-circle" style="width: 300px; height: 300px;">';
                            }
                        } else {
                            $_SESSION['error'] = 'Ошибка: доступ к фото врача закрыт.';
                            echo '<img src="' . BASE_PHOTO_PATH . '" alt="Базовое фото" class="doctor-photo img-fluid rounded-circle" style="width: 300px; height: 300px;">';
                        }
                    } else {
                        $_SESSION['error'] = 'Ошибка: фото врача не найдено. Пожалуйста, проверьте путь к файлу.';
                        echo '<img src="' . BASE_PHOTO_PATH . '" alt="Базовое фото" class="doctor-photo img-fluid rounded-circle" style="width: 300px; height: 300px;">';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="text-center mb-4">';        
                    if (file_exists(BASE_PHOTO_PATH)) {
                        if (is_readable(BASE_PHOTO_PATH)) {
                            echo '<img src="' . BASE_PHOTO_PATH . '" alt="Базовое фото" class="doctor-photo img-fluid rounded-circle" style="width: 300px; height: 300px;">';
                        } else {
                            $_SESSION['error'] = 'Ошибка: доступ к базовому фото закрыт.';
                        }
                    } else {
                        $_SESSION['error'] = 'Ошибка: базовое фото не найдено.';
                    }
                    echo '</div>';
                }

                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                    unset($_SESSION['error']); 
                }

                echo '<div class="text-center mb-4">';
                echo '<button id="uploadPhotoButton" class="btn btn-primary">Загрузить фото</button>';
                
                if (!empty($doctor['PhotoURL']) && !isset($_SESSION['error']) && file_exists($photoPath)) {
                    echo '<form action="upload_photo_doctor.php" method="post" style="display: inline;">';
                    echo '<input type="hidden" name="deleteDoctorID" value="' . $doctorID . '">';
                    echo '<button type="submit" class="btn btn-danger">Удалить изображение</button>';
                    echo '</form>';
                }
                echo '</div>';
                
                echo '<form id="photoUploadForm" action="upload_photo_doctor.php" method="post" enctype="multipart/form-data" class="mb-4" style="display: none;">';
                echo '<input type="hidden" name="doctorID" value="' . $doctorID . '">';
                echo '<div class="form-group" style="display: none;">';
                echo '<input type="file" name="photo" accept="image/*" required class="form-control-file" id="photoInput">';
                echo '</div>';
                echo '<button type="submit" class="btn btn-primary">Загрузить фото</button>';
                echo '</form>';

                echo '<div class="text-center mb-4">';
                echo '<h3>' . htmlspecialchars($doctor['Name']) . '</h3>'; 
                echo '</div>';
            }

            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']); 
            }

            echo '<script>
                document.getElementById("doctorPhoto")?.addEventListener("click", function() {
                    document.getElementById("photoInput").click();
                });
                
                document.getElementById("photoInput").addEventListener("change", function() {
                    document.getElementById("photoUploadForm").submit();
                });
        
                document.getElementById("uploadPhotoButton")?.addEventListener("click", function() {
                    document.getElementById("photoInput").click();
                });
            </script>';
        
            try {
                $schedule = json_decode($doctor['Schedule'], true);
                $stmt_appointments = $pdo->prepare("SELECT * FROM appointments WHERE DoctorID = :doctorID");
                $stmt_appointments->execute(['doctorID' => $doctorID]);
                $appointments = $stmt_appointments->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo 'Ошибка при выполнении запроса: ' . $e->getMessage();
            }

            $merged_schedule = $schedule;
            foreach ($appointments as $appointment) {
                $merged_schedule[$appointment['Date']][] = $appointment['Time'];
            }

            echo '<h3>Рабочий график:</h3>';
            echo '<ul>';

            foreach ($merged_schedule as $date => $slots) {
                echo '<li><strong>' . $date . ':</strong>';
                echo '<ul>';

                foreach ($slots as $time) {
                    $isEditable = true;
                    foreach ($appointments as $appointment) {
                        if ($appointment['Date'] == $date && $appointment['Time'] == $time && $appointment['Status'] !== 'Отклонено') {
                            $isEditable = false;
                            break;
                        }
                    }
                    if ($isEditable) {
                        echo '<li>' . $time . ' - Свободно 
                    <form method="post" action="update_schedule.php">
                        <input type="hidden" name="doctorID" value="' . $doctorID . '">
                        <input type="hidden" name="date" value="' . $date . '">
                        <input type="text" name="new_time" pattern="\d{2}:\d{2}-\d{2}:\d{2}" title="Введите время в формате hh:mm-hh:mm" placeholder="Введите новое время" required>
                        <input type="hidden" name="old_time" value="' . $time . '">
                        <button type="submit" name="submit" value="update">Изменить</button>
                    </form>
                    <form method="post" action="delete_schedule.php">
                        <input type="hidden" name="doctorID" value="' . $doctorID . '">
                        <input type="hidden" name="date" value="' . $date . '">
                        <input type="hidden" name="time" value="' . $time . '">
                        <button type="submit" name="submit" value="delete">Удалить</button>
                    </form>
                </li>';
                    } else {
                        echo '<li>' . $time . ' - ' . $appointment['Status'] . '</li>';
                    }
                }
                echo '</ul></li>';
            }

            echo '<form id="add_schedule_form" method="post" action="add_schedule_date.php">
                <input type="hidden" name="doctorID" value="' . $doctorID . '">
                <label for="new_date">Новая дата:</label>
                <input type="date" name="new_date" required>
                <input type="hidden" name="num_time_intervals" id="num_time_intervals" value=""> 
                <button type="button" id="submit_button">Добавить новую дату</button>
            </form>';

            echo '</ul>';

            $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

            try {
                $query_appointments = "SELECT A.*, P.Name as PatientName, P.DateBirth as PatientBirth, P.PhoneNumber as PatientPhone, P.Sex as PatientSex 
                                    FROM Appointments A 
                                    JOIN Patients P ON A.PatientID = P.ID 
                                    WHERE DoctorID = :doctorID";

                if (!empty($searchTerm)) {
                    $query_appointments .= " AND (P.Name LIKE :searchTerm 
                                        OR P.DateBirth LIKE :searchTerm 
                                        OR P.PhoneNumber LIKE :searchTerm 
                                        OR P.Sex LIKE :searchTerm 
                                        OR A.Date LIKE :searchTerm 
                                        OR A.Time LIKE :searchTerm 
                                        OR A.Description LIKE :searchTerm
                                        OR A.Status LIKE :searchTerm)";
                }

                $stmt = $pdo->prepare($query_appointments);
                $stmt->bindValue(':doctorID', $doctorID);

                if (!empty($searchTerm)) {
                    $stmt->bindValue(':searchTerm', "%$searchTerm%");
                }

                $stmt->execute();
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo 'Ошибка при выполнении запроса: ' . $e->getMessage();
            }

            if ($appointments) {
                echo '<div class="doctor-appointments">';
                echo '<h3>Текущие записи пациентов:</h3>';

                echo "<form method='get' class='search-form'>";
                echo "<input type='text' name='search' placeholder='Поиск записей...' value='" . $searchTerm . "'>";
                echo "<button type='submit' class='btn btn-primary'>Поиск</button>";
                echo "</form>";

                echo '<table>';
                echo '<tr>
                    <th>Пациент</th>
                    <th>Дата рождения пациента</th>
                    <th>Телефон пациента</th>
                    <th>Пол пациента</th>
                    <th>Дата</th>
                    <th>Время</th>
                    <th>Описание</th>
                    <th>Действия</th>
                  </tr>';

                foreach ($appointments as $appointment) {
                    echo '<tr>';
                    echo '<td>' . $appointment['PatientName'] . '</td>';
                    echo '<td>' . $appointment['PatientBirth'] . '</td>';
                    echo '<td>' . $appointment['PatientPhone'] . '</td>';
                    echo '<td>' . $appointment['PatientSex'] . '</td>';
                    echo '<td>' . date('d.m.Y', strtotime($appointment['Date'])) . '</td>';
                    echo '<td>' . $appointment['Time'] . '</td>';
                    echo '<td>' . $appointment['Description'] . '</td>';
                    echo '<td>';
                    if ($appointment['Status'] == 'Одобрено' || $appointment['Status'] == 'Отклонено') {
                        echo $appointment['Status'];
                    } else {
                        echo '<form method="post" action="update_status.php">
                                <input type="hidden" name="appointment_id" value="' . $appointment['ID'] . '">
                                <button class="accept-btn" type="submit" name="accept" value="accept">Принять</button>
                                <button class="reject-btn" type="submit" name="reject" value="reject">Отклонить</button>
                              </form>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            } else {
                if (!empty($searchTerm)) {
                    echo '<h3>Текущие записи пациентов:</h3>';
                    echo "<form method='get' class='search-form'>";
                    echo "<input type='text' name='search' placeholder='Поиск записей...' value='" . $searchTerm . "'>";
                    echo "<button type='submit' class='btn btn-primary'>Поиск</button>";
                    echo "</form>";

                    echo "<div class='no-records'>Ничего не найдено...</div>";
                } else {
                    echo 'У врача нет текущих записей пациентов.';
                }
            }

            echo '</div>';
        } else {
            echo 'Доктор не найден.';
        }
    } else {
        echo 'Некорректный запрос.';
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.querySelector('#add_schedule_form');
            var newDateInput = document.querySelector('input[name="new_date"]');
            var numTimeIntervalsInput = document.getElementById('num_time_intervals');
            var submitButton = document.getElementById('submit_button');

            submitButton.addEventListener('click', function (event) {
                if (!newDateInput.value) {
                    alert("Надлежит указать дату!");
                    return;
                }

                var numTimeIntervals = prompt("Укажите количество временных промежутков (от 1 до 5):");
                numTimeIntervals = parseInt(numTimeIntervals) || 1;
                numTimeIntervals = Math.min(Math.max(numTimeIntervals, 1), 5);

                numTimeIntervalsInput.value = numTimeIntervals;
                form.submit();
            });
        });
    </script>
</body>

</html>