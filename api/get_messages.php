<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
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
$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;

if ($conversation_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid conversation ID']);
    exit();
}

// Verify user is part of this conversation
$verify_sql = "SELECT * FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

// Get messages
$sql = "SELECT 
    m.id,
    m.message,
    m.sender_id,
    m.receiver_id,
    m.is_read,
    m.created_at,
    u.profile_name as sender_name,
    u.profile_picture as sender_picture
FROM messages m
INNER JOIN users u ON m.sender_id = u.id
WHERE m.conversation_id = ?
ORDER BY m.created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Mark messages as read
$update_sql = "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $conversation_id, $user_id);
$update_stmt->execute();

echo json_encode([
    'success' => true,
    'messages' => $messages
]);

$conn->close();
?>
