<?php

namespace Core;

use Query\Parser;
use Query\Executor;
use Storage\StorageEngine;

/**
 * Database class
 *
 * Singleton class because we only need one connection per http request
 *
 */
class Database
{
    private static ?Database $instance = null;

    /**
     * @var array Holds all loaded Table instances
     */
    private array $tables = [];

    /**
     * @var string Path to storage directory
     */
    private string $storagePath;

    /**
     * @var StorageEngine Storage engine instance
     */
    private StorageEngine $storage;

    private function __construct()
    {
        $this->storage = StorageEngine::getInstance();
        $this->storagePath = $this->storage->getStoragePath();

        $this->loadExistingTables();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Execute a SQL query
     */
    public function execute(string $sql): array
    {
        $parser = new Parser();
        $parsed = $parser->parse($sql);

        $executor = Executor::getInstance();
        return $executor->execute($parsed, $this);
    }

    /**
     * Load all existing tables from storage
     */
    private function loadExistingTables(): void
    {
        $tableNames = $this->storage->listTables();

        foreach ($tableNames as $tableName) {
            $this->tables[$tableName] = [
                'name' => $tableName,
                'loaded' => false
            ];
        }
    }

    /**
     * Get a table by name
     */
    public function getTable(string $name): ?Table
    {
        if (!$this->tableExists($name)) {
            return null;
        }

        return Table::load($name);
    }

    /**
     * Check if a table exists
     */
    public function tableExists(string $name): bool
    {
        return $this->storage->tableExists($name);
    }

    /**
     * Get list of all tables
     */
    public function listTables(): array
    {
        return $this->storage->listTables();
    }

    /**
     * Get table schema
     */
    public function getTableSchema(string $name): ?array
    {
        if (!$this->tableExists($name)) {
            return null;
        }

        return $this->storage->loadSchema($name);
    }

    /**
     * Get storage path
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }
}