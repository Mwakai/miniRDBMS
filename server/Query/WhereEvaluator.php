<?php

namespace Query;

/**
 * WhereEvaluator - WHERE Clause Logic
 *
 * Evaluates WHERE conditions against row data
 */
class WhereEvaluator
{
    /**
     * Evaluate a WHERE clause against a row
     *
     * @param array $row The row data
     * @param array $where The WHERE clause structure
     * @return bool True if row matches conditions, false otherwise
     */
    public static function evaluate(array $row, array $where): bool
    {
        if (!isset($where['conditions']) || empty($where['conditions'])) {
            return true;
        }

        $result = true;
        $lastLogic = 'AND';

        foreach ($where['conditions'] as $condition) {
            $conditionResult = self::evaluateCondition($row, $condition);

            if ($lastLogic === 'AND') {
                $result = $result && $conditionResult;
            } else {
                $result = $result || $conditionResult;
            }

            $lastLogic = $condition['logic'] ?? 'AND';
        }

        return $result;
    }

    /**
     * Evaluate a single condition
     *
     * @param array $row The row data
     * @param array $condition The condition structure
     * @return bool True if condition matches, false otherwise
     */
    private static function evaluateCondition(array $row, array $condition): bool
    {
        $column = $condition['column'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if ($column === null) {
            return false;
        }

        $columnParts = explode('.', $column);
        $columnName = end($columnParts);

        $rowValue = $row[$columnName] ?? null;

        switch (strtoupper($operator)) {
            case '=':
                return self::compare($rowValue, $value, '=');

            case '!=':
            case '<>':
                return self::compare($rowValue, $value, '!=');

            case '<':
                return self::compare($rowValue, $value, '<');

            case '>':
                return self::compare($rowValue, $value, '>');

            case '<=':
                return self::compare($rowValue, $value, '<=');

            case '>=':
                return self::compare($rowValue, $value, '>=');

            case 'LIKE':
                return self::like($rowValue, $value);

            case 'IN':
                return self::in($rowValue, $value);

            case 'IS':
                if ($value === null) {
                    return $rowValue === null;
                }
                return $rowValue === $value;

            default:
                return false;
        }
    }

    /**
     * Compare two values
     */
    private static function compare($left, $right, string $operator): bool
    {
        if ($left === null || $right === null) {
            if ($operator === '=' || $operator === '!=') {
                return $operator === '=' ? ($left === $right) : ($left !== $right);
            }
            return false;
        }

        switch ($operator) {
            case '=':
                if (is_numeric($left) && is_numeric($right)) {
                    return (float) $left === (float) $right;
                }
                return (string) $left === (string) $right;

            case '!=':
                if (is_numeric($left) && is_numeric($right)) {
                    return (float) $left !== (float) $right;
                }
                return (string) $left !== (string) $right;

            case '<':
                if (is_numeric($left) && is_numeric($right)) {
                    return (float) $left < (float) $right;
                }
                return (string) $left < (string) $right;

            case '>':
                if (is_numeric($left) && is_numeric($right)) {
                    return (float) $left > (float) $right;
                }
                return (string) $left > (string) $right;

            case '<=':
                if (is_numeric($left) && is_numeric($right)) {
                    return (float) $left <= (float) $right;
                }
                return (string) $left <= (string) $right;

            case '>=':
                if (is_numeric($left) && is_numeric($right)) {
                    return (float) $left >= (float) $right;
                }
                return (string) $left >= (string) $right;

            default:
                return false;
        }
    }

    /**
     * LIKE operator implementation
     *
     * Supports % (any characters) and _ (single character) wildcards
     */
    private static function like($value, string $pattern): bool
    {
        if ($value === null) {
            return false;
        }

        $pattern = preg_quote($pattern, '/');

        $pattern = str_replace('%', '.*', $pattern);
        $pattern = str_replace('_', '.', $pattern);

        $regex = '/^' . $pattern . '$/i';

        return preg_match($regex, (string) $value) === 1;
    }

    /**
     * IN operator implementation
     *
     * @param mixed $value The value to check
     * @param array|string $list The list of values
     * @return bool
     */
    private static function in($value, $list): bool
    {
        if (!is_array($list)) {
            $list = [$list];
        }

        if ($value === null) {
            return in_array(null, $list, true);
        }

        foreach ($list as $item) {
            if (is_numeric($value) && is_numeric($item)) {
                if ((float) $value === (float) $item) {
                    return true;
                }
            } elseif ((string) $value === (string) $item) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate WHERE clause with support for table aliases
     *
     * @param array $row The row data
     * @param array $where The WHERE clause
     * @param array $aliases Table alias mapping
     * @return bool
     */
    public static function evaluateWithAliases(array $row, array $where, array $aliases = []): bool
    {
        if (!isset($where['conditions']) || empty($where['conditions'])) {
            return true;
        }

        $result = true;
        $lastLogic = 'AND';

        foreach ($where['conditions'] as $condition) {
            $column = $condition['column'] ?? null;

            if ($column && strpos($column, '.') !== false) {
                list($tableOrAlias, $columnName) = explode('.', $column, 2);

                $actualTable = $aliases[$tableOrAlias] ?? $tableOrAlias;

                if (isset($row[$actualTable . '.' . $columnName])) {
                    $condition['column'] = $actualTable . '.' . $columnName;
                } elseif (isset($row[$columnName])) {
                    $condition['column'] = $columnName;
                }
            }

            $conditionResult = self::evaluateCondition($row, $condition);

            if ($lastLogic === 'AND') {
                $result = $result && $conditionResult;
            } else {
                $result = $result || $conditionResult;
            }

            $lastLogic = $condition['logic'] ?? 'AND';
        }

        return $result;
    }
}
