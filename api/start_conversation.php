<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vetconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$other_user_id = isset($data['other_user_id']) ? intval($data['other_user_id']) : 0;

if ($other_user_id <= 0 || $other_user_id == $user_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

// Check if conversation already exists
$user1 = min($user_id, $other_user_id);
$user2 = max($user_id, $other_user_id);

$check_sql = "SELECT id FROM conversations WHERE user1_id = ? AND user2_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $user1, $user2);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $row = $check_result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'conversation_id' => $row['id'],
        'existing' => true
    ]);
} else {
    // Create new conversation
    $insert_sql = "INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $user1, $user2);
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'conversation_id' => $insert_stmt->insert_id,
            'existing' => false
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create conversation']);
    }
}

$conn->close();
?>
