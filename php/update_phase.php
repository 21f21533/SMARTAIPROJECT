<?php
include "db.php";
session_start();

header('Content-Type: application/json');

// Get input data from JSON
$data = json_decode(file_get_contents("php://input"), true);

$phase_id     = $data['id'] ?? null;
$is_completed = $data['is_completed'] ?? null;
$task_id      = $data['task_id'] ?? null;
$client_time  = $data['client_time'] ?? null;
$user_id      = $_SESSION['user_id'] ?? 0;

// Validate input
if (!$phase_id || !$task_id || !is_numeric($is_completed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// 1. Update the subtask's completed status
$stmt = $pdo->prepare("UPDATE task_phases_detailed SET is_completed = ? WHERE id = ?");
$stmt->execute([$is_completed, $phase_id]);

// 2. Log the action in subtask_logs
$log = $pdo->prepare("
    INSERT INTO subtask_logs (user_id, subtask_id, is_completed, timestamp)
    VALUES (?, ?, ?, NOW())
");
$log->execute([$user_id, $phase_id, $is_completed]);

// 3. Check remaining incomplete subtasks
$check = $pdo->prepare("
    SELECT COUNT(*) 
    FROM task_phases_detailed 
    WHERE task_id = ? AND is_completed = 0
");
$check->execute([$task_id]);
$remaining = $check->fetchColumn();

// 4. Fetch task time info
$task_stmt = $pdo->prepare("SELECT date_time, duration FROM tasks WHERE id = ?");
$task_stmt->execute([$task_id]);
$task = $task_stmt->fetch(PDO::FETCH_ASSOC);

// 5. Use client time if available, fallback to server time
$now = $client_time ? new DateTime($client_time) : new DateTime();
$start = new DateTime($task['date_time']);
$end = (clone $start)->modify("+{$task['duration']} minutes");

// 6. Determine task status
if ($remaining == 0) {
    $new_status = 'Complete';
} else {
    $new_status = $now > $end ? 'Delay' : 'Pending';
}

// 7. Update task status
$update = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
$update->execute([$new_status, $task_id]);

// 8. Respond to frontend
echo json_encode([
    'success' => true,
    'status' => $new_status
]);
