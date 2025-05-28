<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;

    try {
        $start = new DateTime($start_time);
        $end = new DateTime($end_time);

        if ($end <= $start) {
            echo "<script>alert('End time must be after start time.'); window.history.back();</script>";
            exit();
        }

        $interval = $start->diff($end);
        $duration = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

        $status = 'Pending';

        // Insert task into DB
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, date_time, duration, is_urgent, is_recurring, status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $start_time, $duration, $is_urgent, $is_recurring, $status]);

        $task_id = $pdo->lastInsertId();

        // === Python path inside virtual environment ===
        $venv_python = "../python/venv/Scripts/python.exe";
        $script_path = "../python/task_generator.py";

        // === Safe escaping and command building ===
        $cmd = $venv_python . " " . $script_path . " " .
            escapeshellarg($task_id) . " " .
            escapeshellarg($title) . " " .
            escapeshellarg($description) . " " .
            escapeshellarg($start_time) . " " .
            escapeshellarg($end_time) . " > nul 2>&1";

        // Log command
        file_put_contents("../python/debug_log.txt", date("Y-m-d H:i:s") . " CMD: $cmd\n", FILE_APPEND);

        // Run Python in background (Windows)
        pclose(popen("start /B " . $cmd, "r"));

        // Redirect to loading
        header("Location: loading.html");
        exit();
    } catch (Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
        exit();
    }
} else {
    echo "Invalid request.";
}
?>
