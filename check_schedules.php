<?php
/**
 * Schedule Notification Checker
 * This script should be run periodically (e.g., via cron job) to check for upcoming schedules
 * and mark them for notification
 */

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vetconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get schedules that are due within 24 hours and haven't been notified
$sql = "SELECT s.*, u.email, u.profile_name 
        FROM schedules s 
        INNER JOIN users u ON s.user_id = u.id 
        WHERE s.status = 'pending' 
        AND s.notified = FALSE 
        AND s.schedule_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
        ORDER BY s.schedule_date ASC";

$result = $conn->query($sql);

$notifications_sent = 0;

if ($result && $result->num_rows > 0) {
    while ($schedule = $result->fetch_assoc()) {
        // Here you would send an email or push notification
        // For now, we'll just mark it as notified
        
        $schedule_date = new DateTime($schedule['schedule_date']);
        $now = new DateTime();
        $diff = $now->diff($schedule_date);
        $hours_until = ($diff->days * 24) + $diff->h;
        
        echo "Notification: {$schedule['profile_name']} has a {$schedule['schedule_type']} for {$schedule['pet_name']} in {$hours_until} hours\n";
        echo "Title: {$schedule['title']}\n";
        echo "Date: {$schedule['schedule_date']}\n\n";
        
        // Mark as notified
        $update_sql = "UPDATE schedules SET notified = TRUE WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $schedule['id']);
        $stmt->execute();
        $stmt->close();
        
        $notifications_sent++;
    }
}

echo "Total notifications processed: {$notifications_sent}\n";

$conn->close();
?>
