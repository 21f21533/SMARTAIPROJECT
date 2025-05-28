<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['task_id'])) {
    echo "Invalid request.";
    exit();
}

$task_id = (int)$_GET['task_id'];

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$task_id, $_SESSION['user_id']]);
$task = $stmt->fetch();

if (!$task) {
    echo "<script>alert('Task not found or unauthorized.'); window.location.href='dashboard.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Task</title>
  <link rel="icon" href="../images/icon.ico" type="image/x-icon">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f8;
      padding: 40px;
    }
    .container {
      max-width: 600px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #007bff;
    }
    label {
      font-weight: bold;
      display: block;
      margin-top: 20px;
    }
    input, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    input[type="checkbox"] {
      width: auto;
      margin-right: 10px;
    }
    input[type="submit"] {
      margin-top: 25px;
      width: 100%;
      background: #ffc107;
      color: black;
      font-size: 16px;
      border: none;
      padding: 12px;
      border-radius: 5px;
      cursor: pointer;
    }
    input[type="submit"]:hover {
      background: #e0a800;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>✏️ Edit Task</h2>
    <form action="edit_task.php" method="POST">
      <input type="hidden" name="task_id" value="<?= $task_id ?>">

      <label for="title">Task Title:</label>
      <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>

      <label for="description">Description:</label>
      <textarea name="description" rows="4" required><?= htmlspecialchars($task['description']) ?></textarea>

      <label for="start_time">Start Date & Time:</label>
      <input type="datetime-local" name="start_time" value="<?= date('Y-m-d\TH:i', strtotime($task['date_time'])) ?>" required>

      <label for="end_time">End Date & Time:</label>
      <?php
        $end_time = new DateTime($task['date_time']);
        $end_time->modify("+{$task['duration']} minutes");
      ?>
      <input type="datetime-local" name="end_time" value="<?= $end_time->format('Y-m-d\TH:i') ?>" required>

      <label><input type="checkbox" name="is_urgent" <?= $task['is_urgent'] ? 'checked' : '' ?>> Mark as Urgent</label>
      <label><input type="checkbox" name="is_recurring" <?= $task['is_recurring'] ? 'checked' : '' ?>> Make it Recurring</label>

      <input type="submit" value="Update Task">
    </form>
  </div>
</body>
</html>
