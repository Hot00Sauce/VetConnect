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
$sql = "SELECT profile_name, profile_picture, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Verify user is a veterinarian
if ($user['role'] !== 'Veterinarian') {
    header("Location: Pet_OwnerDashboard.php");
    exit();
}

// Fetch all appointments/schedules for this veterinarian
$schedule_sql = "SELECT s.*, u.profile_name as client_name, u.email as client_email
                FROM schedules s 
                LEFT JOIN users u ON s.user_id = u.id 
                WHERE s.vet_id = ? 
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
    <title>VetConnect - My Appointments</title>
    <link rel="stylesheet" href="style.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
</head>
<body>
    <div class="navbar">
        <div class="logo">VetConnect</div>
        <div class="searchBar"><input type="text" placeholder="Search..."></div>

        <ul class="navigation">
            <li><a href="veterinarian_dashboard.php">Home</a></li>
            <li><a href="#" id="messageToggle" class="nav-icon-link" onclick="toggleToMessages(); return false;">&#9993; Message</a></li>
            <li><a href="veterinarian_schedule.php" class="active">Schedule</a></li>
            <li><a href="#" id="notificationToggle" class="nav-icon-link" onclick="toggleToNotifications(); return false;">&#128276; Notifications</a></li>
        </ul>

        <ul id="togglePopup" class="profile-button">
            <li><img src="<?php echo !empty($user['profile_picture']) ? '../' . htmlspecialchars($user['profile_picture']) : '../assets/images/default-avatar.png'; ?>" alt="Profile Picture"></li>
            <li class="Profiletext">Dr. <?php echo htmlspecialchars($user['profile_name'] ?? 'Veterinarian'); ?></li>
        </ul>
    </div>

    <div class="schedulePageContainer">
        <div class="scheduleHeader">
            <h1>My Appointments</h1>
            <p style="color: #666; margin: 0.5rem 0;">View and manage your scheduled appointments with pet owners</p>
        </div>

        <div class="scheduleFilters">
            <button class="filterBtn active" data-filter="all">All Appointments</button>
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
                            <h3><?php echo htmlspecialchars($schedule['title'] ?? 'Appointment'); ?></h3>
                            
                            <p class="clientName">👤 <strong>Client:</strong> <?php echo htmlspecialchars($schedule['client_name'] ?? 'Unknown'); ?></p>
                            <p class="petName">🐾 <strong>Pet:</strong> <?php echo htmlspecialchars($schedule['pet_name'] ?? 'Pet'); ?></p>
                            
                            <?php if (!empty($schedule['description'])): ?>
                                <p class="scheduleDesc"><?php echo htmlspecialchars($schedule['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="scheduleDetails">
                                <p class="scheduleDateTime">
                                    📅 <?php echo $schedule_date->format('F d, Y'); ?><br>
                                    🕐 <?php echo $schedule_date->format('h:i A'); ?>
                                </p>
                                
                                <?php if (!empty($schedule['client_email'])): ?>
                                    <p class="clientEmail">📧 <?php echo htmlspecialchars($schedule['client_email']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="scheduleCardFooter">
                            <?php if ($schedule['status'] == 'pending'): ?>
                                <button class="btnComplete" onclick="markComplete(<?php echo $schedule['id']; ?>)">✓ Complete</button>
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
                    <h2>No Appointments Yet</h2>
                    <p>You don't have any scheduled appointments. Pet owners will book appointments with you from their dashboard.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Side pop-up -->
    <div id="sidePopup" class="popup">
        <div class="profilePictureOnProfile">
            <img src="<?php echo !empty($user['profile_picture']) ? '../' . htmlspecialchars($user['profile_picture']) : '../assets/images/default-avatar.png'; ?>" alt="Profile Picture">
        </div>
        <h2 class="userName">Dr. <?php echo htmlspecialchars($user['profile_name'] ?? 'Veterinarian'); ?></h2>
        <p style="text-align: center;">Veterinarian</p>
        <p style="text-align: center; padding: 0 1rem;">Manage your professional profile and view your schedule.</p>
        <a href="../profiles/profile_customization.php" style="text-decoration: none;">
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
                <a href="veterinarian_dashboard.php">
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
                <a href="veterinarian_schedule.php" class="active">
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
                <a href="../profiles/profile_customization.php">
                    <i class="fi fi-br-user"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="footer">
        © 2024 VetConnect. All rights reserved.
    </div>

    <script src="../assets/js/register.js"></script>
    <script src="../assets/js/schedule_page.js"></script>
</body>
</html>
