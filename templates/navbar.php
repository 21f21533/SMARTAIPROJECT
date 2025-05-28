<?php if (!isset($_SESSION)) session_start(); ?>
<style>
  .navbar {
    background-color: #007bff;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
    font-family: Arial, sans-serif;
  }

  .navbar a {
    color: white;
    text-decoration: none;
    margin: 0 12px;
    font-weight: bold;
  }

  .navbar a:hover {
    text-decoration: underline;
  }

  .navbar .right {
    display: flex;
    align-items: center;
  }
</style>

<div class="navbar">
  <div class="left">
    <a href="dashboard.php">📋 Dashboard</a>
    <a href="report.php">📊 Report</a>
  </div>
  <div class="right">
    <span>👤 <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
    <a href="logout.php" style="margin-left: 15px;">🚪 Logout</a>
  </div>
</div>
