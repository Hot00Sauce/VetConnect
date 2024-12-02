<?php
session_start();
$servername = "localhost";
$username = "root"; // Change as needed
$password = ""; // Change as needed
$dbname = "vetconnect"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        // Redirect based on role
        if ($user['role'] == 'Veterinarian') {
            header("Location: veterinarian_dashboard.php");
        } else {
            header("Location: Pet_OwnerDashboard.php");
        }
    } else {
        echo "Invalid email or password.";
    }
}

$conn->close();
?>
