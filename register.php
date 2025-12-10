<?php
// Connect to the database
$servername = "localhost"; // Change as needed
$username = "root"; // Change as needed
$password = ""; // Change as needed
$dbname = "vetconnect"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $role = $_POST['role'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Secure password hash

    // Check if email already exists
    $sql_check = "SELECT * FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email is already registered. Please use a different email or login.'); window.location.href='index.php';</script>";
    } else {
        // Insert the user data into the database with default profile values
        $default_profile_name = $name;
        $default_profile_picture = "assets/profile-user-svgrepo-com.svg";
        
        $sql = "INSERT INTO users (role, name, email, password, profile_name, profile_picture) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $role, $name, $email, $password, $default_profile_name, $default_profile_picture);

        if ($stmt->execute()) {
            echo "<script>alert('Registration successful! You can now login.'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "'); window.history.back();</script>";
        }
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
