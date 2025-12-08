<?php

namespace Asinazionale\Core;

class Autoloader {
    protected static $registered = false;
    
    public static function register() {
        if (!self::$registered) {
            spl_autoload_register([__CLASS__, 'autoload']);
            self::$registered = true;
        }
    }

    /**
     * Autoloader function that doesn't use the old Wordpress tradition of naming class files starting with class- but uses namespaces and classes named after the file they are
     */
    public static function autoload($class) {
        $prefix = 'Asinazionale\\';
        // Autoloader file is already in the plugin's `includes` directory,
        // so the base directory for classes should be this directory.
        $base_dir = ASINAZIONALE_PLUGIN_PATH . 'includes/';
        $len = strlen($prefix);

        // Check if the namespace root of the class is Asinazionale\
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        // Remove the namespace Asinazionale\
        $relative_class = substr($class, $len);

        // Convert namespace to file path
        $file = $base_dir . str_replace('\\','/', $relative_class) . '.php';

        // If the file exists, include it
        if(file_exists($file)) {
            require $file;
        }

    }
}