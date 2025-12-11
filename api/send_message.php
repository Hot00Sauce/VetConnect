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

$conversation_id = isset($data['conversation_id']) ? intval($data['conversation_id']) : 0;
$message = isset($data['message']) ? trim($data['message']) : '';

if ($conversation_id <= 0 || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

// Verify user is part of this conversation and get receiver
$verify_sql = "SELECT user1_id, user2_id FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

$conversation = $verify_result->fetch_assoc();
$receiver_id = ($conversation['user1_id'] == $user_id) ? $conversation['user2_id'] : $conversation['user1_id'];

// Insert message
$insert_sql = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("iiis", $conversation_id, $user_id, $receiver_id, $message);

if ($insert_stmt->execute()) {
    $message_id = $insert_stmt->insert_id;
    
    // Update conversation last_message_time
    $update_sql = "UPDATE conversations SET last_message_time = CURRENT_TIMESTAMP WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $conversation_id);
    $update_stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'created_at' => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}

$conn->close();
?>
