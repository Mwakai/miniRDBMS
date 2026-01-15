<?php

namespace Core;

use Utils\DataType;
use Storage\StorageEngine;

/**
 * Table - In-Memory Table Representation
 *
 * Represents a table with its schema, rows, and indexes
 */
class Table
{
    private string $name;
    private array $schema;
    private array $rows;
    private array $indexes;
    private int $nextAutoIncrement;
    private StorageEngine $storage;

    public function __construct(string $name, array $schema, array $rows = [], array $indexes = [])
    {
        $this->name = $name;
        $this->schema = $schema;
        $this->rows = $rows;
        $this->indexes = $indexes;
        $this->nextAutoIncrement = $this->calculateNextAutoIncrement();
        $this->storage = StorageEngine::getInstance();
    }

    /**
     * Get table name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get table schema
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * Get all rows
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Get indexes
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Get next auto increment value
     */
    public function getNextAutoIncrement(): int
    {
        return $this->nextAutoIncrement;
    }

    /**
     * Add a new row
     */
    public function addRow(array $row): array
    {
        $validatedRow = $this->validateAndPrepareRow($row);

        if (isset($validatedRow['error'])) {
            return $validatedRow;
        }

        $this->rows[] = $validatedRow;

        return [
            'success' => true,
            'row' => $validatedRow
        ];
    }

    /**
     * Update rows matching a condition
     */
    public function updateRows(array $updates, callable $condition): int
    {
        $affected = 0;

        foreach ($this->rows as $index => $row) {
            if ($condition($row)) {
                foreach ($updates as $column => $value) {
                    if (isset($this->schema['columns'][$column])) {
                        $columnDef = $this->schema['columns'][$column];
                        $this->rows[$index][$column] = DataType::convert($value, $columnDef);
                    }
                }
                $affected++;
            }
        }

        return $affected;
    }

    /**
     * Delete rows matching a condition
     */
    public function deleteRows(callable $condition): int
    {
        $affected = 0;
        $newRows = [];

        foreach ($this->rows as $row) {
            if ($condition($row)) {
                $affected++;
            } else {
                $newRows[] = $row;
            }
        }

        $this->rows = $newRows;

        return $affected;
    }

    /**
     * Validate and prepare a row for insertion
     */
    private function validateAndPrepareRow(array $row): array
    {
        $preparedRow = [];
        $columns = $this->schema['columns'];

        foreach ($columns as $columnName => $columnDef) {
            if (isset($row[$columnName])) {
                $value = $row[$columnName];

                if (!DataType::validate($value, $columnDef)) {
                    return [
                        'error' => "Invalid value for column '{$columnName}'",
                        'success' => false
                    ];
                }

                $preparedRow[$columnName] = DataType::convert($value, $columnDef);
            } else {
                if ($columnDef['autoIncrement'] ?? false) {
                    $preparedRow[$columnName] = $this->nextAutoIncrement++;
                } elseif (isset($columnDef['default'])) {
                    $preparedRow[$columnName] = $columnDef['default'];
                } elseif ($columnDef['nullable'] ?? false) {
                    $preparedRow[$columnName] = null;
                } else {
                    return [
                        'error' => "Missing required column '{$columnName}'",
                        'success' => false
                    ];
                }
            }
        }

        $uniqueCheck = $this->checkUniqueConstraints($preparedRow);
        if (isset($uniqueCheck['error'])) {
            return $uniqueCheck;
        }

        return $preparedRow;
    }

    /**
     * Check unique constraints
     */
    private function checkUniqueConstraints(array $row): array
    {
        $columns = $this->schema['columns'];

        foreach ($columns as $columnName => $columnDef) {
            if (($columnDef['unique'] ?? false) || ($columnDef['primaryKey'] ?? false)) {
                $value = $row[$columnName];

                foreach ($this->rows as $existingRow) {
                    if ($existingRow[$columnName] === $value) {
                        return [
                            'error' => "Duplicate value for unique column '{$columnName}': {$value}",
                            'success' => false
                        ];
                    }
                }
            }
        }

        return ['success' => true];
    }

    /**
     * Calculate next auto increment value
     */
    private function calculateNextAutoIncrement(): int
    {
        $max = 0;

        foreach ($this->schema['columns'] as $columnName => $columnDef) {
            if ($columnDef['autoIncrement'] ?? false) {
                foreach ($this->rows as $row) {
                    if (isset($row[$columnName]) && $row[$columnName] > $max) {
                        $max = $row[$columnName];
                    }
                }
            }
        }

        return $max + 1;
    }

    /**
     * Rebuild indexes for this table
     */
    public function rebuildIndexes(): void
    {
        $this->indexes = [];
        $columns = $this->schema['columns'];

        foreach ($columns as $columnName => $columnDef) {
            if (($columnDef['primaryKey'] ?? false) || ($columnDef['unique'] ?? false)) {
                $type = ($columnDef['primaryKey'] ?? false) ? 'primary' : 'unique';
                $this->indexes[$columnName] = [
                    'type' => $type,
                    'map' => []
                ];

                foreach ($this->rows as $index => $row) {
                    $value = $row[$columnName];
                    $key = is_string($value) ? $value : (string) $value;

                    if (!isset($this->indexes[$columnName]['map'][$key])) {
                        $this->indexes[$columnName]['map'][$key] = [];
                    }

                    $this->indexes[$columnName]['map'][$key][] = $index;
                }
            }
        }
    }

    /**
     * Save table to storage
     */
    public function save(): void
    {
        $this->rebuildIndexes();

        $data = [
            'rows' => $this->rows,
            'rowCount' => count($this->rows),
            'nextAutoIncrement' => $this->nextAutoIncrement
        ];

        $this->storage->saveTable($this->name, $data);
        $this->storage->saveSchema($this->name, $this->schema);
        $this->storage->saveIndex($this->name, $this->indexes);
    }

    /**
     * Load table from storage
     */
    public static function load(string $name): ?Table
    {
        $storage = StorageEngine::getInstance();

        if (!$storage->tableExists($name)) {
            return null;
        }

        $schema = $storage->loadSchema($name);
        $data = $storage->loadTable($name);
        $indexes = $storage->loadIndex($name);

        $rows = $data['rows'] ?? [];

        return new self($name, $schema, $rows, $indexes);
    }
}
