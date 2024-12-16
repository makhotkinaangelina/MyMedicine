<!DOCTYPE html>
<html>

<head>
    <title>Мой проект HTML</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="appointments.css">
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="appointments.js"></script>

<body>

    <div class="container">
        <?php
        include 'header.php';
        require_once 'db.php';
        require_once 'functions.php';

        $weights = getWeights($pdo);
        $categories = get_categories($pdo);

        if (isset($_GET['error'])) {
            $errorMessage = urldecode($_GET['error']);
            echo '<div class="error-message">' . $errorMessage . '</div>';
        }

        $searchName = isset($_GET['search']) ? $_GET['search'] : '';
        $specialization = isset($_GET['specialization']) ? $_GET['specialization'] : '';

        //$doctors = getDoctorsInfoSlow($pdo, $weights, $specialization, $searchName);
        $doctors = getDoctorsInfoSlow($pdo, $weights, $specialization, $searchName);

        $url = "appointments.php?specialization=" . urlencode($specialization);

        $isUserLoggedIn = isset($_SESSION['user_id']);

        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $cookieName = 'searchHistory' . ($userId ? '_' . $userId : '');

        $searchHistoryJson = isset($_COOKIE[$cookieName]) ? decryptData($_COOKIE[$cookieName], $userId) : '[]';
        $searchHistory = json_decode($searchHistoryJson, true) ?: [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isUserLoggedIn && isset($_POST['clear_history'])) {
            setcookie($cookieName, '', time() - 3600, "/");
            header("Location: " . $url);
            exit;
        }

        if (isset($_GET['search'])) {
            $searchName = trim($_GET['search']);

            if (!empty($searchName)) {
                if (!in_array($searchName, $searchHistory)) {
                    array_unshift($searchHistory, $searchName);

                    if (count($searchHistory) > 10) {
                        array_pop($searchHistory);
                    }

                    $encryptedHistory = encryptData(json_encode($searchHistory), $userId);
                    setcookie($cookieName, $encryptedHistory, time() + (86400 * 30), "/");
                }
            }
        }

        if ($isUserLoggedIn) {
            echo '<div class="form-container">';
            echo '<form method="get" action="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="specialization" value="' . htmlspecialchars($specialization, ENT_QUOTES, 'UTF-8') . '">';
            echo '<select name="customSearch" onchange="this.form.search.value=this.value;">';
            echo '<option value="" disabled selected>Выберите из истории поиска</option>';

            foreach ($searchHistory as $item) {
                // Экранирование элемента истории поиска
                $encodedItem = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
                echo '<option value="' . $encodedItem . '"';
                if ($searchName === $item) {
                    echo ' selected';
                }
                echo '>' . $encodedItem . '</option>';
            }

            echo '</select>'; // Перенесено сюда
        
            // Экранирование имени поиска
            echo '<input type="text" name="search" value="' . htmlspecialchars($searchName, ENT_QUOTES, 'UTF-8') . '" placeholder="Поиск по имени" maxlength="50">';
            echo '<input type="submit" value="Искать">';
            echo '</form>';

            // Форма для очистки истории поиска
            echo '<form method="post" action="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="display:inline;">';
            echo '<input type="hidden" name="clear_history" value="1">';
            echo '<input type="submit" value="Очистить историю поиска" onclick="return confirm(\'Вы уверены, что хотите очистить историю поиска?\');">';
            echo '</form>';

        } else {
            echo '<div class="form-container">';
            echo '<form method="get" action="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="hidden" name="specialization" value="' . htmlspecialchars($specialization, ENT_QUOTES, 'UTF-8') . '">';
            echo '<select name="customSearch" onchange="this.form.search.value=this.value;">';
            echo '<option value="" disabled selected>Выберите из истории поиска</option>';
            echo '</select>'; // Нужно закрыть здесь, если пользователь не вошел
            echo '<input type="text" name="search" value="' . htmlspecialchars($searchName, ENT_QUOTES, 'UTF-8') . '" placeholder="Поиск по имени" maxlength="50">';
            echo '<input type="submit" value="Искать">';
            echo '</form>';
        }

        echo '</div>';

        if (count($doctors) > 0) {
            echo '<table>';
            echo '<tr><th>ФИО врача, стаж работы</th><th>Дата и время работы</th>';
            echo '<th>Действия</th>';
            echo '</tr>';

            foreach ($doctors as &$doctor) {
                echo '<tr><td class="doctor-name" id="doctor_name_' . $doctor['DoctorID'] . '">';
                echo $doctor['Name'] . ", " . $doctor['Experience'];
                if (isset($doctor['AverageRating']) && $doctor['AverageRating']) {
                    echo ' <span class="rating">';
                    for ($i = 0; $i < 5; $i++) {
                        if ($i < $doctor['AverageRating']) {
                            echo '★';
                        }
                    }
                    echo '</span>';
                } else {
                    echo ' <span class="rating">Нет рейтинга</span>';
                }

                echo '</td><td>';
                $schedule = json_decode($doctor['Schedule'], true);
                if ($schedule) {
                    foreach ($schedule as $date => $times) {
                        echo '<div>';
                        echo '<p id="selected_date_' . $doctor['DoctorID'] . '">' . date('d.m.Y', strtotime($date)) . '</p>';
                        echo '<ul>';
                        foreach ($times as $time) {
                            echo '<li class="li-radio-btn"><input type="radio" name="selected_time_' . $doctor['DoctorID'] . '" value="' . $time . '"> ' . $time . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                } else {
                    echo 'Расписание отсутствует';
                }

                echo '</td>';

                if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === "admin") {
                    echo '<td>
                        <button class="btn-small" onclick="editRecord(' . $doctor['DoctorID'] . ')">Редактировать</button>
                        <input type="hidden" name="doctor_id" value="' . $doctor['DoctorID'] . '">
                        <button type="submit" class="btn-small" name="delete_doctor" onclick="confirmDelete(' . $doctor['DoctorID'] . ')">Удалить</button>
                      </td>';
                } else {
                    echo '<td>
                <form id="appointment_form_' . $doctor['DoctorID'] . '" method="post" action="submit_appointment.php">
                    <input type="hidden" name="doctor_id" id="doctor_id_' . $doctor['DoctorID'] . '" value="' . $doctor['DoctorID'] . '">
                    <input type="hidden" name="date_' . $doctor['DoctorID'] . '" id="date_' . $doctor['DoctorID'] . '" value="">
                    <input type="hidden" name="time_' . $doctor['DoctorID'] . '" id="time_' . $doctor['DoctorID'] . '" value="">
                    <input type="hidden" name="comment_' . $doctor['DoctorID'] . '" id="comment_' . $doctor['DoctorID'] . '" value="">
                    <button class="btn-small" type="button" onclick="submitAppointment(' . $doctor['DoctorID'] . ')">Записаться</button>
                </form>
                    <button id="update_button_' . $doctor['DoctorID'] . '" class="btn-small" type="button" onclick="getDoctorInfo(' . $doctor['DoctorID'] . ')">Обновить</button>     
                    </td>';
                }

                echo '</tr>';
                echo '<tr><td colspan="3" class="divider"></td></tr>';
            }

            echo '</table>';
        } else {
            echo 'Врачи не найдены.';
        }
        ?>
    </div>

    <div id="commentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('commentModal')">&times;</span>
            <textarea id="commentInput" placeholder="Место для вашего комментария к заявке на прием."
                maxlength="200"></textarea>
            <button class="btn-sub" id="submitCommentBtn" onclick="submitComment(doctorId)">Отправить</button>
        </div>
    </div>

    <div id="editDoctorModal" class="modal">
        <div class="modalwindow-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Изменить данные доктора</h2>

            <form id="editForm" action="update_doctor.php" method="POST">
                <input type="hidden" id="doctor_id" name="doctor_id">

                <label for="name">ФИО:</label>
                <input type="text" id="name" name="name" required>

                <label for="specializationID">Специализация:</label>
                <select name="specializationID" id="specializationID" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['CategoryID']; ?>"><?php echo $category['Category']; ?></option>
                    <?php endforeach; ?>
                </select><br>

                <label for="date_birth">Дата рождения:</label>
                <input type="date" id="date_birth" name="date_birth">

                <label for="experience_start">Дата начала работы:</label>
                <input type="date" id="experience_start" name="experience_start">

                <label for="scheduleDates">Дата и время работы:</label>
                <div id="scheduleDates">

                </div>
                <input type="hidden" id="schedule" name="schedule">
                <button type="button" onclick="addScheduleDate()">Добавить дату</button>

                <input type="submit" id="saveButton" value="Сохранить">
            </form>
        </div>
    </div>
    <script>
        function updateSearchHistory(value) {
            let searchHistory = JSON.parse(getCookie("searchHistory") || "[]");

            if (!searchHistory.includes(value)) {
                searchHistory.unshift(value);

                if (searchHistory.length > 10) {
                    searchHistory.pop();
                }

                document.cookie = "searchHistory=" + JSON.stringify(searchHistory) + "; path=/; max-age=" + (30 * 24 * 60 * 60);
            }
        }

        function getCookie(name) {
            let cookieArr = document.cookie.split(";");
            for (let i = 0; i < cookieArr.length; i++) {
                let cookiePair = cookieArr[i].split("=");
                if (name == cookiePair[0].trim()) {
                    return decodeURIComponent(cookiePair[1]);
                }
            }
            return null;
        }

        document.querySelector('select[name="customSearch"]').addEventListener('change', function () {
            updateSearchHistory(this.value);
        });

        function editRecord(id) {
            var doctor = <?php echo json_encode($doctors); ?>;
            var selectedDoctor = doctor.find(function (doc) {
                return doc.id === id;
            });

            if (selectedDoctor) {
                document.getElementById('doctor_id').value = selectedDoctor.id;
                document.getElementById('name').value = selectedDoctor.Name;
                document.getElementById('specializationID').value = selectedDoctor.SpecializationID;
                document.getElementById('date_birth').value = selectedDoctor.DateBirth;
                document.getElementById('experience_start').value = selectedDoctor.ExperienceStart;
                document.getElementById('schedule').value = selectedDoctor.Schedule;

                var scheduleInputs = '';
                var schedule = JSON.parse(selectedDoctor.Schedule);

                if (schedule && typeof schedule === 'object') {
                    for (const date in schedule) {
                        scheduleInputs += '<div>';
                        scheduleInputs += '<p data-date="' + date + '">' + date + '</p>';
                        scheduleInputs += '<ul>';
                        schedule[date].forEach(function (time) {
                            scheduleInputs += '<li class="li-radio-btn">от <input type="time" class="start-time-input" name="schedule[' + date + '][]" value="' + time.split('-')[0] + '" onchange="handleTimeChange(this)"> до <input type="time" class="end-time-input" name="schedule[' + date + '][]" value="' + time.split('-')[1] + '" onchange="handleTimeChange(this)"></li>';
                        });
                        scheduleInputs += '</ul>';
                        scheduleInputs += '<button type="button" onclick="removeScheduleDate(\'' + id + '\', \'' + date + '\')">Удалить дату</button>';
                        scheduleInputs += '</div>';
                    }
                    document.getElementById('scheduleDates').innerHTML = scheduleInputs;
                } else {
                    console.error("Schedule is not in the correct format.");
                }

                var modal = document.getElementById('editDoctorModal');
                if (modal) {
                    modal.style.display = "block";
                } else {
                    console.error("Modal element not found.");
                }
            }
        }
    </script>
</body>

</html>