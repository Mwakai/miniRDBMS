<?php

/**
 * miniRDBMS Database Setup Script
 *
 * Creates tables and sample data for the task management demo
 */

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;

header('Content-Type: text/plain');

$db = Database::getInstance();
$output = [];

// Drop existing tables if they exist
$output[] = "Dropping existing tables...";

try {
    $result = $db->execute("DROP TABLE tasks");
    $output[] = "  - tasks: " . ($result['success'] ? "Dropped" : "Not found");
} catch (Exception $e) {
    $output[] = "  - tasks: Not found";
}

try {
    $result = $db->execute("DROP TABLE users");
    $output[] = "  - users: " . ($result['success'] ? "Dropped" : "Not found");
} catch (Exception $e) {
    $output[] = "  - users: Not found";
}

$output[] = "";
$output[] = "Creating tables...";

// Create users table
$createUsers = "CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    created_at DATETIME
)";

$result = $db->execute($createUsers);
if ($result['success']) {
    $output[] = "  ✓ users table created";
} else {
    $output[] = "  ✗ Failed to create users table: " . $result['error'];
    echo implode("\n", $output);
    exit;
}

// Create tasks table
$createTasks = "CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    due_date DATE,
    completed BOOLEAN DEFAULT 0,
    created_at DATETIME
)";

$result = $db->execute($createTasks);
if ($result['success']) {
    $output[] = "  ✓ tasks table created";
} else {
    $output[] = "  ✗ Failed to create tasks table: " . $result['error'];
    echo implode("\n", $output);
    exit;
}

$output[] = "";
$output[] = "Inserting sample data...";

// Insert sample users
$users = [
    ["alice", "alice@example.com"],
    ["bob", "bob@example.com"],
    ["charlie", "charlie@example.com"]
];

foreach ($users as $user) {
    $sql = "INSERT INTO users (username, email, created_at) VALUES ('{$user[0]}', '{$user[1]}', '2026-01-15 10:00:00')";
    $result = $db->execute($sql);

    if ($result['success']) {
        $output[] = "  ✓ User '{$user[0]}' added";
    } else {
        $output[] = "  ✗ Failed to add user '{$user[0]}': " . $result['error'];
    }
}

// Insert sample tasks
$tasks = [
    [1, "Complete project documentation", "Write comprehensive documentation for the miniRDBMS project", "in_progress", "2026-01-20", 0],
    [1, "Fix bug in parser", "Fix the parser issue with nested queries", "pending", "2026-01-18", 0],
    [2, "Review code changes", "Review pull requests from the team", "completed", "2026-01-16", 1],
    [2, "Update dependencies", "Update all npm packages to latest versions", "pending", "2026-01-22", 0],
    [3, "Write unit tests", "Increase test coverage to 80%", "in_progress", "2026-01-25", 0],
    [3, "Deploy to production", "Deploy version 1.0 to production server", "pending", "2026-01-30", 0]
];

foreach ($tasks as $task) {
    $sql = "INSERT INTO tasks (user_id, title, description, status, due_date, completed, created_at)
            VALUES ({$task[0]}, '{$task[1]}', '{$task[2]}', '{$task[3]}', '{$task[4]}', {$task[5]}, '2026-01-15 10:00:00')";
    $result = $db->execute($sql);

    if ($result['success']) {
        $output[] = "  ✓ Task '{$task[1]}' added";
    } else {
        $output[] = "  ✗ Failed to add task: " . $result['error'];
    }
}

$output[] = "";
$output[] = "Database setup complete!";
$output[] = "";
$output[] = "Summary:";
$output[] = "  - Tables created: 2 (users, tasks)";
$output[] = "  - Users added: " . count($users);
$output[] = "  - Tasks added: " . count($tasks);

echo implode("\n", $output);
