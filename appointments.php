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

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['doctor_id'])) {
        $doctorId = $_POST['doctor_id'];
    
        try {
            $stmt = $pdo->prepare("CALL GetDoctorById(:doctorId)");
            $stmt->bindParam(':doctorId', $doctorId, PDO::PARAM_INT);
    
            if ($stmt->execute()) {
                $doctorData = $stmt->fetch(PDO::FETCH_ASSOC);
                header('Content-Type: application/json');
                echo json_encode($doctorData);
            } else {
                echo json_encode(["error" => "Ошибка выполнения процедуры для врача с идентификатором $doctorId"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["error" => "Ошибка при вызове процедуры: " . $e->getMessage()]);
        }
    }
  
    $categories = get_categories($pdo);

    if (isset($_GET['error'])) {
        $errorMessage = urldecode($_GET['error']);
        echo '<div class="error-message">' . $errorMessage . '</div>';
    }
    
    $searchName = isset($_GET['search']) ? $_GET['search'] : '';
    $specialization = isset($_GET['specialization']) ? $_GET['specialization'] : '';

    $stmt = $pdo->prepare("CALL GetDoctorsBySpecializationAndName(:specialization, :searchName)");
    $stmt->bindParam(':specialization', $specialization, PDO::PARAM_STR);
    $stmt->bindParam(':searchName', $searchName, PDO::PARAM_STR);
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    usort($doctors, function($a, $b) {
        $aFreeSlots = countFreeSlots($a['Schedule']);
        $bFreeSlots = countFreeSlots($b['Schedule']);
    
        if ($a['Experience'] == $b['Experience']) {
            return $bFreeSlots - $aFreeSlots; 
        } else {
            return $b['Experience'] - $a['Experience']; 
        }
    });
    
    function countFreeSlots($schedule) {
        $freeSlots = 0;
        $decodedSchedule = json_decode($schedule, true);
    
        if (is_array($decodedSchedule) && !empty($decodedSchedule)) {
            foreach ($decodedSchedule as $date => $slots) {
                foreach ($slots as $slot) {
                    $freeSlots++;
                }
            }
        }
    
        return $freeSlots;
    }

    $url = "appointments.php?specialization={$specialization}";

    if (isset($_SESSION['searchHistory']) && is_array($_SESSION['searchHistory'])) {
        $searchHistory = $_SESSION['searchHistory'];
    } else {
        $searchHistory = [];
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchName = $_GET['search'];
        if (!in_array($searchName, $searchHistory)) {
            array_unshift($searchHistory, $searchName);
            if (count($searchHistory) > 5) {
                array_pop($searchHistory);
            }
        }
    } 

    $_SESSION['searchHistory'] = $searchHistory;

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['doctor_id'])) {
        $doctorId = $_POST['doctor_id'];
    
        $stmt = $pdo->prepare("CALL GetDoctorById(:doctorId)");
        $stmt->bindParam(':doctorId', $doctorId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $doctorData = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($doctorData);
        } else {
            echo json_encode(["error" => "Ошибка выполнения процедуры для врача с идентификатором $doctorId"]);
        }
    }

    echo '<div class="form-container">';
    echo '<form method="get" action="' . $url . '">';
    echo '<input type="hidden" name="specialization" value="' . $specialization . '">';
    echo '<select name="customSearch" onchange="this.form.search.value=this.value;">';
    echo '<option value="">Выберите из истории поиска</option>';
    foreach ($searchHistory as $item) {
        echo '<option value="' . $item . '"';
        if ($searchName === $item) {
            echo ' selected';
        }
        echo '>' . $item . '</option>';
    }
    echo '</select>';
    echo '<input type="text" name="search" value="' . $searchName . '" placeholder="Поиск по имени" maxlength="50">';
    echo '<input type="submit" value="Искать">';
    echo '</form>';
    echo '</div>';

    if (count($doctors) > 0) {
        echo '<table>';
        echo '<tr><th>ФИО врача, стаж работы</th><th>Дата и время работы</th>';
        echo '<th>Действия</th>';
        echo '</tr>';
    
        foreach ($doctors as $doctor) {
            echo '<tr><td class="doctor-name" id="doctor_name_' . $doctor['id'] . '">' . $doctor['Name'] . ", " . $doctor['Experience'] . '</td><td>';
            $schedule = json_decode($doctor['Schedule'], true);
            if ($schedule) {
                foreach ($schedule as $date => $times) { 
                    echo '<div>';           
                    echo '<p id="selected_date_' . $doctor['id'] . '">' . date('d.m.Y', strtotime($date)) . '</p>';
                    echo '<ul>';
                    foreach ($times as $time) {
                        echo '<li class="li-radio-btn"><input type="radio" name="selected_time_' . $doctor['id'] . '" value="' . $time . '"> ' . $time . '</li>';
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
                        <button class="btn-small" onclick="editRecord('.$doctor['id'].')">Редактировать</button>
                        <input type="hidden" name="doctor_id" value="'.$doctor['id'].'">
                        <button type="submit" class="btn-small" name="delete_doctor" onclick="confirmDelete('.$doctor['id'].')">Удалить</button>
                      </td>';
            } else {
                echo '<td>
                <form id="appointment_form_'.$doctor['id'].'" method="post" action="submit_appointment.php">
                    <input type="hidden" name="doctor_id" id="doctor_id_' . $doctor['id'] . '" value="'.$doctor['id'].'">
                    <input type="hidden" name="date_'.$doctor['id'].'" id="date_' . $doctor['id'] . '" value="">
                    <input type="hidden" name="time_'.$doctor['id'].'" id="time_' . $doctor['id'] . '" value="">
                    <input type="hidden" name="comment_'.$doctor['id'].'" id="comment_'.$doctor['id'].'" value="">
                    <button class="btn-small" type="button" onclick="submitAppointment('.$doctor['id'].')">Записаться</button>
                </form>
                    <button id="update_button_'.$doctor['id'].'" class="btn-small" type="button" onclick="getDoctorInfo('.$doctor['id'].')">Обновить</button>     
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
        <textarea id="commentInput" placeholder="Место для вашего комментария к заявке на прием." maxlength="200"></textarea>
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
                <?php foreach ($categories as $category) : ?>
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
function editRecord(id) {
    var doctor = <?php echo json_encode($doctors); ?>;
    var selectedDoctor = doctor.find(function(doc) {
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
                schedule[date].forEach(function(time) {
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