<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
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

// Redirect pet owners to their own dashboard
if (isset($user['role']) && $user['role'] === 'Pet Owner') {
    header("Location: Pet_OwnerDashboard.php");
    exit();
}

// Re-fetch for the rest of the queries
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

// Fetch connected pet owners (those who have had conversations with this vet)
$petowners_sql = "SELECT DISTINCT u.id, u.profile_name, u.profile_picture, u.email, u.city, u.address,
                  (SELECT COUNT(*) FROM schedules s WHERE s.user_id = u.id AND s.vet_id = ? AND s.status = 'pending') as pending_appointments,
                  (SELECT MAX(created_at) FROM conversations WHERE (user1_id = u.id AND user2_id = ?) OR (user2_id = u.id AND user1_id = ?)) as last_contact
                  FROM users u
                  INNER JOIN conversations c ON (c.user1_id = u.id OR c.user2_id = u.id)
                  WHERE u.role = 'Pet Owner' 
                  AND (c.user1_id = ? OR c.user2_id = ?)
                  AND u.id != ?
                  ORDER BY last_contact DESC, u.profile_name ASC
                  LIMIT 20";
$petowners_stmt = $conn->prepare($petowners_sql);
$petowners_stmt->bind_param("iiiiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$petowners_stmt->execute();
$petowners_result = $petowners_stmt->get_result();

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
        <style>
        @media (max-width: 1024px) {
            .notification-rightSection {
                display: none !important;
            }
        }
        </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetConnect - Veterinarian Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <script>
    // Toggle functions - defined early so onclick handlers work
    function toggleToMessages() {
        if (window.innerWidth <= 1024) {
            showMobileMessagesPopup();
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

    // --- Mobile Second Message Panel Popup Logic ---
    // Show the message list popup (mobile)
    function showMobileMessagesPopup() {
        var popup = document.getElementById('mobilePanelPopup');
        var header = document.getElementById('mobilePanelTitle');
        var body = document.getElementById('mobilePanelBody');
        header.textContent = '✉️ Messages';
        body.innerHTML = document.getElementById('messagesPanel').innerHTML;
        // Wait for conversations to load, then attach click handler to .messageItem
        setTimeout(function() {
            var items = body.querySelectorAll('.messageItem');
            items.forEach(function(item) {
                item.addEventListener('click', function() {
                    // Optionally, you can extract conversation id/name here
                    closeMobilePanel();
                    showMobileSecondMessagePanel(item);
                });
            });
        }, 600);
        popup.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Show the second message panel popup (mobile)
    function showMobileSecondMessagePanel(messageItem) {
        var popup = document.getElementById('mobileSecondMessagePopup');
        if (popup) {
            // Optionally, populate chat header and messages from the selected conversation
            var chatHeader = popup.querySelector('.chatHeader');
            if (chatHeader && messageItem) {
                // Try to get the conversation name from the messageItem
                var name = messageItem.querySelector('.conversationName') ? messageItem.querySelector('.conversationName').textContent : 'Chat';
                chatHeader.textContent = name;
            }
            // Optionally clear chat messages area
            var chatMessages = popup.querySelector('#chatMessages');
            if (chatMessages) {
                chatMessages.innerHTML = '<div style="text-align:center;color:#888;padding:2rem;">No messages yet.</div>';
            }
            popup.classList.add('active');
            popup.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    // Close the second message panel popup (mobile)
    function closeMobileSecondMessagePanel() {
        var popup = document.getElementById('mobileSecondMessagePopup');
        if (popup) {
            popup.classList.remove('active');
            popup.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // Add event for back button in second message panel (mobile)
    document.addEventListener('DOMContentLoaded', function() {
        var backBtn = document.querySelector('#mobileSecondMessagePopup .backToMessagesBtn');
        if (backBtn) {
            backBtn.onclick = function() {
                closeMobileSecondMessagePanel();
                showMobileMessagesPopup();
            };
        }
    });
    
    // Ensure desktop Back to Messages button always works
    document.addEventListener('DOMContentLoaded', function() {
        function attachDesktopBackBtn() {
            var btn = document.querySelector('.desktopBackToMessagesBtn');
            if (btn) {
                btn.onclick = function(e) {
                    e.preventDefault();
                    if (typeof desktopBackToMessagesPanel === 'function') desktopBackToMessagesPanel();
                };
            }
        }
        attachDesktopBackBtn();
        // In case the button is re-rendered, observe DOM changes
        var observer = new MutationObserver(attachDesktopBackBtn);
        observer.observe(document.body, { childList: true, subtree: true });
    });

    // Ensure only one message panel is visible on desktop at page load
    document.addEventListener('DOMContentLoaded', function() {
        if (window.innerWidth > 1024) {
            var msgPanel = document.querySelector('.message-rightSection');
            var chatPanel = document.querySelector('.desktop-second-message-rightSection');
            if (msgPanel) {
                msgPanel.style.display = 'block';
                msgPanel.classList.add('active');
            }
            if (chatPanel) {
                chatPanel.style.display = 'none';
                chatPanel.classList.remove('active');
            }
        }
    });
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
            <li><img src="<?php echo !empty($user['profile_picture']) ? '../' . htmlspecialchars($user['profile_picture']) : '../assets/images/default-avatar.png'; ?>" alt="Profile Picture"></li>
            <li class="Profiletext"><?php echo htmlspecialchars($user['profile_name'] ?? 'Veterinarian'); ?></li>
        </ul>
    </div>

    <div class="dashboardContainer">
        <!-- Left Section - Connected Pet Owners -->
        <div class="leftSection">
            <h2>Connected Pet Owners</h2>
            <div class="vetList">
                <?php if ($petowners_result && $petowners_result->num_rows > 0): ?>
                    <?php while($owner = $petowners_result->fetch_assoc()): ?>
                        <div class="vetCard">
                            <img src="<?php echo !empty($owner['profile_picture']) ? '../' . htmlspecialchars($owner['profile_picture']) : '../assets/images/default-avatar.png'; ?>" alt="Owner Profile">
                            <div class="vetInfo">
                                <h3><?php echo htmlspecialchars($owner['profile_name'] ?? 'Pet Owner'); ?></h3>
                                <?php if (!empty($owner['city'])): ?>
                                    <p style="font-size: 0.75rem; color: #888; margin: 0.2rem 0;">📍 <?php echo htmlspecialchars($owner['city']); ?></p>
                                <?php endif; ?>
                                <?php if ($owner['pending_appointments'] > 0): ?>
                                    <p style="font-size: 0.75rem; color: #FF6500; margin: 0.2rem 0; font-weight: 600;">⏳ <?php echo $owner['pending_appointments']; ?> pending appointment<?php echo $owner['pending_appointments'] > 1 ? 's' : ''; ?></p>
                                <?php endif; ?>
                                <button class="connectBtn" onclick="startConversation(<?php echo $owner['id']; ?>, '<?php echo htmlspecialchars($owner['profile_name'] ?? 'Pet Owner', ENT_QUOTES); ?>')">Message</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 2rem 1rem;">
                        No connected pet owners yet. When pet owners contact you, they will appear here.
                    </p>
                <?php endif; ?>
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

            <!-- Dashboard Statistics -->
            <div class="statsContainer" style="margin-top: 2rem;">
                <h2 style="color: #FF6500; margin-bottom: 1.5rem;">📊 Dashboard Overview</h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
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
                </div>

                <div class="quickActions" style="margin-top: 2rem;">
                    <h3 style="margin: 0 0 1rem; color: #333;">Quick Actions</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="veterinarian_schedule.php" style="text-decoration: none; flex: 1; min-width: 200px;">
                            <div class="actionBtn" style="background: #FF6500; color: white; padding: 1rem; border-radius: 10px; cursor: pointer; transition: all 0.3s; text-align: center;">
                                📅 View All Appointments
                            </div>
                        </a>
                        <a href="../profiles/profile_customization.php" style="text-decoration: none; flex: 1; min-width: 200px;">
                            <div class="actionBtn" style="background: #FFAD60; color: white; padding: 1rem; border-radius: 10px; cursor: pointer; transition: all 0.3s; text-align: center;">
                                ⚙️ Edit Profile
                            </div>
                        </a>
                    </div>
                </div>
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
            </div>
        </div>
        <!-- Second message panel for desktop (hidden by default, shown when a conversation is open) -->
        <div class="desktop-second-message-rightSection" style="display: none;">
            <div id="desktopSecondMessagesPanel" class="rightPanel active">
                <button class="desktopBackToMessagesBtn" onclick="desktopBackToMessagesPanel();" style="
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    margin: 0.75rem 0 0 0.75rem;
                    padding: 0.45rem 1.2rem;
                    border: none;
                    background: linear-gradient(90deg, #4CAF50 0%, #2196F3 100%);
                    color: #fff;
                    border-radius: 24px;
                    box-shadow: 0 2px 8px rgba(33,150,243,0.08);
                    cursor: pointer;
                    font-size: 1.05rem;">
                    ← Back to Messages
                </button>
                <div class="desktopChatHeader"></div>
                <div class="desktopChatArea">
                    <div class="desktopChatMessages" id="desktopChatMessages"></div>
                    <div class="desktopChatInput">
                        <input type="text" id="desktopMessageInput" placeholder="Type a message...">
                        <button onclick="desktopSendMessage()">Send</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- The desktop-second-message-rightSection is now only for desktop above -->
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

    <!-- Mobile Second Message Panel Popup -->
    <div id="mobileSecondMessagePopup" class="mobile-panel-popup" onclick="if(event.target === this) closeMobileSecondMessagePanel()">
        <div class="mobile-panel-content">
            <div class="mobile-panel-header">
                <h2 id="mobileSecondPanelTitle">Chat</h2>
                <button class="mobile-panel-close" onclick="closeMobileSecondMessagePanel()">×</button>
            </div>
            <div class="mobile-panel-body" id="mobileSecondPanelBody">
                <div class="second-message-rightSection" style="display: block;">
                    <div id="secondMessagesPanel" class="rightPanel active">
                        <button class="backToMessagesBtn" onclick="closeMobileSecondMessagePanel(); showMobileMessagesPopup();" style="
                            display: flex;
                            align-items: center;
                            gap: 0.5rem;
                            margin: 0.75rem 0 0 0.75rem;
                            padding: 0.45rem 1.2rem;
                            border: none;
                            background: linear-gradient(90deg, #4CAF50 0%, #2196F3 100%);
                            color: #fff;
                            border-radius: 24px;
                            box-shadow: 0 2px 8px rgba(33,150,243,0.08);
                            cursor: pointer;
                            font-size: 1.05rem;
                            font-weight: 500;
                            transition: background 0.2s, box-shadow 0.2s;
                        "
                        onmouseover="this.style.background='linear-gradient(90deg, #2196F3 0%, #4CAF50 100%)'; this.style.boxShadow='0 4px 16px rgba(33,150,243,0.15)';"
                        onmouseout="this.style.background='linear-gradient(90deg, #4CAF50 0%, #2196F3 100%)'; this.style.boxShadow='0 2px 8px rgba(33,150,243,0.08)';"
                        >
                            <span style="font-size: 1.3rem; line-height: 1;">&#8592;</span>
                            <span>Back to Messages</span>
                        </button>
                        <div class="chatArea" style="display: flex; flex-direction: column; height: 500px; max-height: 60vh; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden;">
                            <div class="chatHeader">
                                <!-- Will be populated by JS -->
                            </div>
                            <div class="chatMessages" id="chatMessages" style="flex: 1 1 auto; overflow-y: auto; padding: 1rem; min-height: 0;">
                                <!-- Messages will be loaded here -->
                            </div>
                            <div class="chatInput">
                                <input type="text" id="messageInput" placeholder="Type your message...">
                                <button onclick="sendMessage()">Send</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* True overlay for mobile second message popup */
    #mobileSecondMessagePopup {
        display: none;
        position: fixed;
        z-index: 1200;
        top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.32);
        align-items: center;
        justify-content: center;
    }
    #mobileSecondMessagePopup.active {
        display: flex !important;
    }
    #mobileSecondMessagePopup .mobile-panel-content {
        background: #fff;
        border-radius: 12px;
        max-width: 98vw;
        width: 100vw;
        min-width: 0;
        max-height: 98vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        margin: auto;
    }
    @media (max-width: 1024px) {
        #mobileSecondMessagePopup .mobile-panel-content {
            width: 100vw;
            max-width: 100vw;
            min-width: 0;
        }
        #mobileSecondMessagePopup .chatArea {
            max-height: 60vh;
        }
    }
    </style>
    
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

    <div style="display: none;" data-user-id="<?php echo $_SESSION['user_id']; ?>"></div>
    <script src="../assets/js/register.js"></script>
    <script src="../assets/js/messaging.js"></script>
</body>
</html>
