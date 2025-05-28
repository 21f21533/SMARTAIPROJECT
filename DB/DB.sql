-- Create the database
CREATE DATABASE IF NOT EXISTS smart_tasks;
USE smart_tasks;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Main Tasks Table
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date_time DATETIME NOT NULL,
    duration INT NOT NULL,
    is_urgent BOOLEAN DEFAULT 0,
    is_recurring BOOLEAN DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Raw AI Output Table (optional)
CREATE TABLE IF NOT EXISTS task_phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    phase_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Normalized AI Output Table (subtasks)
CREATE TABLE IF NOT EXISTS task_phases_detailed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    start_time DATETIME,
    end_time DATETIME,
    description TEXT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Subtask Logs Table (for checkbox history)
CREATE TABLE IF NOT EXISTS subtask_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subtask_id INT NOT NULL,
    is_completed BOOLEAN NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subtask_id) REFERENCES task_phases_detailed(id) ON DELETE CASCADE
);
