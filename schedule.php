<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vetconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

// Fetch the user's profile information
$sql = "SELECT profile_name, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch veterinarians for the schedule form
$vet_sql = "SELECT id, profile_name FROM users WHERE role = 'Veterinarian'";
$vet_result = $conn->query($vet_sql);

// Fetch all schedules for the user (pending, completed, and cancelled)
$schedule_sql = "SELECT s.*, u.profile_name as vet_name 
                FROM schedules s 
                LEFT JOIN users u ON s.vet_id = u.id 
                WHERE s.user_id = ? 
                ORDER BY s.schedule_date DESC";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $_SESSION['user_id']);
$schedule_stmt->execute();
$all_schedules = $schedule_stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetConnect - My Schedules</title>
    <link rel="stylesheet" href="style.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
</head>
<body>
    <div class="navbar">
        <div class="logo">VetConnect</div>
        <div class="searchBar"><input type="text" placeholder="Search..."></div>

        <ul class="navigation">
            <li><a href="Pet_OwnerDashboard.php">Home</a></li>
            <li><a href="javascript:void(0);" id="messageToggle" class="nav-icon-link">&#9993; Message</a></li>
            <li><a href="schedule.php" class="active">Schedule</a></li>
            <li><a href="javascript:void(0);" id="notificationToggle" class="nav-icon-link">&#128276; Notifications</a></li>
        </ul>

        <ul id="togglePopup" class="profile-button">
            <li><img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="Profile Picture"></li>
            <li class="Profiletext"><?php echo htmlspecialchars($user['profile_name'] ?? 'User'); ?></li>
        </ul>
    </div>

    <div class="schedulePageContainer">
        <div class="scheduleHeader">
            <h1>My Pet Schedules</h1>
            <button id="addScheduleBtn" class="scheduleBtn">+ Add New Schedule</button>
        </div>

        <div class="scheduleFilters">
            <button class="filterBtn active" data-filter="all">All Schedules</button>
            <button class="filterBtn" data-filter="pending">Upcoming</button>
            <button class="filterBtn" data-filter="completed">Completed</button>
            <button class="filterBtn" data-filter="cancelled">Cancelled</button>
        </div>

        <div class="scheduleGrid" id="scheduleGrid">
            <?php if ($all_schedules && $all_schedules->num_rows > 0): ?>
                <?php while($schedule = $all_schedules->fetch_assoc()): 
                    $schedule_date = new DateTime($schedule['schedule_date']);
                    $now = new DateTime();
                    $is_past = $schedule_date < $now;
                    
                    $icon = '🔔';
                    $type_label = '';
                    if ($schedule['schedule_type'] == 'vaccination') {
                        $icon = '💉';
                        $type_label = 'Vaccination';
                    } elseif ($schedule['schedule_type'] == 'medication') {
                        $icon = '💊';
                        $type_label = 'Medication';
                    } elseif ($schedule['schedule_type'] == 'clinic_visit') {
                        $icon = '🏥';
                        $type_label = 'Clinic Visit';
                    }
                    
                    $status_class = $schedule['status'];
                    if ($schedule['status'] == 'pending' && $is_past) {
                        $status_class = 'overdue';
                    }
                ?>
                    <div class="scheduleCard" data-status="<?php echo $schedule['status']; ?>" data-id="<?php echo $schedule['id']; ?>">
                        <div class="scheduleCardHeader">
                            <div class="scheduleIcon"><?php echo $icon; ?></div>
                            <span class="scheduleType"><?php echo $type_label; ?></span>
                            <span class="scheduleStatus status-<?php echo $status_class; ?>">
                                <?php echo ucfirst($schedule['status']); ?>
                            </span>
                        </div>
                        
                        <div class="scheduleCardBody">
                            <h3><?php echo htmlspecialchars($schedule['title']); ?></h3>
                            <p class="petName">🐾 <?php echo htmlspecialchars($schedule['pet_name']); ?></p>
                            
                            <?php if (!empty($schedule['description'])): ?>
                                <p class="scheduleDesc"><?php echo htmlspecialchars($schedule['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="scheduleDetails">
                                <p class="scheduleDateTime">
                                    📅 <?php echo $schedule_date->format('F d, Y'); ?><br>
                                    🕐 <?php echo $schedule_date->format('h:i A'); ?>
                                </p>
                                
                                <?php if (!empty($schedule['vet_name'])): ?>
                                    <p class="scheduleVet">👨‍⚕️ <?php echo htmlspecialchars($schedule['vet_name']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="scheduleCardFooter">
                            <?php if ($schedule['status'] == 'pending'): ?>
                                <button class="btnComplete" onclick="markComplete(<?php echo $schedule['id']; ?>)">✓ Complete</button>
                                <button class="btnEdit" onclick="editSchedule(<?php echo $schedule['id']; ?>)">✎ Edit</button>
                                <button class="btnCancel" onclick="cancelSchedule(<?php echo $schedule['id']; ?>)">✗ Cancel</button>
                            <?php else: ?>
                                <button class="btnDelete" onclick="deleteSchedule(<?php echo $schedule['id']; ?>)">🗑 Delete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="emptyState">
                    <div class="emptyIcon">📅</div>
                    <h2>No Schedules Yet</h2>
                    <p>Create your first schedule to keep track of your pet's appointments, vaccinations, and medications.</p>
                    <button id="addFirstSchedule" class="scheduleBtn">+ Create Schedule</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Add New Schedule</h2>
            <form id="scheduleForm">
                <input type="hidden" id="scheduleId" name="scheduleId" value="">
                
                <div class="form-group">
                    <label for="petName">Pet Name *</label>
                    <input type="text" id="petName" name="petName" required>
                </div>

                <div class="form-group">
                    <label for="scheduleType">Schedule Type *</label>
                    <select id="scheduleType" name="scheduleType" required>
                        <option value="">Select type...</option>
                        <option value="clinic_visit">Clinic Visit</option>
                        <option value="vaccination">Vaccination</option>
                        <option value="medication">Medication</option>
                    </select>
                </div>

                <div class="form-group" id="vetSelectGroup" style="display: none;">
                    <label for="vetSelect">Select Veterinarian</label>
                    <select id="vetSelect" name="vetSelect">
                        <option value="">Select veterinarian...</option>
                        <?php 
                        if ($vet_result && $vet_result->num_rows > 0):
                            $vet_result->data_seek(0); // Reset pointer
                            while($vet = $vet_result->fetch_assoc()): ?>
                                <option value="<?php echo $vet['id']; ?>"><?php echo htmlspecialchars($vet['profile_name']); ?></option>
                            <?php endwhile;
                        endif;
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="scheduleDate">Date & Time *</label>
                    <input type="datetime-local" id="scheduleDate" name="scheduleDate" required>
                </div>

                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" placeholder="e.g., Annual Checkup" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Add any notes or details..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" id="cancelSchedule">Cancel</button>
                    <button type="submit" class="btn-submit" id="submitBtn">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Side pop-up -->
    <div id="sidePopup" class="popup">
        <div class="profilePictureOnProfile">
            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="Profile Picture">
        </div>
        <h2 class="userName"><?php echo htmlspecialchars($user['profile_name'] ?? 'User'); ?></h2>
        <p style="text-align: center;">Pet Owner</p>
        <p style="text-align: center; padding: 0 1rem;">Manage your profile settings and view your appointment history.</p>
        <a href="uploads/update_profile.php" style="text-decoration: none;">
            <div class="paymentButton"><h3>Edit Profile</h3></div>
        </a>
        <a href="logout.php" style="text-decoration: none;">
            <div class="logoutButton"><h3>Logout</h3></div>
        </a>
    </div>
    
    <!-- Bottom Navigation for Mobile/Tablet -->
    <nav class="bottom-nav">
        <ul class="bottom-nav-list">
            <li>
                <a href="Pet_OwnerDashboard.php">
                    <i class="fi fi-br-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="alert('Messages feature coming soon!'); return false;">
                    <i class="fi fi-br-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li>
                <a href="schedule.php" class="active">
                    <i class="fi fi-br-calendar"></i>
                    <span>Schedule</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="alert('Notifications feature coming soon!'); return false;">
                    <i class="fi fi-br-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            <li>
                <a href="uploads/profile_customization.php">
                    <i class="fi fi-br-user"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="footer">
        © 2024 VetConnect. All rights reserved.
    </div>

    <script src="register.js"></script>
    <script src="schedule_page.js"></script>
</body>
</html>
