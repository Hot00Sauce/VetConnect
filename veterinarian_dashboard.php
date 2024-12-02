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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VetConnect</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <div class="logo">VetConnect</div>
        <div class="searchBar"><input type="text" placeholder="Search..."></div>

        <ul class="navigation">
            <li><a href="index.html">Home</a></li>
            <li><a href="">Message</a></li>
            <li><a href="">Schedule</a></li>
        </ul>

        <ul id="togglePopup" class="profile-button">
            <li><img src="<?php echo $user['profile_picture']; ?>" alt="Profile Picture" style="width: 50px; height: 50px; border-radius: 50%;"></li>
            <li class="Profiletext"><?php echo $user['profile_name']; ?></li>
        </ul>
    </div>

    <div class="mainContent">
        <h1>Welcome, <?php echo $user['profile_name']; ?>!</h1>
    </div>
    
    <div class="footer">
        © 2024 VetConnect. All rights reserved.
    </div>

    <script src="register.js"></script>
</body>
</html>
