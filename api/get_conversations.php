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

// Get all conversations for this user
$sql = "SELECT 
    c.id as conversation_id,
    c.last_message_time,
    u.id as other_user_id,
    u.profile_name,
    u.profile_picture,
    u.role,
    m.message as last_message,
    m.sender_id as last_sender_id,
    COALESCE(unread.unread_count, 0) as unread_count
FROM conversations c
INNER JOIN users u ON (
    CASE 
        WHEN c.user1_id = ? THEN c.user2_id
        ELSE c.user1_id
    END = u.id
)
LEFT JOIN messages m ON (
    m.id = (
        SELECT id FROM messages 
        WHERE conversation_id = c.id 
        ORDER BY created_at DESC 
        LIMIT 1
    )
)
LEFT JOIN (
    SELECT conversation_id, COUNT(*) as unread_count
    FROM messages
    WHERE receiver_id = ? AND is_read = 0
    GROUP BY conversation_id
) unread ON unread.conversation_id = c.id
WHERE c.user1_id = ? OR c.user2_id = ?
ORDER BY c.last_message_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}

echo json_encode([
    'success' => true,
    'conversations' => $conversations
]);

$conn->close();
?>
