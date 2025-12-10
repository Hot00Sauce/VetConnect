<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Adjust if login.php is in a different directory
    exit();
}

$servername = "localhost";
$username = "root"; // Adjust as needed
$password = ""; // Adjust as needed
$dbname = "vetconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch current user data
$user_sql = "SELECT profile_name, profile_picture, address, city, latitude, longitude, clinic_name, role FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

// Store role in session for easy access
if (!isset($_SESSION['role']) && isset($user_data['role'])) {
    $_SESSION['role'] = $user_data['role'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $profile_name = $_POST['profile_name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $clinic_name = isset($_POST['clinic_name']) ? $_POST['clinic_name'] : null;

    // Directory for uploaded files
    $target_dir = "../uploads/"; // Save relative to 'user.php', so it points to the right location when displayed
    
    // Check if the uploads directory exists, if not create it
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $uploadOk = 1;

    // Check if image file is a valid image type
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if ($check === false) {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size (limit to 2MB)
    if ($_FILES["profile_picture"]["size"] > 2000000) {
        echo "Sorry, your file is too large. Max size is 2MB.";
        $uploadOk = 0;
    }

    // Allow certain file formats (JPEG, PNG, JPG, GIF)
    $allowedFormats = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowedFormats)) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Geocode the address to get latitude and longitude
    $latitude = null;
    $longitude = null;
    if (!empty($address) && !empty($city)) {
        $full_address = urlencode($address . ', ' . $city);
        $geocode_url = "https://nominatim.openstreetmap.org/search?q={$full_address}&format=json&limit=1";
        
        $opts = [
            "http" => [
                "header" => "User-Agent: VetConnect/1.0\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $geocode_response = @file_get_contents($geocode_url, false, $context);
        
        if ($geocode_response) {
            $geocode_data = json_decode($geocode_response, true);
            if (!empty($geocode_data) && isset($geocode_data[0]['lat']) && isset($geocode_data[0]['lon'])) {
                $latitude = $geocode_data[0]['lat'];
                $longitude = $geocode_data[0]['lon'];
            }
        }
    }

    // Check if uploadOk is still 1 (i.e., passed all checks)
    if ($uploadOk == 1) {
        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Prepare and execute the update query with location data
            $target_file_db = "uploads/" . basename($_FILES["profile_picture"]["name"]); // Store relative path in DB
            $sql = "UPDATE users SET profile_name = ?, profile_picture = ?, address = ?, city = ?, latitude = ?, longitude = ?, clinic_name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssddsi", $profile_name, $target_file_db, $address, $city, $latitude, $longitude, $clinic_name, $_SESSION['user_id']);
            $stmt->execute();

            // Redirect to the user's profile page based on their role
            if ($user_data['role'] == 'Veterinarian') {
                header("Location: ../veterinarian_dashboard.php");
            } else {
                header("Location: ../Pet_OwnerDashboard.php");
            }
            exit();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetConnect - Edit Profile</title>
    <link rel="stylesheet" href="../style.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            padding: 2rem 1rem;
            padding-bottom: 6rem; /* Space for bottom nav on mobile */
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .profile-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: visible;
            animation: slideIn 0.5s ease-out;
            margin: 0 auto;
            margin-bottom: 2rem;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .profile-header {
            background: linear-gradient(135deg, #FF6500 0%, #FFAD60 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .profile-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .profile-header p {
            margin: 0.5rem 0 0;
            opacity: 0.95;
            font-size: 0.95rem;
        }
        
        .profile-avatar-preview {
            margin: 1.5rem auto 0;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .profile-avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-avatar-preview .placeholder {
            font-size: 3rem;
            color: #ddd;
        }
        
        .profile-form {
            padding: 2.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
        }
        
        .form-group input:focus {
            border-color: #FF6500;
            box-shadow: 0 0 0 3px rgba(255, 101, 0, 0.1);
        }
        
        .form-group input[type="file"] {
            padding: 0.7rem;
            cursor: pointer;
            background: #f8f9fa;
        }
        
        .form-group input[type="file"]::-webkit-file-upload-button {
            background: #FF6500;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 1rem;
            transition: background 0.3s;
        }
        
        .form-group input[type="file"]::-webkit-file-upload-button:hover {
            background: #FF8534;
        }
        
        .current-file {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.4rem 0.8rem;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #666;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #FF6500;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #555;
        }
        
        .info-box strong {
            color: #FF6500;
        }
        
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #FF6500 0%, #FFAD60 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 101, 0, 0.3);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 101, 0, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .back-link a:hover {
            color: #764ba2;
            gap: 0.7rem;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .profile-form {
                padding: 1.5rem;
            }
            
            body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="profile-header">
        <h2>Edit Your Profile</h2>
        <p>Update your information and location</p>
        <span class="role-badge"><?php echo htmlspecialchars($user_data['role'] ?? 'User'); ?></span>
        
        <div class="profile-avatar-preview" id="avatarPreview">
            <?php if (!empty($user_data['profile_picture'])): ?>
                <img src="../<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile">
            <?php else: ?>
                <div class="placeholder">👤</div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="profile-form">
        <div class="info-box">
            📍 <strong>Location helps us connect you!</strong> Your address will be used to find nearby veterinarians (within 50km radius).
        </div>
        
        <form action="profile_customization.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_name">Full Name</label>
                <input type="text" name="profile_name" id="profile_name" 
                       value="<?php echo htmlspecialchars($user_data['profile_name'] ?? ''); ?>" 
                       placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="profile_picture">Profile Picture</label>
                <input type="file" name="profile_picture" id="profile_picture" 
                       accept="image/*" required onchange="previewImage(event)">
                <?php if (!empty($user_data['profile_picture'])): ?>
                    <span class="current-file">📎 Current: <?php echo basename($user_data['profile_picture']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="address">Street Address</label>
                <input type="text" name="address" id="address" 
                       placeholder="123 Main Street" 
                       value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" name="city" id="city" 
                           placeholder="City name" 
                           value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" required>
                </div>

                <?php if ($user_data['role'] == 'Veterinarian'): ?>
                    <div class="form-group">
                        <label for="clinic_name">Clinic Name</label>
                        <input type="text" name="clinic_name" id="clinic_name" 
                               placeholder="Your clinic" 
                               value="<?php echo htmlspecialchars($user_data['clinic_name'] ?? ''); ?>">
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="submit-btn">💾 Save Profile</button>
        </form>
        
        <!-- Debug: Current role is <?php echo htmlspecialchars($user_data['role'] ?? 'NOT SET'); ?> -->
        
        <div class="back-link">
            <?php 
            // Determine the correct dashboard URL
            $dashboard_url = '../Pet_OwnerDashboard.php'; // Default
            if (isset($user_data['role']) && $user_data['role'] === 'Veterinarian') {
                $dashboard_url = '../veterinarian_dashboard.php';
            }
            ?>
            <a href="<?php echo $dashboard_url; ?>">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const preview = document.getElementById('avatarPreview');
    const file = event.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        }
        reader.readAsDataURL(file);
    }
}
</script>

<!-- Bottom Navigation for Mobile/Tablet -->
<nav class="bottom-nav">
    <ul class="bottom-nav-list">
        <li>
            <a href="<?php echo $user_data['role'] === 'veterinarian' ? '../veterinarian_dashboard.php' : '../Pet_OwnerDashboard.php'; ?>">
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
            <a href="<?php echo $user_data['role'] === 'veterinarian' ? '../veterinarian_schedule.php' : '../schedule.php'; ?>">
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
            <a href="profile_customization.php" class="active">
                <i class="fi fi-br-user"></i>
                <span>Profile</span>
            </a>
        </li>
    </ul>
</nav>

</body>
</html>
