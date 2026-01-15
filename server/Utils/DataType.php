<?php

namespace Utils;

/**
 * DataType Validator and Converter
 *
 * Handles validation and conversion for supported data types:
 * INT, VARCHAR, TEXT, DATE, DATETIME, BOOLEAN
 */
class DataType
{
    const VALID_TYPES = ['INT', 'VARCHAR', 'TEXT', 'DATE', 'DATETIME', 'BOOLEAN'];

    /**
     * Validate if a value matches the specified column definition
     */
    public static function validate($value, array $columnDef): bool
    {
        if ($value === null) {
            return $columnDef['nullable'] ?? false;
        }

        $type = strtoupper($columnDef['type']);

        switch ($type) {
            case 'INT':
                return is_numeric($value);

            case 'VARCHAR':
                if (!is_string($value)) {
                    return false;
                }
                $length = $columnDef['length'] ?? 255;
                return strlen($value) <= $length;

            case 'TEXT':
                return is_string($value);

            case 'DATE':
                return self::isValidDate($value);

            case 'DATETIME':
                return self::isValidDateTime($value);

            case 'BOOLEAN':
                return is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1';

            default:
                return false;
        }
    }

    /**
     * Convert a value to the appropriate type
     */
    public static function convert($value, array $columnDef)
    {
        if ($value === null) {
            return null;
        }

        $type = strtoupper($columnDef['type']);

        switch ($type) {
            case 'INT':
                return (int) $value;

            case 'VARCHAR':
            case 'TEXT':
                return (string) $value;

            case 'DATE':
                return self::normalizeDate($value);

            case 'DATETIME':
                return self::normalizeDateTime($value);

            case 'BOOLEAN':
                if (is_bool($value)) {
                    return $value ? 1 : 0;
                }
                return in_array($value, [1, '1', true], true) ? 1 : 0;

            default:
                return $value;
        }
    }

    /**
     * Get the default value for a column
     */
    public static function getDefaultValue(array $columnDef)
    {
        if (isset($columnDef['default'])) {
            return $columnDef['default'];
        }

        if ($columnDef['nullable'] ?? false) {
            return null;
        }

        $type = strtoupper($columnDef['type']);

        switch ($type) {
            case 'INT':
                return 0;
            case 'VARCHAR':
            case 'TEXT':
                return '';
            case 'DATE':
                return date('Y-m-d');
            case 'DATETIME':
                return date('Y-m-d H:i:s');
            case 'BOOLEAN':
                return 0;
            default:
                return null;
        }
    }

    /**
     * Check if a date string is valid
     */
    private static function isValidDate($date): bool
    {
        if (!is_string($date)) {
            return false;
        }

        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Check if a datetime string is valid
     */
    private static function isValidDateTime($datetime): bool
    {
        if (!is_string($datetime)) {
            return false;
        }

        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d && $d->format('Y-m-d H:i:s') === $datetime;
    }

    /**
     * Normalize date to Y-m-d format
     */
    private static function normalizeDate($date): string
    {
        if (self::isValidDate($date)) {
            return $date;
        }

        $timestamp = is_numeric($date) ? (int) $date : strtotime($date);
        return date('Y-m-d', $timestamp);
    }

    /**
     * Normalize datetime to Y-m-d H:i:s format
     */
    private static function normalizeDateTime($datetime): string
    {
        if (self::isValidDateTime($datetime)) {
            return $datetime;
        }

        $timestamp = is_numeric($datetime) ? (int) $datetime : strtotime($datetime);
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Check if a type is valid
     */
    public static function isValidType(string $type): bool
    {
        return in_array(strtoupper($type), self::VALID_TYPES);
    }
}
