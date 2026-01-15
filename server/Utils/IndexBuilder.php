<?php

namespace Utils;

/**
 * IndexBuilder - Index Management
 *
 * Builds and manages indexes for tables
 */
class IndexBuilder
{
    /**
     * Build an index for a column from rows
     *
     * @param array $rows The table rows
     * @param string $column The column to index
     * @param string $type Index type ('primary' or 'unique')
     * @return array Index structure
     */
    public static function buildIndex(array $rows, string $column, string $type = 'unique'): array
    {
        $index = [
            'type' => $type,
            'map' => []
        ];

        foreach ($rows as $rowIndex => $row) {
            if (!isset($row[$column])) {
                continue;
            }

            $value = $row[$column];
            $key = is_string($value) ? $value : (string) $value;

            if (!isset($index['map'][$key])) {
                $index['map'][$key] = [];
            }

            $index['map'][$key][] = $rowIndex;
        }

        return $index;
    }

    /**
     * Build all indexes for a table based on its schema
     *
     * @param array $rows The table rows
     * @param array $schema The table schema
     * @return array Array of indexes by column name
     */
    public static function buildAllIndexes(array $rows, array $schema): array
    {
        $indexes = [];
        $columns = $schema['columns'] ?? [];

        foreach ($columns as $columnName => $columnDef) {
            if ($columnDef['primaryKey'] ?? false) {
                $indexes[$columnName] = self::buildIndex($rows, $columnName, 'primary');
            } elseif ($columnDef['unique'] ?? false) {
                $indexes[$columnName] = self::buildIndex($rows, $columnName, 'unique');
            }
        }

        return $indexes;
    }

    /**
     * Check if a value violates uniqueness constraint
     *
     * @param mixed $value The value to check
     * @param string $column The column name
     * @param array $index The index structure
     * @return bool True if unique (no duplicate), false if duplicate exists
     */
    public static function checkUnique($value, string $column, array $index): bool
    {
        if (!isset($index[$column])) {
            return true;
        }

        $key = is_string($value) ? $value : (string) $value;

        return !isset($index[$column]['map'][$key]);
    }

    /**
     * Update index after inserting a new row
     *
     * @param array $index Current index structure
     * @param array $row The new row
     * @param int $rowIndex The index of the new row
     * @param string $column The column to update
     * @return array Updated index
     */
    public static function updateIndex(array $index, array $row, int $rowIndex, string $column): array
    {
        if (!isset($row[$column])) {
            return $index;
        }

        $value = $row[$column];
        $key = is_string($value) ? $value : (string) $value;

        if (!isset($index['map'][$key])) {
            $index['map'][$key] = [];
        }

        $index['map'][$key][] = $rowIndex;

        return $index;
    }

    /**
     * Find row indexes by value using an index
     *
     * @param array $index The index structure
     * @param mixed $value The value to search for
     * @return array Array of row indexes
     */
    public static function findByValue(array $index, $value): array
    {
        $key = is_string($value) ? $value : (string) $value;

        return $index['map'][$key] ?? [];
    }

    /**
     * Get all indexed values
     *
     * @param array $index The index structure
     * @return array Array of all indexed values
     */
    public static function getAllValues(array $index): array
    {
        return array_keys($index['map'] ?? []);
    }

    /**
     * Check if an index exists for a column
     *
     * @param array $indexes All table indexes
     * @param string $column The column name
     * @return bool
     */
    public static function hasIndex(array $indexes, string $column): bool
    {
        return isset($indexes[$column]);
    }

    /**
     * Get index type for a column
     *
     * @param array $indexes All table indexes
     * @param string $column The column name
     * @return string|null 'primary', 'unique', or null if no index
     */
    public static function getIndexType(array $indexes, string $column): ?string
    {
        return $indexes[$column]['type'] ?? null;
    }
}
