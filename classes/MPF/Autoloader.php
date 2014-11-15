<?php

namespace MPF;

class Autoloader {
    /**
     * Paths where classes might be in a priority order
     * 
     * @var array
     */
    protected $paths = array();
    
    public function __construct() {
    }
    
    /**
     * Register Autoloader
     * 
     * @return void
     */
    public function register() {
        spl_autoload_register(array($this, 'loadClass'));
    }
    
    /**
     * Adds a possible path for classes
     * 
     * @param type $path
     * @return void
     */
    public function addPath($path) {
        array_unshift($this->paths, $path);
    }
    
    public function loadClass($className) {
        $className = trim($className, '\\');

        // if its a namespace we change it into folders
        if (false !== strpos($className, '\\')) {
            $className = str_replace('\\', '/', $className) . '.php';
        }

        foreach ($this->paths as $path) {
            if ($this->loadFile($path.$className)) {
                return $path.$className;
            }
        }

        Logger::Buffer('__autoload', 'could NOT find class "' . $className . '"', Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
        return false;
    }
    
    protected function loadFile($filepath) {
        if (file_exists($filepath)) {
            require($filepath);
            return true;
        }
        return false;
    }
}
