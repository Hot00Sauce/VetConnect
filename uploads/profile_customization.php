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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $profile_name = $_POST['profile_name'];

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

    // Check if uploadOk is still 1 (i.e., passed all checks)
    if ($uploadOk == 1) {
        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Prepare and execute the update query
            $target_file_db = "uploads/" . basename($_FILES["profile_picture"]["name"]); // Store relative path in DB
            $sql = "UPDATE users SET profile_name = ?, profile_picture = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $profile_name, $target_file_db, $_SESSION['user_id']);
            $stmt->execute();

            // Redirect to the user's profile page
            header("Location: ../Pet_OwnerDashboard.php.php"); // Adjust if user.php is in the root folder
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
    <title>Customize Profile</title>
    <style>
        form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f7f7f7;
            border-radius: 8px;
        }
        label, input, button {
            display: block;
            margin-bottom: 10px;
            width: 100%;
        }
        input[type="file"] {
            padding: 5px;
        }
    </style>
</head>
<body>

<h2>Customize Your Profile</h2>
<form action="profile_customization.php" method="POST" enctype="multipart/form-data">
    <label for="profile_name">Profile Name:</label>
    <input type="text" name="profile_name" id="profile_name" required>

    <label for="profile_picture">Upload Profile Picture:</label>
    <input type="file" name="profile_picture" id="profile_picture" required>

    <button type="submit">Save Profile</button>
</form>

</body>
</html>
