<?php
include "db.php";

if (!isset($_SESSION['user_id'])) {
    exit(); // Don't proceed if not logged in
}

$user_id = $_SESSION['user_id'];

// Get tasks starting within next 30 minutes
$stmt = $pdo->prepare("SELECT title, date_time FROM tasks 
                       WHERE user_id = ? 
                       AND date_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

// Output as popup JS alerts
foreach ($tasks as $task) {
    $title = htmlspecialchars($task['title']);
    $time = date("h:i A", strtotime($task['date_time']));
    echo "<script>alert('🔔 Reminder: \"$title\" is starting at $time!');</script>";
}
?>
