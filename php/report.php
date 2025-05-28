<?php
session_start();
include "db.php";
include "../templates/navbar.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch tasks and count statuses
$stmt = $pdo->prepare("
    SELECT 
        t.id, t.title, t.date_time, t.duration, t.status,
        (SELECT COUNT(*) FROM task_phases WHERE task_id = t.id) AS phase_count
    FROM tasks t
    WHERE t.user_id = ?
    ORDER BY t.date_time ASC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

// Count status values
$status_counts = ['Complete' => 0, 'Delay' => 0, 'Pending' => 0];
foreach ($tasks as $task) {
    $status = ucfirst(strtolower($task['status']));
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Task Report</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 40px;
      background: #f2f5f9;
    }

    h2 {
      text-align: center;
      color: #333;
    }

    .container {
      display: flex;
      justify-content: space-between;
      gap: 30px;
      margin-top: 30px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: left;
    }

    th {
      background-color: #007bff;
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .left {
      flex: 1.2;
    }

    .right {
      flex: 0.8;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    canvas {
      max-width: 100%;
    }

    a.back {
      display: inline-block;
      margin-top: 25px;
      color: #007bff;
      text-decoration: none;
    }

    a.back:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <h2>Your Task Report</h2>

  <div class="container">
    <div class="left">
      <?php if (count($tasks) === 0): ?>
        <p>No tasks available.</p>
      <?php else: ?>
        <table>
          <tr>
            <th>Title</th>
            <th>Start Time</th>
            <th>Duration (mins)</th>
            <th>Status</th>
            <th># of Subtasks</th>
          </tr>
          <?php foreach ($tasks as $task): ?>
            <tr>
              <td><?= htmlspecialchars($task['title']) ?></td>
              <td><?= $task['date_time'] ?></td>
              <td><?= $task['duration'] ?></td>
              <td><?= $task['status'] ?></td>
              <td><?= $task['phase_count'] ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
      <a class="back" href="dashboard.php">← Back to Dashboard</a>
    </div>
    <br>
    <div class="right">
  <div style="width: 350px; height: 350px;">
    <canvas id="statusChart"></canvas>
  </div>
</div>

  </div>

  <script>
    const ctx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: ['Complete', 'Delay', 'Pending'],
        datasets: [{
          label: 'Task Status Distribution',
          data: [
            <?= $status_counts['Complete'] ?>,
            <?= $status_counts['Delay'] ?>,
            <?= $status_counts['Pending'] ?>
          ],
          backgroundColor: [
            '#28a745', // green
            '#dc3545', // red
            '#ffc107'  // yellow
          ],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          },
          title: {
            display: true,
            text: 'Task Status Overview'
          }
        }
      }
    });
  </script>

</body>
</html>
