<?php

/**
 * miniRDBMS REST API
 *
 * Provides JSON API endpoints for executing SQL queries
 */

require_once __DIR__ . '/../bootstrap.php';

use Core\Database;

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize database
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database initialization failed: ' . $e->getMessage()
    ]);
    exit;
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'info';

    switch ($action) {
        case 'tables':
            $tables = $db->listTables();
            echo json_encode([
                'success' => true,
                'tables' => $tables,
                'count' => count($tables)
            ]);
            break;

        case 'schema':
            $table = $_GET['table'] ?? null;
            if (!$table) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing table parameter'
                ]);
                break;
            }

            $schema = $db->getTableSchema($table);
            if (!$schema) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => "Table '{$table}' not found"
                ]);
                break;
            }

            echo json_encode([
                'success' => true,
                'schema' => $schema
            ]);
            break;

        case 'info':
        default:
            echo json_encode([
                'success' => true,
                'name' => 'miniRDBMS API',
                'version' => '1.0.0',
                'endpoints' => [
                    'GET /api.php?action=tables' => 'List all tables',
                    'GET /api.php?action=schema&table=X' => 'Get table schema',
                    'POST /api.php' => 'Execute SQL query (body: {"sql": "..."})'
                ]
            ]);
            break;
    }
    exit;
}

// Handle POST requests (SQL execution)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON: ' . json_last_error_msg()
        ]);
        exit;
    }

    $sql = $input['sql'] ?? null;

    if (!$sql || !is_string($sql)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing or invalid SQL query'
        ]);
        exit;
    }

    // Trim and remove trailing semicolons
    $sql = trim($sql);
    $sql = rtrim($sql, ';');

    if (empty($sql)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Empty SQL query'
        ]);
        exit;
    }

    // Execute the query
    try {
        $startTime = microtime(true);
        $result = $db->execute($sql);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $result['durationMs'] = $duration;

        // Set appropriate HTTP status code
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'type' => 'exception'
        ], JSON_PRETTY_PRINT);
    }
    exit;
}

// Handle unsupported methods
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Method not allowed'
]);
