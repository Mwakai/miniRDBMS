<?php

/**
 * miniRDBMS REPL - Interactive SQL Command Line Interface
 *
 * Usage: php repl.php
 */

require_once __DIR__ . '/bootstrap.php';

use Core\Database;
use Core\Config;

$banner = <<<BANNER

╔══════════════════════════════════════╗
║      miniRDBMS Interactive REPL      ║
║                                      ║
║  Type SQL commands or:               ║
║    .exit     - Quit                  ║
║    .tables   - List all tables       ║
║    .schema <table> - Show schema     ║
║    .help     - Show this help        ║
╚══════════════════════════════════════╝

BANNER;

echo $banner;

$db = Database::getInstance();
$historyFile = __DIR__ . '/.repl_history';
$multilineBuffer = '';

// Main REPL loop
while (true) {
    $prompt = $multilineBuffer ? '...> ' : 'miniRDBMS> ';
    echo $prompt;

    $line = trim(fgets(STDIN));

    if ($line === false) {
        break;
    }

    if (empty($line)) {
        continue;
    }

    // Handle special commands
    if (strpos($line, '.') === 0 && empty($multilineBuffer)) {
        $parts = explode(' ', $line, 2);
        $command = $parts[0];
        $arg = $parts[1] ?? null;

        switch ($command) {
            case '.exit':
            case '.quit':
                echo "Goodbye!\n";
                exit(0);

            case '.tables':
                $tables = $db->listTables();
                if (empty($tables)) {
                    echo "No tables found.\n";
                } else {
                    echo "\nTables:\n";
                    foreach ($tables as $table) {
                        echo "  - {$table}\n";
                    }
                    echo "\n";
                }
                continue 2;

            case '.schema':
                if (!$arg) {
                    echo "Usage: .schema <table_name>\n";
                    continue 2;
                }

                $schema = $db->getTableSchema($arg);
                if (!$schema) {
                    echo "Table '{$arg}' not found.\n";
                    continue 2;
                }

                echo "\nTable: {$schema['name']}\n";
                echo "Created: {$schema['created_at']}\n\n";
                echo "Columns:\n";

                foreach ($schema['columns'] as $colName => $colDef) {
                    $type = $colDef['type'];
                    if ($type === 'VARCHAR' && isset($colDef['length'])) {
                        $type .= "({$colDef['length']})";
                    }

                    $constraints = [];
                    if ($colDef['primaryKey'] ?? false) {
                        $constraints[] = 'PRIMARY KEY';
                    }
                    if ($colDef['unique'] ?? false) {
                        $constraints[] = 'UNIQUE';
                    }
                    if (!($colDef['nullable'] ?? true)) {
                        $constraints[] = 'NOT NULL';
                    }
                    if ($colDef['autoIncrement'] ?? false) {
                        $constraints[] = 'AUTO_INCREMENT';
                    }
                    if (isset($colDef['default'])) {
                        $default = $colDef['default'] === null ? 'NULL' : "'{$colDef['default']}'";
                        $constraints[] = "DEFAULT {$default}";
                    }

                    $constraintStr = !empty($constraints) ? ' ' . implode(', ', $constraints) : '';
                    echo "  {$colName}: {$type}{$constraintStr}\n";
                }

                if (!empty($schema['indexes'])) {
                    echo "\nIndexes: " . implode(', ', $schema['indexes']) . "\n";
                }
                echo "\n";
                continue 2;

            case '.help':
                echo $banner;
                continue 2;

            default:
                echo "Unknown command: {$command}\n";
                echo "Type .help for available commands\n";
                continue 2;
        }
    }

    // Accumulate multi-line SQL statements
    $multilineBuffer .= $line . ' ';

    // Check if statement is complete (ends with semicolon)
    if (substr(rtrim($line), -1) !== ';') {
        continue;
    }

    $sql = trim($multilineBuffer);
    $multilineBuffer = '';

    // Remove trailing semicolon
    $sql = rtrim($sql, ';');

    if (empty($sql)) {
        continue;
    }

    // Execute SQL
    $startTime = microtime(true);

    try {
        $result = $db->execute($sql);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($result['success']) {
            $type = $result['type'] ?? 'QUERY';

            if ($type === 'SELECT' && isset($result['rows'])) {
                // Display results as table
                displayResultTable($result['rows']);
                echo "\n{$result['rowCount']} row(s) returned";
            } elseif (isset($result['affectedRows'])) {
                echo $result['message'] ?? 'Success';
                echo " ({$result['affectedRows']} row(s) affected)";
            } else {
                echo $result['message'] ?? 'Success';
            }

            echo " [{$duration}ms]\n\n";
        } else {
            echo "Error: {$result['error']}\n";
            if (isset($result['executionTime'])) {
                echo "[{$duration}ms]\n";
            }
            echo "\n";
        }
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n\n";
    }
}

/**
 * Display result rows as an ASCII table
 */
function displayResultTable(array $rows): void
{
    if (empty($rows)) {
        echo "\n(empty result set)\n";
        return;
    }

    $columns = array_keys($rows[0]);
    $widths = [];

    // Calculate column widths
    foreach ($columns as $col) {
        $widths[$col] = strlen($col);
    }

    foreach ($rows as $row) {
        foreach ($row as $col => $value) {
            $len = strlen((string) $value);
            if ($len > $widths[$col]) {
                $widths[$col] = $len;
            }
        }
    }

    // Maximum width per column
    foreach ($widths as $col => $width) {
        $widths[$col] = min($width, 50);
    }

    // Print top border
    echo "\n┌";
    foreach ($columns as $i => $col) {
        echo str_repeat('─', $widths[$col] + 2);
        echo ($i < count($columns) - 1) ? '┬' : '';
    }
    echo "┐\n";

    // Print header
    echo "│";
    foreach ($columns as $col) {
        echo ' ' . str_pad($col, $widths[$col]) . ' │';
    }
    echo "\n";

    // Print header separator
    echo "├";
    foreach ($columns as $i => $col) {
        echo str_repeat('─', $widths[$col] + 2);
        echo ($i < count($columns) - 1) ? '┼' : '';
    }
    echo "┤\n";

    // Print rows
    foreach ($rows as $row) {
        echo "│";
        foreach ($columns as $col) {
            $value = $row[$col] ?? '';
            if ($value === null) {
                $value = 'NULL';
            }
            $value = (string) $value;
            if (strlen($value) > $widths[$col]) {
                $value = substr($value, 0, $widths[$col] - 3) . '...';
            }
            echo ' ' . str_pad($value, $widths[$col]) . ' │';
        }
        echo "\n";
    }

    // Print bottom border
    echo "└";
    foreach ($columns as $i => $col) {
        echo str_repeat('─', $widths[$col] + 2);
        echo ($i < count($columns) - 1) ? '┴' : '';
    }
    echo "┘\n";
}
