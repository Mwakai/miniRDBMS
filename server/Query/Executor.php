<?php

namespace Query;

use Core\Table;
use Core\Database;
use Storage\StorageEngine;

/**
 * Executor - Query Execution Engine
 *
 * Executes parsed SQL commands and returns results
 */
class Executor
{
    private static $instance = null;
    private StorageEngine $storage;

    private function __construct()
    {
        $this->storage = StorageEngine::getInstance();
    }

    public static function getInstance(): Executor
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Execute a parsed SQL command
     */
    public function execute(array $parsed, Database $db): array
    {
        $startTime = microtime(true);

        try {
            $result = match($parsed['type']) {
                'CREATE' => $this->executeCreate($parsed, $db),
                'DROP' => $this->executeDrop($parsed, $db),
                'INSERT' => $this->executeInsert($parsed, $db),
                'SELECT' => $this->executeSelect($parsed, $db),
                'UPDATE' => $this->executeUpdate($parsed, $db),
                'DELETE' => $this->executeDelete($parsed, $db),
                default => ['success' => false, 'error' => 'Unknown command type: ' . $parsed['type']]
            };

            $result['executionTime'] = round(microtime(true) - $startTime, 4);
            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'executionTime' => round(microtime(true) - $startTime, 4)
            ];
        }
    }

    /**
     * Execute CREATE TABLE
     */
    private function executeCreate(array $parsed, Database $db): array
    {
        $tableName = $parsed['table'];

        if ($this->storage->tableExists($tableName)) {
            return [
                'success' => false,
                'error' => "Table '{$tableName}' already exists"
            ];
        }

        $schema = [
            'name' => $tableName,
            'columns' => $parsed['columns'],
            'primaryKey' => null,
            'indexes' => [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        foreach ($parsed['columns'] as $columnName => $columnDef) {
            if ($columnDef['primaryKey']) {
                $schema['primaryKey'] = $columnName;
                $schema['indexes'][] = $columnName;
            } elseif ($columnDef['unique']) {
                $schema['indexes'][] = $columnName;
            }
        }

        $data = [
            'rows' => [],
            'rowCount' => 0,
            'nextAutoIncrement' => 1
        ];

        $this->storage->saveSchema($tableName, $schema);
        $this->storage->saveTable($tableName, $data);
        $this->storage->saveIndex($tableName, []);

        return [
            'success' => true,
            'type' => 'CREATE',
            'table' => $tableName,
            'message' => "Table '{$tableName}' created successfully"
        ];
    }

    /**
     * Execute DROP TABLE
     */
    private function executeDrop(array $parsed, Database $db): array
    {
        $tableName = $parsed['table'];

        if (!$this->storage->tableExists($tableName)) {
            return [
                'success' => false,
                'error' => "Table '{$tableName}' does not exist"
            ];
        }

        $this->storage->dropTable($tableName);

        return [
            'success' => true,
            'type' => 'DROP',
            'table' => $tableName,
            'message' => "Table '{$tableName}' dropped successfully"
        ];
    }

    /**
     * Execute INSERT
     */
    private function executeInsert(array $parsed, Database $db): array
    {
        $tableName = $parsed['table'];

        if (!$this->storage->tableExists($tableName)) {
            return [
                'success' => false,
                'error' => "Table '{$tableName}' does not exist"
            ];
        }

        $table = Table::load($tableName);
        $columns = $parsed['columns'];
        $values = $parsed['values'];

        $insertedIds = [];
        $affectedRows = 0;

        foreach ($values as $valueRow) {
            $row = [];
            foreach ($columns as $index => $column) {
                $row[$column] = $valueRow[$index] ?? null;
            }

            $result = $table->addRow($row);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }

            $affectedRows++;

            $schema = $table->getSchema();
            foreach ($schema['columns'] as $colName => $colDef) {
                if ($colDef['primaryKey'] ?? false) {
                    $insertedIds[] = $result['row'][$colName];
                }
            }
        }

        $table->save();

        return [
            'success' => true,
            'type' => 'INSERT',
            'table' => $tableName,
            'affectedRows' => $affectedRows,
            'insertedIds' => $insertedIds,
            'message' => "Inserted {$affectedRows} row(s) into '{$tableName}'"
        ];
    }

    /**
     * Execute SELECT
     */
    private function executeSelect(array $parsed, Database $db): array
    {
        $tableName = $parsed['table'];

        if (!$this->storage->tableExists($tableName)) {
            return [
                'success' => false,
                'error' => "Table '{$tableName}' does not exist"
            ];
        }

        $table = Table::load($tableName);
        $rows = $table->getRows();

        if (isset($parsed['join'])) {
            $rows = $this->processJoin($rows, $parsed['join'], $parsed['table'], $parsed['alias'] ?? null);
        }

        if (isset($parsed['where'])) {
            $filteredRows = [];
            foreach ($rows as $row) {
                if (WhereEvaluator::evaluate($row, $parsed['where'])) {
                    $filteredRows[] = $row;
                }
            }
            $rows = $filteredRows;
        }

        if (isset($parsed['orderBy'])) {
            $rows = $this->applyOrderBy($rows, $parsed['orderBy']);
        }

        if (isset($parsed['limit'])) {
            $rows = array_slice($rows, 0, $parsed['limit']);
        }

        $columns = $parsed['columns'];
        if ($columns === ['*'] || in_array('*', $columns)) {
            $resultRows = $rows;
        } else {
            $resultRows = [];
            foreach ($rows as $row) {
                $resultRow = [];
                foreach ($columns as $column) {
                    if (strpos($column, '.') !== false) {
                        if (isset($row[$column])) {
                            $resultRow[$column] = $row[$column];
                        }
                    } else {
                        if (isset($row[$column])) {
                            $resultRow[$column] = $row[$column];
                        }
                    }
                }
                $resultRows[] = $resultRow;
            }
        }

        return [
            'success' => true,
            'type' => 'SELECT',
            'table' => $tableName,
            'rows' => $resultRows,
            'rowCount' => count($resultRows),
            'message' => "Selected " . count($resultRows) . " row(s) from '{$tableName}'"
        ];
    }

    /**
     * Execute UPDATE
     */
    private function executeUpdate(array $parsed, Database $db): array
    {
        $tableName = $parsed['table'];

        if (!$this->storage->tableExists($tableName)) {
            return [
                'success' => false,
                'error' => "Table '{$tableName}' does not exist"
            ];
        }

        $table = Table::load($tableName);

        $condition = function($row) use ($parsed) {
            if (!isset($parsed['where'])) {
                return true;
            }
            return WhereEvaluator::evaluate($row, $parsed['where']);
        };

        $affectedRows = $table->updateRows($parsed['set'], $condition);

        $table->save();

        return [
            'success' => true,
            'type' => 'UPDATE',
            'table' => $tableName,
            'affectedRows' => $affectedRows,
            'message' => "Updated {$affectedRows} row(s) in '{$tableName}'"
        ];
    }

    /**
     * Execute DELETE
     */
    private function executeDelete(array $parsed, Database $db): array
    {
        $tableName = $parsed['table'];

        if (!$this->storage->tableExists($tableName)) {
            return [
                'success' => false,
                'error' => "Table '{$tableName}' does not exist"
            ];
        }

        $table = Table::load($tableName);

        $condition = function($row) use ($parsed) {
            if (!isset($parsed['where'])) {
                return true;
            }
            return WhereEvaluator::evaluate($row, $parsed['where']);
        };

        $affectedRows = $table->deleteRows($condition);

        $table->save();

        return [
            'success' => true,
            'type' => 'DELETE',
            'table' => $tableName,
            'affectedRows' => $affectedRows,
            'message' => "Deleted {$affectedRows} row(s) from '{$tableName}'"
        ];
    }

    /**
     * Process INNER JOIN
     */
    private function processJoin(array $leftRows, array $join, string $leftTableName, ?string $leftAlias): array
    {
        $rightTableName = $join['table'];

        if (!$this->storage->tableExists($rightTableName)) {
            throw new \Exception("Table '{$rightTableName}' does not exist");
        }

        $rightTable = Table::load($rightTableName);
        $rightRows = $rightTable->getRows();

        $leftTableAlias = $leftAlias ?? $leftTableName;
        $rightTableAlias = $join['alias'] ?? $rightTableName;

        $onLeft = $join['on']['left'];
        $onRight = $join['on']['right'];

        $leftJoinColumn = $onLeft['column'];
        $rightJoinColumn = $onRight['column'];

        $joinedRows = [];

        foreach ($leftRows as $leftRow) {
            foreach ($rightRows as $rightRow) {
                $leftValue = $leftRow[$leftJoinColumn] ?? null;
                $rightValue = $rightRow[$rightJoinColumn] ?? null;

                if ($leftValue !== null && $leftValue === $rightValue) {
                    $joinedRow = [];

                    foreach ($leftRow as $col => $val) {
                        $joinedRow[$leftTableAlias . '.' . $col] = $val;
                        $joinedRow[$col] = $val;
                    }

                    foreach ($rightRow as $col => $val) {
                        $joinedRow[$rightTableAlias . '.' . $col] = $val;
                        if (!isset($joinedRow[$col])) {
                            $joinedRow[$col] = $val;
                        }
                    }

                    $joinedRows[] = $joinedRow;
                }
            }
        }

        return $joinedRows;
    }

    /**
     * Apply ORDER BY clause
     */
    private function applyOrderBy(array $rows, array $orderBy): array
    {
        usort($rows, function($a, $b) use ($orderBy) {
            foreach ($orderBy as $order) {
                $column = $order['column'];
                $direction = $order['direction'];

                $aVal = $a[$column] ?? null;
                $bVal = $b[$column] ?? null;

                if ($aVal === $bVal) {
                    continue;
                }

                if ($aVal === null) {
                    return $direction === 'ASC' ? -1 : 1;
                }

                if ($bVal === null) {
                    return $direction === 'ASC' ? 1 : -1;
                }

                if (is_numeric($aVal) && is_numeric($bVal)) {
                    $cmp = $aVal <=> $bVal;
                } else {
                    $cmp = strcmp((string) $aVal, (string) $bVal);
                }

                if ($cmp !== 0) {
                    return $direction === 'ASC' ? $cmp : -$cmp;
                }
            }

            return 0;
        });

        return $rows;
    }
}