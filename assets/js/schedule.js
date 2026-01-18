// Schedule Management JavaScript

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('scheduleModal');
    const addScheduleBtn = document.getElementById('addScheduleBtn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelSchedule');
    const scheduleForm = document.getElementById('scheduleForm');
    const scheduleTypeSelect = document.getElementById('scheduleType');
    const vetSelectGroup = document.getElementById('vetSelectGroup');
    const vetSelect = document.getElementById('vetSelect');

    // Open modal when clicking Add Schedule button
    if (addScheduleBtn) {
        addScheduleBtn.addEventListener('click', function () {
            modal.style.display = 'block';
            // Set minimum date to today
            const now = new Date();
            const minDateTime = now.toISOString().slice(0, 16);
            document.getElementById('scheduleDate').min = minDateTime;
        });
    }

    // Close modal when clicking X
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
            scheduleForm.reset();
            vetSelectGroup.style.display = 'none';
        });
    }

    // Close modal when clicking Cancel button
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            modal.style.display = 'none';
            scheduleForm.reset();
            vetSelectGroup.style.display = 'none';
        });
    }

    // Close modal when clicking outside of it
    window.addEventListener('click', function (event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            scheduleForm.reset();
            vetSelectGroup.style.display = 'none';
        }
    });

    // Show/hide veterinarian select based on schedule type
    if (scheduleTypeSelect) {
        scheduleTypeSelect.addEventListener('change', function () {
            if (this.value === 'clinic_visit') {
                vetSelectGroup.style.display = 'block';
                vetSelect.required = true;
            } else {
                vetSelectGroup.style.display = 'none';
                vetSelect.required = false;
                vetSelect.value = '';
            }
        });
    }

    // Handle form submission
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('petName', document.getElementById('petName').value);
            formData.append('scheduleType', document.getElementById('scheduleType').value);
            formData.append('vetId', document.getElementById('vetSelect').value);
            formData.append('scheduleDate', document.getElementById('scheduleDate').value);
            formData.append('title', document.getElementById('title').value);
            formData.append('description', document.getElementById('description').value);

            fetch('../schedule_handler.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Schedule created successfully!');
                        modal.style.display = 'none';
                        scheduleForm.reset();
                        vetSelectGroup.style.display = 'none';
                        // Reload page to show updated notifications
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while creating the schedule.');
                });
        });
    }
});

// Function to load schedules (can be called from other parts of the app)
function loadSchedules() {
    fetch('schedule_handler.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationList(data.schedules);
            }
        })
        .catch(error => console.error('Error loading schedules:', error));
}

// Function to update notification list
function updateNotificationList(schedules) {
    const notificationList = document.getElementById('notificationList');

    if (!schedules || schedules.length === 0) {
        notificationList.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem 0;">No upcoming schedules. Click "Add Schedule" to create one.</p>';
        return;
    }

    let html = '';
    schedules.forEach(schedule => {
        const scheduleDate = new Date(schedule.schedule_date);
        const now = new Date();
        const diffTime = scheduleDate - now;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        let icon = '🔔';
        if (schedule.schedule_type === 'vaccination') icon = '💉';
        else if (schedule.schedule_type === 'medication') icon = '💊';
        else if (schedule.schedule_type === 'clinic_visit') icon = '🏥';

        let timeText = '';
        if (diffDays === 0) timeText = 'Today';
        else if (diffDays === 1) timeText = 'Tomorrow';
        else timeText = 'In ' + diffDays + ' days';

        const urgentClass = diffDays <= 2 ? 'urgent' : '';

        html += `
            <div class="notification ${urgentClass}">
                <div class="notifIcon">${icon}</div>
                <div class="notifContent">
                    <h4>${escapeHtml(schedule.title)}</h4>
                    <p>${escapeHtml(schedule.description)}</p>
                    <p><strong>${escapeHtml(schedule.pet_name)}</strong> - ${scheduleDate.toLocaleString()}</p>
                    <span class="notifTime">${timeText}</span>
                </div>
            </div>
        `;
    });

    notificationList.innerHTML = html;
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
