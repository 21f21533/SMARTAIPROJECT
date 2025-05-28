<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch tasks
$stmt = $pdo->prepare("
    SELECT id, title, description, date_time, duration, status 
    FROM tasks 
    WHERE user_id = ? 
    ORDER BY date_time ASC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch subtasks (phases)
$phases_stmt = $pdo->prepare("
    SELECT id, task_id, start_time, end_time, description, is_completed
    FROM task_phases_detailed 
    WHERE task_id IN (SELECT id FROM tasks WHERE user_id = ?)
    ORDER BY start_time ASC
");
$phases_stmt->execute([$user_id]);
$phases = $phases_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group subtasks by task_id
$phases_by_task = [];
foreach ($phases as $phase) {
    $phases_by_task[$phase['task_id']][] = $phase;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta http-equiv="refresh" content="60">
  <link rel="icon" href="../images/icon.ico" type="image/x-icon">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #eef2f5;
      padding: 30px;
    }

    h2 {
      text-align: center;
    }

    .task-card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      margin: 20px auto;
      max-width: 750px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      position: relative;
    }

    .task-card h3 {
      color: #007bff;
      margin: 0;
    }

    .task-info {
      margin: 10px 0;
    }

    .toggle-btn {
      background: #007bff;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 5px;
      cursor: pointer;
    }

    .toggle-btn:hover {
      background: #0056b3;
    }

    .phases {
      margin-top: 12px;
      background: #f8f9fa;
      padding: 12px;
      border-radius: 8px;
    }

    .phase-item {
      margin-bottom: 10px;
    }

    .done {
      text-decoration: line-through;
      color: gray;
    }

    .hidden {
      display: none;
    }

    .status-box {
      position: absolute;
      top: 20px;
      right: 20px;
      padding: 6px 12px;
      font-weight: bold;
      border-radius: 6px;
      font-size: 14px;
      color: white;
    }

    .status-pending {
      background-color: #6c757d;
    }

    .status-complete {
      background-color: #28a745;
    }

    .status-delay {
      background-color: #dc3545;
    }

    a.add-task {
      display: block;
      width: 200px;
      margin: 0 auto 30px;
      text-align: center;
      padding: 12px;
      background: #28a745;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }

    a.add-task:hover {
      background: #218838;
    }
  </style>
</head>
<?php include "../templates/navbar.php"; ?>
<body>

<h2>📋 Your Smart Tasks</h2>

<a href="add_task_form.html" class="add-task">+ Add New Task</a>

<?php if (empty($tasks)): ?>
  <p style="text-align: center;">You haven't added any tasks yet.</p>
<?php endif; ?>

<?php foreach ($tasks as $task): ?>
  <?php
    $phases = $phases_by_task[$task['id']] ?? [];
    $incomplete = array_filter($phases, fn($p) => !$p['is_completed']);
    $start = new DateTime($task['date_time']);
    $end = (clone $start)->modify("+{$task['duration']} minutes");
    $now = new DateTime();

    if (empty($phases)) {
        $statusText = $task['status'];
    } elseif (count($incomplete) === 0) {
        $statusText = 'Complete';
    } elseif ($now > $end) {
        $statusText = 'Delay';
    } else {
        $statusText = 'Pending';
    }

    // Update DB if status has changed
    if ($statusText !== $task['status']) {
        $update = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $update->execute([$statusText, $task['id']]);
    }

    $statusClass = $statusText === 'Complete' ? 'status-complete' :
                   ($statusText === 'Delay' ? 'status-delay' : 'status-pending');
  ?>
  <div class="task-card" id="task-<?= $task['id'] ?>">
    <h3><?= htmlspecialchars($task['title']) ?></h3>
    <div class="status-box <?= $statusClass ?>" id="status-<?= $task['id'] ?>">
      <?= $statusText ?>
    </div>

    <div class="task-info">
      <strong>Description:</strong> <?= nl2br(htmlspecialchars($task['description'] ?? '')) ?><br>
      <strong>Start:</strong> <?= $task['date_time'] ?><br>
      <strong>Duration:</strong> <?= $task['duration'] ?? 0 ?> minutes
    </div>

    <?php if (!empty($phases)): ?>
      <button class="toggle-btn" onclick="togglePhases('phases-<?= $task['id'] ?>')">📋 Show AI Plan</button>
      <div class="phases hidden" id="phases-<?= $task['id'] ?>">
        <strong>AI-Generated Subtasks:</strong>
        <form>
          <?php foreach ($phases as $phase): ?>
            <div class="phase-item">
              <input type="checkbox"
                     id="cb-<?= $phase['id'] ?>"
                     onchange="updatePhase(<?= $phase['id'] ?>, <?= $task['id'] ?>)"
                     <?= $phase['is_completed'] ? 'checked' : '' ?>>
              <label id="label-<?= $phase['id'] ?>"
                     for="cb-<?= $phase['id'] ?>"
                     class="<?= $phase['is_completed'] ? 'done' : '' ?>">
                <strong><?= date("m/d/Y h:i A", strtotime($phase['start_time'])) ?></strong> to
                <strong><?= date("m/d/Y h:i A", strtotime($phase['end_time'])) ?></strong>:
                <?= htmlspecialchars($phase['description']) ?>
              </label>
            </div>
          <?php endforeach; ?>
        </form>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<script>
function togglePhases(id) {
  const el = document.getElementById(id);
  if (el) el.classList.toggle("hidden");
}

function updatePhase(phaseId, taskId) {
  const checkbox = document.getElementById('cb-' + phaseId);
  const label = document.getElementById('label-' + phaseId);

  checkbox.checked
    ? label.classList.add('done')
    : label.classList.remove('done');

  fetch('update_phase.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id: phaseId,
      is_completed: checkbox.checked ? 1 : 0,
      task_id: taskId,
      client_time: new Date().toISOString()
    })
  })
  .then(res => res.json())
  .then(data => {
    const status = document.getElementById('status-' + taskId);
    if (status && data.status) {
      status.textContent = data.status;
      status.className = 'status-box ' + (
        data.status === 'Complete' ? 'status-complete' :
        data.status === 'Delay' ? 'status-delay' :
        'status-pending'
      );
    }
  });
}
</script>

</body>
</html>
