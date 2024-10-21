function getDoctorInfo(doctor_id) {
    $.ajax({
        type: "POST",
        url: "get_doctor_info.php",
        data: { doctor_id: doctor_id },
        success: function(data) {
            var doctorData = data;

            var scheduleRow = $('#update_button_' + doctor_id).closest('tr');
            scheduleRow.find('.doctor-name').text(doctorData.Name + ", " + doctorData.Experience);
            
            сonsole.log(scheduleRow);
            var scheduleHtml = '';

            Object.keys(schedule).forEach(function(date) {
                        scheduleHtml += '<div>';
                        scheduleHtml += '<p id="selected_date_' + doctor.id + '">' + date + '</p>';
                        scheduleHtml += '<ul>';
                        schedule[date].forEach(function(time) {
                            scheduleHtml += '<li class="li-radio-btn"><input type="radio" name="selected_time_' + doctor.id + '" value="' + time + '"> ' + time + '</li>';
                        });
                        scheduleHtml += '</ul>';
                        scheduleHtml += '</div>';
                    });

            scheduleRow.find('.doctor-schedule').html(scheduleHtml);
        },
        error: function(xhr, status, error) {
            console.error("Ошибка при выполнении AJAX-запроса:", error);
        }
    });
}

function submitAppointment(doctorId) {
    var selectedTime = document.querySelector('input[name="selected_time_' + doctorId + '"]:checked');
    if (selectedTime) {
        var selectedDate = selectedTime.parentNode.parentNode.parentNode.querySelector('p').textContent;

        var modal = document.getElementById('commentModal');
        var commentInput = document.getElementById('commentInput');
        var submitCommentBtn = document.getElementById('submitCommentBtn');

        modal.style.display = 'block'; 

        submitCommentBtn.onclick = function() {
            var comment = commentInput.value;
            document.getElementById('time_' + doctorId).value = selectedTime.value;
                document.getElementById('date_' + doctorId).value = selectedDate;
                document.getElementById('comment_' + doctorId).value = comment;
                document.getElementById('appointment_form_' + doctorId).submit();
                modal.style.display = 'none'; 
        };
    } else {
        alert('Пожалуйста, выберите время для записи.');
    }
}

function setSelectedCategoryID(categoryID) {
    document.getElementById('category_id').value = categoryID;
}

function handleTimeChange(input) {
    var selectedTime = input.value; 
    console.log(selectedTime);

    var liElement = input.closest('li');

    var dateElement = liElement.closest('div').querySelector('p[data-date]');
    var date = dateElement.getAttribute('data-date');

    var scheduleJSON = document.getElementById('schedule').value;
    var schedule = JSON.parse(scheduleJSON);

    var scheduleArray = schedule[date];
    var index = Array.from(liElement.parentNode.children).indexOf(liElement);

    var startTimeInput = liElement.querySelector('.start-time-input');
    var endTimeInput = liElement.querySelector('.end-time-input');

    var timeToChange;
    if (input.classList.contains('start-time-input')) {
        timeToChange = 'start';
    } else if (input.classList.contains('end-time-input')) {
        timeToChange = 'end';
    }

    if (timeToChange === 'start') {
        scheduleArray[index] = selectedTime + '-' + scheduleArray[index].split('-')[1];
    } else if (timeToChange === 'end') {
        scheduleArray[index] = scheduleArray[index].split('-')[0] + '-' + selectedTime;
    }

    schedule[date] = scheduleArray;
    var updatedScheduleJSON = JSON.stringify(schedule);

    document.getElementById('schedule').value = updatedScheduleJSON;
}

function addScheduleDate() {
    var doctorId = document.getElementById('doctor_id').value;
    var scheduleDates = document.getElementById('scheduleDates');
    var newDateInput = document.createElement('div');

    var today = new Date();
    var day = String(today.getDate()).padStart(2, '0');
    var month = String(today.getMonth() + 1).padStart(2, '0');
    var year = today.getFullYear();
    var todayString = year + '-' + month + '-' + day;

    var numTimeIntervals = prompt("Укажите количество временных промежутков (от 1 до 5):");
    numTimeIntervals = parseInt(numTimeIntervals) || 1;
    numTimeIntervals = Math.min(Math.max(numTimeIntervals, 1), 5); 

    var scheduleArray = [];
    for (let i = 0; i < numTimeIntervals; i++) {
        var currentTime = new Date();
        var nearestHour = currentTime.getHours() + i; 
        var nearestTime = nearestHour.toString().padStart(2, '0') + ':00'; 
        scheduleArray.push(nearestTime + '-' + (nearestHour + 1).toString().padStart(2, '0') + ':00'); 
    }

    newDateInput.innerHTML = `
        <div>
            <p data-date="${todayString}">${todayString}</p>
            <ul>
                ${scheduleArray.map(interval => `<li class="li-radio-btn">от <input type="time" name="new_times[]" value="${interval.split('-')[0]}"> до <input type="time" name="new_times[]" value="${interval.split('-')[1]}"></li>`).join('')}
            </ul>
            <button type="button" onclick="removeScheduleDate('${doctorId}', '${todayString}')">Удалить дату</button>
        </div>
    `;

    scheduleDates.appendChild(newDateInput);

    let scheduleJSON = document.getElementById('schedule').value;
    let schedule = JSON.parse(scheduleJSON);

    if (!schedule[todayString]) {
        schedule[todayString] = [];
    }

    schedule[todayString] = scheduleArray; 

    let updatedScheduleJSON = JSON.stringify(schedule);
    document.getElementById('schedule').value = updatedScheduleJSON;
}

function removeScheduleDate(id, date) {
    console.log(id); 
    console.log(date);

    const dateElement = document.querySelector(`p[data-date="${date}"]`);
    if (dateElement) {
        dateElement.parentNode.remove();
    }

    let scheduleJSON = document.getElementById('schedule').value;
    let schedule = JSON.parse(scheduleJSON);
    if (schedule.hasOwnProperty(date)) {
        delete schedule[date];
    }
    let updatedScheduleJSON = JSON.stringify(schedule);
    document.getElementById('schedule').value = updatedScheduleJSON;
}

function closeEditModal() {
    var modal = document.getElementById('editDoctorModal');
    if (modal) {
        modal.style.display = "none";
    } else {
        console.error("Modal element not found.");
    }
}

function openModal(modalId) {
    var modal = document.getElementById(modalId);
    modal.style.display = 'block';

    var closeBtn = modal.querySelector('.close');
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    modal.style.display = 'none';
}

function submitComment(doctorId) {
    var comment = document.getElementById('commentInput').value;
    document.getElementById('comment_' + doctorId).value = comment;
    closeModal('commentModal'); 
}

function confirmDelete(id) {
    if (confirm("Вы уверены, что хотите удалить этого доктора?")) {
        deleteRecord(id);
        return true;
    }
    return false;
}

function deleteRecord(id) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'delete_doctor.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            window.location.reload();
        }
    };
    xhr.send('doctor_id=' + id);
}

