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

// Fetch the user's profile information including location
$sql = "SELECT profile_name, profile_picture, latitude, longitude FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch veterinarians within 50km if user has location set
$vet_result = null;
if (!empty($user['latitude']) && !empty($user['longitude'])) {
    // Using Haversine formula to calculate distance
    // 50km = 0.45 degrees approximately (rough estimate for filtering)
    $lat = $user['latitude'];
    $lon = $user['longitude'];
    
    $vet_sql = "SELECT id, profile_name, profile_picture, email, clinic_name, address, city, latitude, longitude,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM users 
                WHERE role = 'Veterinarian' 
                AND latitude IS NOT NULL 
                AND longitude IS NOT NULL
                HAVING distance <= 50
                ORDER BY distance ASC
                LIMIT 10";
    $vet_stmt = $conn->prepare($vet_sql);
    $vet_stmt->bind_param("ddd", $lat, $lon, $lat);
    $vet_stmt->execute();
    $vet_result = $vet_stmt->get_result();
} else {
    // If no location set, show all veterinarians (or show message)
    $vet_sql = "SELECT id, profile_name, profile_picture, email, clinic_name, address, city FROM users WHERE role = 'Veterinarian' LIMIT 10";
    $vet_result = $conn->query($vet_sql);
}

// Fetch upcoming schedules for notifications
$schedule_sql = "SELECT s.*, u.profile_name as vet_name 
                FROM schedules s 
                LEFT JOIN users u ON s.vet_id = u.id 
                WHERE s.user_id = ? AND s.status = 'pending' AND s.schedule_date >= NOW() 
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
    <title>VetConnect - Pet Owner Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <script>
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
    
    </script>
