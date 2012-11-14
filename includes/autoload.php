<?php

use MPF\ENV;
use MPF\Logger;

/*
 * Loads a class
 */

function __autoload($className) {
    // if we have a namespace class name we do a different replace
/*
    if (false !== strpos($className, '\\')) {
        $libClass = str_replace('\\', '/', $className) . '.php';
        $libName = substr($libClass, 0, strpos($libClass, '/'));
        $libClass = PATH_SITE . 'libs/' . $libName . '/' . preg_replace('#^' . $libName . '/#', 'classes/', $libClass);
        if (stream_resolve_include_path($libClass)) {
            Logger::Log('__autoload', 'Found lib class "' . $libClass . '"', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
            require($libClass);
            return;
        }
    }
*/
    // if we didnt find it in the libs folder and it does have a \ in it, its probably an addition to the a lib
    if (false !== strpos($className, '\\')) {
        $libName = substr($className, 0, strpos($className, '\\'));
        Logger::Log('__autoload', 'Found possible lib (' . $libName . ') extention (' . $className . ')', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
        $className = str_replace('\\', '/', $className) . '.php';
    } else {
        $className = implode('/', explode('_', $className)) . '.php';
    }

    foreach (ENV::paths()->classes() as $path) {
        if (stream_resolve_include_path($path . $className)) { //  && !is_dir($path . $className)
            Logger::Log('__autoload', 'Found file "' . $path . $className . '"', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
            require($path . $className);
            return;
        }
    }
    Logger::Log('__autoload', 'could NOT find class "' . $className . '"', Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
}
