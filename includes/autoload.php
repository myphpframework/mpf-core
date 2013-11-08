<?php

use MPF\ENV;
use MPF\Logger;

/*
 * Loads a class
 */

function __autoload($className) {
    // if we didnt find it in the libs folder and it does have a \ in it, its probably an addition to the a lib
    if (false !== strpos($className, '\\')) {
        $libName = substr($className, 0, strpos($className, '\\'));
        $className = str_replace('\\', '/', $className) . '.php';
    } else {
        $className = implode('/', explode('_', $className)) . '.php';
    }

    foreach (ENV::paths()->classes() as $path) {
        if (file_exists($path . $className)) {
            require($path . $className);
            return;
        }
    }
    Logger::Buffer('__autoload', 'could NOT find class "' . $className . '"', Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
}
