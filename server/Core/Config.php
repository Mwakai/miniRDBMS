<?php

namespace Core;

class Config
{
    private static $instance = null;

    private array $config = [];

    private function __construct()
    {
        // Use environment variable for storage path (for cloud hosting like Render)
        // Falls back to local Storage directory
        $storagePath = getenv('STORAGE_PATH') ?: __DIR__ . '/../Storage';

        $this->config = [
            'storage_path' => $storagePath,
            'storage_format' => 'json',
            'debug_mode' => false,
            'max_query_time' => 5000,
            'default_limit' => 1000,
            'enable_query_log' => false,
        ];
    }

    /**
     * Get singleton instance
     * 
     * @return Config
     */
    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Set a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }
    
    /**
     * Check if a configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }
    
    /**
     * Get all configuration values
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }
    
    /**
     * Reset instance (for testing)
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
    
}