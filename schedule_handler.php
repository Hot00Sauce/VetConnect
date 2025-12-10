<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vetconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle different actions
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'create':
        createSchedule($conn, $user_id);
        break;
    
    case 'list':
        listSchedules($conn, $user_id);
        break;
    
    case 'update':
        updateSchedule($conn, $user_id);
        break;
    
    case 'delete':
        deleteSchedule($conn, $user_id);
        break;
    
    case 'mark_completed':
        markCompleted($conn, $user_id);
        break;
    
    case 'cancel':
        cancelSchedule($conn, $user_id);
        break;
    
    case 'get':
        getSchedule($conn, $user_id);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();

// Function to create a new schedule
function createSchedule($conn, $user_id) {
    $pet_name = $_POST['petName'] ?? '';
    $schedule_type = $_POST['scheduleType'] ?? '';
    $vet_id = !empty($_POST['vetId']) ? $_POST['vetId'] : NULL;
    $schedule_date = $_POST['scheduleDate'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Validate required fields
    if (empty($pet_name) || empty($schedule_type) || empty($schedule_date) || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        return;
    }
    
    // Validate schedule type
    $valid_types = ['clinic_visit', 'vaccination', 'medication'];
    if (!in_array($schedule_type, $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid schedule type']);
        return;
    }
    
    // Convert datetime-local format to MySQL datetime format
    $schedule_date = str_replace('T', ' ', $schedule_date) . ':00';
    
    // Insert schedule
    $sql = "INSERT INTO schedules (user_id, vet_id, pet_name, schedule_type, schedule_date, title, description, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssss", $user_id, $vet_id, $pet_name, $schedule_type, $schedule_date, $title, $description);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Schedule created successfully',
            'schedule_id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create schedule: ' . $conn->error]);
    }
    
    $stmt->close();
}

// Function to list all schedules for a user
function listSchedules($conn, $user_id) {
    $sql = "SELECT s.*, u.profile_name as vet_name 
            FROM schedules s 
            LEFT JOIN users u ON s.vet_id = u.id 
            WHERE s.user_id = ? AND s.status = 'pending' AND s.schedule_date >= NOW() 
            ORDER BY s.schedule_date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    
    echo json_encode(['success' => true, 'schedules' => $schedules]);
    $stmt->close();
}

// Function to update a schedule
function updateSchedule($conn, $user_id) {
    $schedule_id = $_POST['scheduleId'] ?? 0;
    $pet_name = $_POST['petName'] ?? '';
    $schedule_type = $_POST['scheduleType'] ?? '';
    $vet_id = !empty($_POST['vetId']) ? $_POST['vetId'] : NULL;
    $schedule_date = $_POST['scheduleDate'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Validate schedule ownership
    $check_sql = "SELECT id FROM schedules WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $schedule_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found or unauthorized']);
        $check_stmt->close();
        return;
    }
    $check_stmt->close();
    
    // Convert datetime-local format to MySQL datetime format
    $schedule_date = str_replace('T', ' ', $schedule_date) . ':00';
    
    // Update schedule
    $sql = "UPDATE schedules SET 
            pet_name = ?, 
            schedule_type = ?, 
            vet_id = ?, 
            schedule_date = ?, 
            title = ?, 
            description = ? 
            WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssii", $pet_name, $schedule_type, $vet_id, $schedule_date, $title, $description, $schedule_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update schedule']);
    }
    
    $stmt->close();
}

// Function to delete a schedule
function deleteSchedule($conn, $user_id) {
    $schedule_id = $_POST['scheduleId'] ?? 0;
    
    $sql = "DELETE FROM schedules WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $schedule_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete schedule or unauthorized']);
    }
    
    $stmt->close();
}

// Function to mark a schedule as completed
function markCompleted($conn, $user_id) {
    $schedule_id = $_POST['scheduleId'] ?? 0;
    
    $sql = "UPDATE schedules SET status = 'completed' WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $schedule_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Schedule marked as completed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update schedule or unauthorized']);
    }
    
    $stmt->close();
}

// Function to cancel a schedule
function cancelSchedule($conn, $user_id) {
    $schedule_id = $_POST['scheduleId'] ?? 0;
    
    $sql = "UPDATE schedules SET status = 'cancelled' WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $schedule_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Schedule cancelled']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel schedule or unauthorized']);
    }
    
    $stmt->close();
}

// Function to get a single schedule
function getSchedule($conn, $user_id) {
    $schedule_id = $_GET['scheduleId'] ?? 0;
    
    $sql = "SELECT s.*, u.profile_name as vet_name 
            FROM schedules s 
            LEFT JOIN users u ON s.vet_id = u.id 
            WHERE s.id = ? AND s.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $schedule_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $schedule = $result->fetch_assoc();
        echo json_encode(['success' => true, 'schedule' => $schedule]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Schedule not found or unauthorized']);
    }
    
    $stmt->close();
}
?>
