// Schedule Page JavaScript

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('scheduleModal');
    const addScheduleBtn = document.getElementById('addScheduleBtn');
    const addFirstSchedule = document.getElementById('addFirstSchedule');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelSchedule');
    const scheduleForm = document.getElementById('scheduleForm');
    const scheduleTypeSelect = document.getElementById('scheduleType');
    const vetSelectGroup = document.getElementById('vetSelectGroup');
    const vetSelect = document.getElementById('vetSelect');
    const filterBtns = document.querySelectorAll('.filterBtn');
    const scheduleGrid = document.getElementById('scheduleGrid');

    // Open modal for new schedule
    if (addScheduleBtn) {
        addScheduleBtn.addEventListener('click', function () {
            openModal();
        });
    }

    if (addFirstSchedule) {
        addFirstSchedule.addEventListener('click', function () {
            openModal();
        });
    }

    function openModal(scheduleData = null) {
        modal.style.display = 'block';

        if (scheduleData) {
            // Edit mode
            document.getElementById('modalTitle').textContent = 'Edit Schedule';
            document.getElementById('submitBtn').textContent = 'Update Schedule';
            document.getElementById('scheduleId').value = scheduleData.id;
            document.getElementById('petName').value = scheduleData.pet_name;
            document.getElementById('scheduleType').value = scheduleData.schedule_type;
            document.getElementById('title').value = scheduleData.title;
            document.getElementById('description').value = scheduleData.description || '';

            // Convert MySQL datetime to datetime-local format
            const date = new Date(scheduleData.schedule_date);
            const localDateTime = date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0') + 'T' +
                String(date.getHours()).padStart(2, '0') + ':' +
                String(date.getMinutes()).padStart(2, '0');
            document.getElementById('scheduleDate').value = localDateTime;

            if (scheduleData.schedule_type === 'clinic_visit' && scheduleData.vet_id) {
                vetSelectGroup.style.display = 'block';
                vetSelect.value = scheduleData.vet_id;
                vetSelect.required = true;
            }
        } else {
            // Add mode
            document.getElementById('modalTitle').textContent = 'Add New Schedule';
            document.getElementById('submitBtn').textContent = 'Save Schedule';
            scheduleForm.reset();
            document.getElementById('scheduleId').value = '';
            vetSelectGroup.style.display = 'none';
        }

        // Set minimum date to today
        const now = new Date();
        const minDateTime = now.toISOString().slice(0, 16);
        document.getElementById('scheduleDate').min = minDateTime;
    }

    // Close modal
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    function closeModal() {
        modal.style.display = 'none';
        scheduleForm.reset();
        vetSelectGroup.style.display = 'none';
        document.getElementById('scheduleId').value = '';
    }

    // Close modal when clicking outside
    window.addEventListener('click', function (event) {
        if (event.target == modal) {
            closeModal();
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

            const scheduleId = document.getElementById('scheduleId').value;
            const action = scheduleId ? 'update' : 'create';

            const formData = new FormData();
            formData.append('action', action);
            if (scheduleId) formData.append('scheduleId', scheduleId);
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
                        alert(action === 'create' ? 'Schedule created successfully!' : 'Schedule updated successfully!');
                        closeModal();
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving the schedule.');
                });
        });
    }

    // Filter functionality
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');
            const scheduleCards = document.querySelectorAll('.scheduleCard');

            scheduleCards.forEach(card => {
                if (filter === 'all') {
                    card.style.display = 'block';
                } else {
                    const status = card.getAttribute('data-status');
                    card.style.display = status === filter ? 'block' : 'none';
                }
            });
        });
    });
});

// Global functions for schedule actions
function editSchedule(scheduleId) {
    fetch(`schedule_handler.php?action=get&scheduleId=${scheduleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.schedule) {
                const modal = document.getElementById('scheduleModal');
                modal.style.display = 'block';

                document.getElementById('modalTitle').textContent = 'Edit Schedule';
                document.getElementById('submitBtn').textContent = 'Update Schedule';
                document.getElementById('scheduleId').value = data.schedule.id;
                document.getElementById('petName').value = data.schedule.pet_name;
                document.getElementById('scheduleType').value = data.schedule.schedule_type;
                document.getElementById('title').value = data.schedule.title;
                document.getElementById('description').value = data.schedule.description || '';

                // Convert MySQL datetime to datetime-local format
                const date = new Date(data.schedule.schedule_date);
                const localDateTime = date.getFullYear() + '-' +
                    String(date.getMonth() + 1).padStart(2, '0') + '-' +
                    String(date.getDate()).padStart(2, '0') + 'T' +
                    String(date.getHours()).padStart(2, '0') + ':' +
                    String(date.getMinutes()).padStart(2, '0');
                document.getElementById('scheduleDate').value = localDateTime;

                const vetSelectGroup = document.getElementById('vetSelectGroup');
                const vetSelect = document.getElementById('vetSelect');

                if (data.schedule.schedule_type === 'clinic_visit') {
                    vetSelectGroup.style.display = 'block';
                    vetSelect.required = true;
                    if (data.schedule.vet_id) {
                        vetSelect.value = data.schedule.vet_id;
                    }
                } else {
                    vetSelectGroup.style.display = 'none';
                    vetSelect.required = false;
                }
            } else {
                alert('Error loading schedule data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading the schedule.');
        });
}

function markComplete(scheduleId) {
    if (confirm('Mark this schedule as completed?')) {
        const formData = new FormData();
        formData.append('action', 'mark_completed');
        formData.append('scheduleId', scheduleId);

        fetch('../schedule_handler.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Schedule marked as completed!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
    }
}

function cancelSchedule(scheduleId) {
    if (confirm('Cancel this schedule? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('scheduleId', scheduleId);

        fetch('../schedule_handler.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Schedule cancelled!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
    }
}

function deleteSchedule(scheduleId) {
    if (confirm('Delete this schedule permanently? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('scheduleId', scheduleId);

        fetch('../schedule_handler.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Schedule deleted!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
    }
}
