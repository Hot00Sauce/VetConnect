<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vetconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $profile_name = $_POST['profile_name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $clinic_name = isset($_POST['clinic_name']) ? $_POST['clinic_name'] : null;

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

    // Handle profile picture upload if provided
    $profile_picture = $user_data['profile_picture']; // Keep existing if not uploaded
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $uploadOk = 1;

        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check !== false && $_FILES["profile_picture"]["size"] <= 2000000) {
            $allowedFormats = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageFileType, $allowedFormats)) {
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    $profile_picture = "uploads/" . basename($_FILES["profile_picture"]["name"]);
                }
            }
        }
    }

    // Update user profile
    $sql = "UPDATE users SET profile_name = ?, profile_picture = ?, address = ?, city = ?, latitude = ?, longitude = ?, clinic_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssddsi", $profile_name, $profile_picture, $address, $city, $latitude, $longitude, $clinic_name, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating profile.";
    }
    
    $stmt->close();
    
    // Redirect back
    if ($user_data['role'] == 'Veterinarian') {
        header("Location: ../veterinarian_dashboard.php");
    } else {
        header("Location: ../Pet_OwnerDashboard.php");
    }
    exit();
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
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        
        .profile-form-container {
            max-width: 600px;
            width: 100%;
            background-color: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-form-container h2 {
            text-align: center;
            color: #FF6500;
            margin-bottom: 1.5rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-section:last-of-type {
            border-bottom: none;
        }
        
        .form-section h3 {
            color: #FF6500;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        
        .profile-form-container label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        
        .profile-form-container input,
        .profile-form-container textarea {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
            font-family: Arial, sans-serif;
        }
        
        .profile-form-container input:focus,
        .profile-form-container textarea:focus {
            border-color: #FFAD60;
        }
        
        .profile-form-container input[type="file"] {
            padding: 0.5rem;
            border-style: dashed;
        }
        
        .profile-form-container button {
            width: 100%;
            padding: 1rem;
            background-color: #FF6500;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .profile-form-container button:hover {
            background-color: #DE8F5F;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .info-box {
            background-color: #FFF4E0;
            border-left: 4px solid #FFAD60;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .info-box p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        small {
            color: #888;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="profile-form-container">
    <h2>Edit Your Profile</h2>
    
    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
        <!-- Basic Information -->
        <div class="form-section">
            <h3>📝 Basic Information</h3>
            
            <label for="profile_name">Profile Name *</label>
            <input type="text" name="profile_name" id="profile_name" value="<?php echo htmlspecialchars($user_data['profile_name'] ?? ''); ?>" required>

            <label for="profile_picture">Profile Picture (Optional)</label>
            <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
            <?php if (!empty($user_data['profile_picture'])): ?>
                <small>Current: <?php echo basename($user_data['profile_picture']); ?></small>
            <?php endif; ?>
        </div>

        <!-- Location Information -->
        <div class="form-section">
            <h3>📍 Location Information</h3>
            
            <div class="info-box">
                <p>💡 Setting your location helps us show you nearby veterinarians within 50km radius.</p>
            </div>
            
            <label for="address">Street Address *</label>
            <input type="text" name="address" id="address" placeholder="e.g., 123 Main Street" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" required>

            <label for="city">City *</label>
            <input type="text" name="city" id="city" placeholder="e.g., Manila, Quezon City" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" required>
            <small>Your location will be automatically geocoded for accurate distance calculations.</small>
        </div>

        <?php if ($user_data['role'] == 'Veterinarian'): ?>
        <!-- Clinic Information (Veterinarians Only) -->
        <div class="form-section">
            <h3>🏥 Clinic Information</h3>
            
            <label for="clinic_name">Clinic Name</label>
            <input type="text" name="clinic_name" id="clinic_name" placeholder="e.g., Happy Paws Veterinary Clinic" value="<?php echo htmlspecialchars($user_data['clinic_name'] ?? ''); ?>">
            <small>This will be displayed to pet owners when they search for veterinarians.</small>
        </div>
        <?php endif; ?>

        <button type="submit">💾 Save Profile</button>
    </form>
    
    <div class="back-link">
        <a href="<?php echo ($user_data['role'] == 'Veterinarian') ? '../veterinarian_dashboard.php' : '../Pet_OwnerDashboard.php'; ?>">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>
