<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if (isset($_GET['task_id'])) {
    $task_id = (int)$_GET['task_id'];

    // Make sure the task belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $_SESSION['user_id']]);
    $task = $stmt->fetch();

    if (!$task) {
        echo "<script>alert('Task not found or unauthorized'); window.location.href='dashboard.php';</script>";
        exit();
    }

    // Delete from all related tables (cascade should handle this if foreign keys are set)
    $pdo->prepare("DELETE FROM task_phases WHERE task_id = ?")->execute([$task_id]);
    $pdo->prepare("DELETE FROM task_phases_detailed WHERE task_id = ?")->execute([$task_id]);
    $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]);

    header("Location: dashboard.php?deleted=1");
    exit();
} else {
    echo "Invalid request.";
}
?>
