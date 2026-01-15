<?php

/**
 * miniRDBMS Bootstrap
 *
 * PSR-4 compliant autoloader and initialization
 */

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/server/';

    $namespaceMap = [
        'Core\\' => 'Core/',
        'Query\\' => 'Query/',
        'Storage\\' => 'Storage/',
        'Utils\\' => 'Utils/'
    ];

    foreach ($namespaceMap as $namespace => $dir) {
        if (strpos($class, $namespace) === 0) {
            $relativeClass = substr($class, strlen($namespace));
            $file = $baseDir . $dir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }

    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');
