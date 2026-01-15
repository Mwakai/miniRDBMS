<?php

/**
 * Database class
 * 
 * Singleton class because we only need one connection per http request
 * 
 */

 class Database {

   private static ?Database $instance = null;
    
    /**
     * @var array Holds all loaded Table instances
     */
    private array $tables = [];
    
    /**
     * @var string Path to storage directory
     */
    private string $storagePath;
    

    private function __construct()
    {
        // Get storage path from Config (or use default)
        $this->storagePath = Config::getInstance()->get('storage_path', 'storage');
        
        // Ensure storage directory exists
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
        
        // Load all existing tables from storage
        // $this->loadExistingTables();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function execute()
    {
        
    }

    /**
     * Load all existing tables from storage
     * @return void
     */
    private function loadExistingTables(): void
    {
        
    }
 }