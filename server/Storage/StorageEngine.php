<?php

namespace Storage;

use Core\Config;

/**
 * StorageEngine - JSON File-Based Storage
 *
 * Handles all file I/O operations for tables, schemas, and indexes
 */
class StorageEngine
{
    private static $instance = null;
    private string $storagePath;
    private string $tablesPath;

    private function __construct()
    {
        $config = Config::getInstance();
        $this->storagePath = $config->get('storage_path');
        $this->tablesPath = $this->storagePath . '/tables';

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }

        if (!is_dir($this->tablesPath)) {
            mkdir($this->tablesPath, 0777, true);
        }
    }

    public static function getInstance(): StorageEngine
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if a table exists
     */
    public function tableExists(string $tableName): bool
    {
        $dataFile = $this->tablesPath . '/' . $tableName . '.json';
        $schemaFile = $this->tablesPath . '/' . $tableName . '_schema.json';
        return file_exists($dataFile) && file_exists($schemaFile);
    }

    /**
     * Load table data
     */
    public function loadTable(string $tableName): ?array
    {
        $file = $this->tablesPath . '/' . $tableName . '.json';

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to parse table data for '{$tableName}': " . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Save table data
     */
    public function saveTable(string $tableName, array $data): void
    {
        $file = $this->tablesPath . '/' . $tableName . '.json';

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to encode table data for '{$tableName}': " . json_last_error_msg());
        }

        $fp = fopen($file, 'w');
        if (!$fp) {
            throw new \Exception("Failed to open file for writing: {$file}");
        }

        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $json);
            flock($fp, LOCK_UN);
        } else {
            throw new \Exception("Failed to acquire lock on file: {$file}");
        }

        fclose($fp);
    }

    /**
     * Load table schema
     */
    public function loadSchema(string $tableName): ?array
    {
        $file = $this->tablesPath . '/' . $tableName . '_schema.json';

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $schema = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to parse schema for '{$tableName}': " . json_last_error_msg());
        }

        return $schema;
    }

    /**
     * Save table schema
     */
    public function saveSchema(string $tableName, array $schema): void
    {
        $file = $this->tablesPath . '/' . $tableName . '_schema.json';

        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to encode schema for '{$tableName}': " . json_last_error_msg());
        }

        file_put_contents($file, $json, LOCK_EX);
    }

    /**
     * Load table indexes
     */
    public function loadIndex(string $tableName): ?array
    {
        $file = $this->tablesPath . '/' . $tableName . '_index.json';

        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        $index = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to parse index for '{$tableName}': " . json_last_error_msg());
        }

        return $index;
    }

    /**
     * Save table indexes
     */
    public function saveIndex(string $tableName, array $index): void
    {
        $file = $this->tablesPath . '/' . $tableName . '_index.json';

        $json = json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to encode index for '{$tableName}': " . json_last_error_msg());
        }

        file_put_contents($file, $json, LOCK_EX);
    }

    /**
     * Drop table (delete all related files)
     */
    public function dropTable(string $tableName): void
    {
        $files = [
            $this->tablesPath . '/' . $tableName . '.json',
            $this->tablesPath . '/' . $tableName . '_schema.json',
            $this->tablesPath . '/' . $tableName . '_index.json'
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * List all tables
     */
    public function listTables(): array
    {
        $tables = [];
        $files = glob($this->tablesPath . '/*_schema.json');

        foreach ($files as $file) {
            $basename = basename($file);
            $tableName = str_replace('_schema.json', '', $basename);
            $tables[] = $tableName;
        }

        return $tables;
    }

    /**
     * Get storage path
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Get tables directory path
     */
    public function getTablesPath(): string
    {
        return $this->tablesPath;
    }
}