</head>
<body>
    <div class="navbar">
        <div class="logo">VetConnect</div>
        <div class="searchBar"><input type="text" placeholder="Search..."></div>

        <ul class="navigation">
            <li><a href="Pet_OwnerDashboard.php">🏠 <span>Home</span></a></li>
            <li><a href="#" id="messageToggle" class="nav-icon-link" onclick="toggleToMessages(); return false;">&#9993; <span>Message</span></a></li>
            <li><a href="schedule.php">📅 <span>Schedule</span></a></li>
            <li><a href="#" id="notificationToggle" class="nav-icon-link active" onclick="toggleToNotifications(); return false;">&#128276; <span>Notifications</span></a></li>
        </ul>

        <ul id="togglePopup" class="profile-button">
            <li><img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="Profile Picture"></li>
            <li class="Profiletext"><?php echo htmlspecialchars($user['profile_name'] ?? 'User'); ?></li>
        </ul>
    </div>

    <div class="dashboardContainer">
        <!-- Left Section: Veterinarians -->
        <div class="leftSection">
            <h2>Nearby Veterinarians</h2>
            <?php if (empty($user['latitude']) || empty($user['longitude'])): ?>
                <div style="background-color: #FFF4E0; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <p style="color: #FF6500; font-size: 0.9rem; margin: 0;">📍 Set your location in your profile to see nearby veterinarians!</p>
                </div>
            <?php endif; ?>
            <div class="vetList">
                <?php if ($vet_result && $vet_result->num_rows > 0): ?>
                    <?php while($vet = $vet_result->fetch_assoc()): ?>
                        <div class="vetCard">
                            <img src="<?php echo htmlspecialchars($vet['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="Vet Profile">
                            <div class="vetInfo">
                                <h3><?php echo htmlspecialchars($vet['profile_name'] ?? 'Veterinarian'); ?></h3>
                                <?php if (!empty($vet['clinic_name'])): ?>
                                    <p style="font-size: 0.8rem; color: #666; margin: 0.2rem 0;"><?php echo htmlspecialchars($vet['clinic_name'] ?? ''); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($vet['city'])): ?>
                                    <p style="font-size: 0.75rem; color: #888; margin: 0.2rem 0;">📍 <?php echo htmlspecialchars($vet['city'] ?? ''); ?></p>
                                <?php endif; ?>
                                <?php if (isset($vet['distance'])): ?>
                                    <p style="font-size: 0.75rem; color: #4CAF50; margin: 0.2rem 0; font-weight: 600;">~<?php echo number_format($vet['distance'], 1); ?> km away</p>
                                <?php endif; ?>
                                <button class="connectBtn" onclick="startConversation(<?php echo $vet['id']; ?>, '<?php echo htmlspecialchars($vet['profile_name'] ?? 'Veterinarian', ENT_QUOTES); ?>')">Contact</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 2rem 1rem;">
                        <?php if (!empty($user['latitude']) && !empty($user['longitude'])): ?>
                            No veterinarians found within 50km of your location.
                        <?php else: ?>
                            Please set your location in your profile to see nearby veterinarians.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content Section -->
        <div class="mainContent">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h1 style="margin: 0;">Welcome, <?php echo htmlspecialchars($user['profile_name'] ?? 'User'); ?>!</h1>
                <button id="addScheduleBtn" class="scheduleBtn">+ Add Schedule</button>
            </div>
            <p>Find the best veterinarians for your pets. Browse through our suggested veterinarians and connect with them to schedule appointments.</p>
            
            <div class="dashboardCards">
                <div class="card">
                    <h3>My Pets</h3>
                    <p>Manage your pet profiles</p>
                </div>
                <div class="card">
                    <h3>Appointments</h3>
                    <p>View upcoming appointments</p>
                </div>
                <div class="card">
                    <h3>Messages</h3>
                    <p>Chat with veterinarians</p>
                </div>
            </div>
        </div>

        <!-- Right Section: Notifications & Messages -->
        <div class="notification-rightSection" style="display: block;">
            <!-- Notifications Panel -->
            <div id="notificationsPanel" class="rightPanel active">
                <h2>Notifications</h2>
                <div class="notificationList" id="notificationList">
                <?php if ($schedules_result && $schedules_result->num_rows > 0): ?>
                    <?php while($schedule = $schedules_result->fetch_assoc()): 
                        $schedule_date = new DateTime($schedule['schedule_date']);
                        $now = new DateTime();
                        $diff = $now->diff($schedule_date);
                        $days_until = $diff->days;
                        $icon = '🔔';
                        if ($schedule['schedule_type'] == 'vaccination') $icon = '💉';
                        elseif ($schedule['schedule_type'] == 'medication') $icon = '💊';
                        elseif ($schedule['schedule_type'] == 'clinic_visit') $icon = '🏥';
                        $time_text = '';
                        if ($days_until == 0) $time_text = 'Today';
                        elseif ($days_until == 1) $time_text = 'Tomorrow';
                        else $time_text = 'In ' . $days_until . ' days';
                    ?>
                        <div class="notification <?php echo $days_until <= 2 ? 'urgent' : ''; ?>">
                            <div class="notifIcon"><?php echo $icon; ?></div>
                            <div class="notifContent">
                                <h4><?php echo htmlspecialchars($schedule['title'] ?? 'Schedule'); ?></h4>
                                <p><?php echo htmlspecialchars($schedule['description'] ?? 'No description'); ?></p>
                                <p><strong><?php echo htmlspecialchars($schedule['pet_name'] ?? 'Pet'); ?></strong> - <?php echo $schedule_date->format('M d, Y h:i A'); ?></p>
                                <span class="notifTime"><?php echo $time_text; ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 2rem 0;">No upcoming schedules. Click "Add Schedule" to create one.</p>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="message-rightSection" style="display: none;">
            <!-- Messages Panel -->
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

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Schedule</h2>
            <form id="scheduleForm">
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
                    <select id="vetSelect" name="vetId">
                        <option value="">Select veterinarian...</option>
                        <?php
                        // Fetch vets for modal dropdown
                        $vet_modal_sql = "SELECT id, profile_name FROM users WHERE role = 'Veterinarian'";
                        $vet_modal_result = $conn->query($vet_modal_sql);
                        if ($vet_modal_result && $vet_modal_result->num_rows > 0):
                            while($vet = $vet_modal_result->fetch_assoc()): ?>
                                <option value="<?php echo $vet['id']; ?>"><?php echo htmlspecialchars($vet['profile_name'] ?? 'Veterinarian'); ?></option>
                            <?php endwhile;
                        endif;
                        // Close database connection after all queries are done
                        $conn->close();
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
                    <button type="submit" class="btn-submit">Save Schedule</button>
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
                <a href="Pet_OwnerDashboard.php" class="active">
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
                <a href="schedule.php">
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
    <script src="schedule.js"></script>
    <script src="messaging.js"></script>
    <script>
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
                    closeMobilePanel();
                    showMobileSecondMessagePanel();
                });
            });
        }, 600);
        popup.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Show the second message panel popup (mobile)
    function showMobileSecondMessagePanel() {
        var popup = document.getElementById('mobileSecondMessagePopup');
        if (popup) {
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

    // Patch toggleToMessages for mobile to use new popup
    (function() {
        var origToggleToMessages = window.toggleToMessages;
        window.toggleToMessages = function() {
            if (window.innerWidth <= 1024) {
                showMobileMessagesPopup();
                return;
            }
            if (origToggleToMessages) origToggleToMessages();
        };
    })();

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
    </script>

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
</body>
</html>
