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
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch statistics
$stats_sql = "SELECT 
    COUNT(*) as total_appointments,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
    COUNT(DISTINCT user_id) as total_clients
    FROM schedules 
    WHERE vet_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $_SESSION['user_id']);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Fetch upcoming schedules/appointments
$schedule_sql = "SELECT s.*, u.profile_name as client_name, u.email as client_email
                FROM schedules s 
                LEFT JOIN users u ON s.user_id = u.id 
                WHERE s.vet_id = ? AND s.status = 'pending' AND s.schedule_date >= NOW() 
                ORDER BY s.schedule_date ASC LIMIT 5";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $_SESSION['user_id']);
$schedule_stmt->execute();
$schedules_result = $schedule_stmt->get_result();

// Don't close connection yet - we need it for messages panel below
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetConnect - Veterinarian Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <script>
    // Toggle functions - defined early so onclick handlers work
    function toggleToMessages() {
        // Check if mobile/tablet view
        if (window.innerWidth <= 1024) {
            showMobilePanel('messages');
            return;
        }
        // Hide notifications, show messages
        document.querySelector('.notification-rightSection').style.display = 'none';
        document.querySelector('.message-rightSection').style.display = 'block';
        var messageToggle = document.getElementById('messageToggle');
        var notificationToggle = document.getElementById('notificationToggle');
        if (notificationToggle) notificationToggle.classList.remove('active');
        if (messageToggle) messageToggle.classList.add('active');
    }
    function toggleToNotifications() {
        // Check if mobile/tablet view
        if (window.innerWidth <= 1024) {
            showMobilePanel('notifications');
            return;
        }
        // Hide messages, show notifications
        document.querySelector('.notification-rightSection').style.display = 'block';
        document.querySelector('.message-rightSection').style.display = 'none';
        var messageToggle = document.getElementById('messageToggle');
        var notificationToggle = document.getElementById('notificationToggle');
        if (messageToggle) messageToggle.classList.remove('active');
        if (notificationToggle) notificationToggle.classList.add('active');
    }
    
    // Mobile panel pop-up functions
    function showMobilePanel(type) {
        var popup = document.getElementById('mobilePanelPopup');
        var header = document.getElementById('mobilePanelTitle');
        var body = document.getElementById('mobilePanelBody');
        
        if (type === 'messages') {
            header.textContent = '✉️ Messages';
            body.innerHTML = document.getElementById('messagesPanel').innerHTML;
        } else {
            header.textContent = '🔔 Notifications';
            body.innerHTML = document.getElementById('notificationsPanel').innerHTML;
        }
        
        popup.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobilePanel() {
        var popup = document.getElementById('mobilePanelPopup');
        popup.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Start conversation with client
    function startConversation(clientId, clientName) {
        // Switch to Messages panel
        toggleToMessages();
        
        // Scroll to top of message list
        var messageList = document.getElementById('messageList');
        if (messageList) {
            messageList.scrollTop = 0;
        }
        
        // Show notification to user
        alert('Starting conversation with ' + clientName + '.\n\nMessaging feature coming soon!');
    }
    </script>
</head>
<body>
    <div class="navbar">
        <div class="logo">VetConnect</div>
        <div class="searchBar"><input type="text" placeholder="Search..."></div>

        <ul class="navigation">
            <li><a href="veterinarian_dashboard.php">🏠 <span>Home</span></a></li>
            <li><a href="#" id="messageToggle" class="nav-icon-link" onclick="toggleToMessages(); return false;">&#9993; <span>Message</span></a></li>
            <li><a href="veterinarian_schedule.php">📅 <span>Schedule</span></a></li>
            <li><a href="#" id="notificationToggle" class="nav-icon-link active" onclick="toggleToNotifications(); return false;">&#128276; <span>Notifications</span></a></li>
        </ul>

        <ul id="togglePopup" class="profile-button">
            <li><img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="Profile Picture"></li>
            <li class="Profiletext"><?php echo htmlspecialchars($user['profile_name'] ?? 'Veterinarian'); ?></li>
        </ul>
    </div>

    <div class="dashboardContainer">
        <!-- Left Section - Statistics Dashboard -->
        <div class="leftSection">
            <div class="statsContainer">
                <h2 style="color: #FF6500; margin-bottom: 1.5rem;">📊 Dashboard Overview</h2>
                
                <div class="statCard">
                    <div class="statIcon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <span style="font-size: 2rem;">👥</span>
                    </div>
                    <div class="statInfo">
                        <h3><?php echo $stats['total_clients'] ?? 0; ?></h3>
                        <p>Total Clients</p>
                    </div>
                </div>

                <div class="statCard">
                    <div class="statIcon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <span style="font-size: 2rem;">📅</span>
                    </div>
                    <div class="statInfo">
                        <h3><?php echo $stats['total_appointments'] ?? 0; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>

                <div class="statCard">
                    <div class="statIcon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <span style="font-size: 2rem;">⏳</span>
                    </div>
                    <div class="statInfo">
                        <h3><?php echo $stats['pending_appointments'] ?? 0; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="statCard">
                    <div class="statIcon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <span style="font-size: 2rem;">✅</span>
                    </div>
                    <div class="statInfo">
                        <h3><?php echo $stats['completed_appointments'] ?? 0; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>

                <div class="quickActions">
                    <h3 style="margin: 2rem 0 1rem; color: #333;">Quick Actions</h3>
                    <a href="veterinarian_schedule.php" style="text-decoration: none;">
                        <div class="actionBtn" style="background: #FF6500; color: white; padding: 1rem; border-radius: 10px; margin-bottom: 0.8rem; cursor: pointer; transition: all 0.3s;">
                            📅 View All Appointments
                        </div>
                    </a>
                    <a href="uploads/profile_customization.php" style="text-decoration: none;">
                        <div class="actionBtn" style="background: #FFAD60; color: white; padding: 1rem; border-radius: 10px; cursor: pointer; transition: all 0.3s;">
                            ⚙️ Edit Profile
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Center Section - Main Content -->
        <div class="centerSection">
            <div class="welcomeSection">
                <h1 style="margin: 0;">Welcome, Dr. <?php echo htmlspecialchars($user['profile_name'] ?? 'Veterinarian'); ?>!</h1>
                <p style="margin: 0.5rem 0; color: #666;">Manage your appointments and communicate with pet owners</p>
                
                <?php if (!empty($user['clinic_name'])): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #FF6500;">
                        <p style="margin: 0; color: #555;">
                            🏥 <strong><?php echo htmlspecialchars($user['clinic_name']); ?></strong>
                        </p>
                        <?php if (!empty($user['city'])): ?>
                            <p style="margin: 0.5rem 0 0; color: #777; font-size: 0.9rem;">
                                📍 <?php echo htmlspecialchars($user['city']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Section - Notifications and Messages -->
        <div class="notification-rightSection" style="display: block;">
            <!-- Notifications Panel (only notifications, no messages) -->
            <div id="notificationsPanel" class="rightPanel active">
                <h2>Notifications</h2>
                <div class="notificationsList">
                <?php if ($schedules_result && $schedules_result->num_rows > 0): ?>
                    <?php while($schedule = $schedules_result->fetch_assoc()): 
                        $schedule_date = new DateTime($schedule['schedule_date']);
                    ?>
                        <div class="notificationCard">
                            <div class="notifIcon">📅</div>
                            <div class="notifContent">
                                <h4><?php echo htmlspecialchars($schedule['title'] ?? 'Appointment'); ?></h4>
                                <p><?php echo htmlspecialchars($schedule['description'] ?? 'No description'); ?></p>
                                <p><strong>Client: <?php echo htmlspecialchars($schedule['client_name'] ?? 'Unknown'); ?></strong></p>
                                <p><strong>Pet: <?php echo htmlspecialchars($schedule['pet_name'] ?? 'Pet'); ?></strong> - <?php echo $schedule_date->format('M d, Y h:i A'); ?></p>
                                <?php if (!empty($schedule['client_email'])): ?>
                                    <p style="font-size: 0.85rem; color: #666;">📧 <?php echo htmlspecialchars($schedule['client_email']); ?></p>
                                <?php endif; ?>
                                <span class="notifTime"><?php echo $schedule_date->format('g:i A'); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 2rem 0;">No upcoming appointments.</p>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="message-rightSection" style="display: none;">
            <!-- Messages Panel (only messages, no notifications) -->
            <div id="messagesPanel" class="rightPanel active">
                <h2>Messages</h2>
                <div class="searchMessages">
                    <input type="text" placeholder="Search messages..." id="messageSearch">
                </div>
                <div class="messageList" id="messageList">
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                        <p>Loading conversations...</p>
                    </div>
                </div>
                <div class="chatPlaceholder">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">💬</div>
                    <p>Select a conversation to start messaging</p>
                </div>
                <div class="chatArea">
                    <div class="chatHeader">
                        <!-- Will be populated by JS -->
                    </div>
                    <div class="chatMessages" id="chatMessages">
                        <!-- Messages will be loaded here -->
                    </div>
                    <div class="chatInput">
                        <input type="text" id="messageInput" placeholder="Type your message...">
                        <button onclick="sendMessage()">Send</button>
                    </div>
                </div>
                <?php
                // Close database connection after all queries are done
                $conn->close();
                ?>
            </div>
        </div>
    </div>

    <!-- Side pop-up -->
    <div id="sidePopup" class="popup">
        <div class="profilePictureOnProfile">
            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="Profile Picture">
        </div>
        <h2 class="userName">Dr. <?php echo htmlspecialchars($user['profile_name'] ?? 'Veterinarian'); ?></h2>
        <p style="text-align: center;">Veterinarian</p>
        <p style="text-align: center; padding: 0 1rem;">Manage your professional profile and view your schedule.</p>
        <a href="uploads/profile_customization.php" style="text-decoration: none;">
            <div class="paymentButton"><h3>Edit Profile</h3></div>
        </a>
        <a href="logout.php" style="text-decoration: none;">
            <div class="logoutButton"><h3>Logout</h3></div>
        </a>
    </div>
    
    <!-- Mobile Panel Pop-up -->
    <div id="mobilePanelPopup" class="mobile-panel-popup" onclick="if(event.target === this) closeMobilePanel()">
        <div class="mobile-panel-content">
            <div class="mobile-panel-header">
                <h2 id="mobilePanelTitle">Notifications</h2>
                <button class="mobile-panel-close" onclick="closeMobilePanel()">×</button>
            </div>
            <div class="mobile-panel-body" id="mobilePanelBody">
                <!-- Content will be inserted here by JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Bottom Navigation for Mobile/Tablet -->
    <nav class="bottom-nav">
        <ul class="bottom-nav-list">
            <li>
                <a href="veterinarian_dashboard.php" class="active">
                    <i class="fi fi-br-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="toggleToMessages(); return false;">
                    <i class="fi fi-br-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li>
                <a href="veterinarian_schedule.php">
                    <i class="fi fi-br-calendar"></i>
                    <span>Schedule</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="toggleToNotifications(); return false;">
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

    <div style="display: none;" data-user-id="<?php echo $_SESSION['user_id']; ?>"></div>
    <script src="register.js"></script>
    <script src="messaging.js"></script>
</body>
</html>
