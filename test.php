<?php

/**
 * miniRDBMS Test Script
 *
 * Tests basic functionality of the RDBMS
 */

require_once __DIR__ . '/bootstrap.php';

use Core\Database;

echo "==================================================\n";
echo "miniRDBMS Test Suite\n";
echo "==================================================\n\n";

$db = Database::getInstance();
$testsPassed = 0;
$testsFailed = 0;

function runTest($name, $sql, $expectedSuccess = true) {
    global $db, $testsPassed, $testsFailed;

    echo "Testing: $name\n";
    echo "SQL: $sql\n";

    try {
        $result = $db->execute($sql);

        if ($result['success'] === $expectedSuccess) {
            echo "✓ PASS\n";
            if (isset($result['message'])) {
                echo "  Message: {$result['message']}\n";
            }
            if (isset($result['rowCount'])) {
                echo "  Rows: {$result['rowCount']}\n";
            }
            $testsPassed++;
        } else {
            echo "✗ FAIL - Expected success={$expectedSuccess}, got " . ($result['success'] ? 'true' : 'false') . "\n";
            if (isset($result['error'])) {
                echo "  Error: {$result['error']}\n";
            }
            $testsFailed++;
        }
    } catch (Exception $e) {
        echo "✗ FAIL - Exception: {$e->getMessage()}\n";
        $testsFailed++;
    }

    echo "\n";
    return $result ?? null;
}

// Test 1: Create table
runTest(
    "CREATE TABLE - users",
    "CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100),
        created_at DATETIME
    )"
);

// Test 2: Insert single row
runTest(
    "INSERT - single user",
    "INSERT INTO users (username, email, created_at)
     VALUES ('john', 'john@example.com', '2026-01-15 10:00:00')"
);

// Test 3: Insert multiple rows
runTest(
    "INSERT - multiple users",
    "INSERT INTO users (username, email, created_at)
     VALUES ('jane', 'jane@example.com', '2026-01-15 10:05:00'),
            ('bob', 'bob@example.com', '2026-01-15 10:10:00')"
);

// Test 4: SELECT all
$result = runTest(
    "SELECT - all users",
    "SELECT * FROM users"
);

if ($result && $result['success'] && isset($result['rows'])) {
    echo "Returned rows:\n";
    foreach ($result['rows'] as $row) {
        echo "  - ID: {$row['id']}, Username: {$row['username']}, Email: {$row['email']}\n";
    }
    echo "\n";
}

// Test 5: SELECT with WHERE
runTest(
    "SELECT - with WHERE clause",
    "SELECT username, email FROM users WHERE username = 'john'"
);

// Test 6: CREATE second table
runTest(
    "CREATE TABLE - tasks",
    "CREATE TABLE tasks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        title VARCHAR(200) NOT NULL,
        completed BOOLEAN DEFAULT 0
    )"
);

// Test 7: Insert tasks
runTest(
    "INSERT - tasks",
    "INSERT INTO tasks (user_id, title, completed)
     VALUES (1, 'Complete project', 0),
            (1, 'Write tests', 1),
            (2, 'Review code', 0)"
);

// Test 8: INNER JOIN
$result = runTest(
    "SELECT - INNER JOIN",
    "SELECT u.username, t.title, t.completed
     FROM users u
     INNER JOIN tasks t ON u.id = t.user_id"
);

if ($result && $result['success'] && isset($result['rows'])) {
    echo "Join results:\n";
    foreach ($result['rows'] as $row) {
        echo "  - {$row['username']}: {$row['title']} (completed: {$row['completed']})\n";
    }
    echo "\n";
}

// Test 9: SELECT with ORDER BY
runTest(
    "SELECT - ORDER BY",
    "SELECT username FROM users ORDER BY username DESC"
);

// Test 10: SELECT with LIMIT
runTest(
    "SELECT - with LIMIT",
    "SELECT * FROM users LIMIT 2"
);

// Test 11: UPDATE
runTest(
    "UPDATE - set completed flag",
    "UPDATE tasks SET completed = 1 WHERE user_id = 1"
);

// Test 12: DELETE
runTest(
    "DELETE - remove completed tasks",
    "DELETE FROM tasks WHERE completed = 1"
);

// Test 13: SELECT after DELETE
runTest(
    "SELECT - verify deletion",
    "SELECT COUNT(*) as remaining FROM tasks"
);

// Test 14: DROP tables
runTest(
    "DROP TABLE - tasks",
    "DROP TABLE tasks"
);

runTest(
    "DROP TABLE - users",
    "DROP TABLE users"
);

// Test 15: Try to SELECT from dropped table (should fail)
runTest(
    "SELECT - from dropped table (should fail)",
    "SELECT * FROM users",
    false
);

echo "==================================================\n";
echo "Test Results\n";
echo "==================================================\n";
echo "Passed: $testsPassed\n";
echo "Failed: $testsFailed\n";
echo "Total: " . ($testsPassed + $testsFailed) . "\n";
echo "\n";

if ($testsFailed === 0) {
    echo "✓ All tests passed!\n";
} else {
    echo "✗ Some tests failed.\n";
}
