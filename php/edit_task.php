<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task_id = (int)$_POST['task_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;

    // Check that task belongs to user
    $check = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
    $check->execute([$task_id, $_SESSION['user_id']]);
    if ($check->rowCount() === 0) {
        echo "<script>alert('Unauthorized task edit.'); window.location.href='dashboard.php';</script>";
        exit();
    }

    $start = new DateTime($start_time);
    $end = new DateTime($end_time);

    if ($end <= $start) {
        echo "<script>alert('End time must be after start time.'); window.history.back();</script>";
        exit();
    }

    $duration = ($start->diff($end)->days * 24 * 60) +
                ($start->diff($end)->h * 60) +
                $start->diff($end)->i;

    // Update task
    $update = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, date_time = ?, duration = ?, is_urgent = ?, is_recurring = ? WHERE id = ?");
    $update->execute([$title, $description, $start_time, $duration, $is_urgent, $is_recurring, $task_id]);

    // Delete old AI plans
    $pdo->prepare("DELETE FROM task_phases WHERE task_id = ?")->execute([$task_id]);
    $pdo->prepare("DELETE FROM task_phases_detailed WHERE task_id = ?")->execute([$task_id]);

    // Run Python task_generator.py with virtualenv
    $venv_python = "C:\\xampp\\htdocs\\smart-task-organizer\\python\\venv\\Scripts\\python.exe";
    $script_path = "C:\\xampp\\htdocs\\smart-task-organizer\\python\\task_generator.py";

    $cmd = "\"$venv_python\" \"$script_path\" " .
        escapeshellarg($task_id) . " " .
        escapeshellarg($title) . " " .
        escapeshellarg($description) . " " .
        escapeshellarg($start_time) . " " .
        escapeshellarg($end_time) . " > nul 2>&1";

    // Optional debug log
    file_put_contents("../python/debug_log.txt", date("Y-m-d H:i:s") . " CMD: $cmd\n", FILE_APPEND);

    // Run in background
    pclose(popen("start /B " . $cmd, "r"));

    // Redirect to loading screen
    header("Location: loading.html");
    exit();
} else {
    echo "Invalid request.";
}
?>
