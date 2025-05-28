<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check if username or email already exists
    $check = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $check->execute([$username, $email]);
    if ($check->fetch()) {
        echo "<script>alert('Username or Email already exists.'); window.location.href='register.html';</script>";
        exit();
    }

    // Hash password
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashed]);

    echo "<script>alert('Account created successfully! Please login.'); window.location.href='login.html';</script>";
    exit();
} else {
    echo "Invalid request.";
}
?>
