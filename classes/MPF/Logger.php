<?php

namespace MPF;

use MPF\Config;

// TODO: Like the bootstrap for Template system the dir for logging should be double checked... another bootstrap?

/**
 * Logs information
 */
class Logger
{

    const LEVEL_NONE = 0;
    const LEVEL_ALL = 1;
    const LEVEL_DEBUG = 2;
    const LEVEL_ERROR = 4;
    const LEVEL_FATAL = 8;
    const LEVEL_WARNING = 16;
    const LEVEL_INFO = 32;
    const CATEGORY_NONE = 0;
    const CATEGORY_ALL = 1;
    const CATEGORY_FRAMEWORK = 2;
    const CATEGORY_DATABASE = 4;
    const CATEGORY_TEMPLATE = 8;
    const CATEGORY_ENVIRONMENT = 16;
    const CATEGORY_SERVICE = 32;

    private static $buffer = array();

    /**
     * Appends a line to a log file
     *
     * @static
     * @throws \MPF\Exception\FileNotWritable
     * @param  string $className
     * @param  string $message
     * @param  bit $level
     * @param  bit $category
     * @return void
     */
    public static function Log($className, $message, $level, $category = 1)
    {
        static $currentLogLevel = null;
        static $currentLogCategory = null;

        if ($currentLogLevel === null) {
            $currentLogLevel = Config::get('settings')->logger->level;
        }

        if ($currentLogCategory === null) {
            $currentLogCategory = Config::get('settings')->logger->category;
        }

        if ($currentLogLevel == self::LEVEL_NONE) {
            return;
        }

        static $logFile = null;
        if ($logFile === null) {
            $logFile = Config::get('settings')->logger->file;
        }

        // if we have some logs in the buffer we append them to the file now and empty the buffer
        if (!empty(self::$buffer)) {
            foreach (self::$buffer as $log) {
                if (($log['level'] == ($currentLogLevel & $log['level'])) || (self::LEVEL_ALL == $currentLogLevel)) {
                    if (($log['category'] == ($currentLogCategory & $log['category'])) || (self::LEVEL_ALL == $currentLogCategory)) {
                        if (!@file_put_contents($logFile, $log['message'], FILE_APPEND)) {
                            throw new Exception\FileNotWritable($logFile);
                        }
                    }
                }
            }

            self::$buffer = array();
        }

        if (($level == ($currentLogLevel & $level)) || (self::LEVEL_ALL == $currentLogLevel)) {
            if (($category == ($currentLogCategory & $category)) || (self::LEVEL_ALL == $currentLogCategory)) {
                $microseconds = explode('.', sprintf('%0.4f', microtime(true)));
                if (!@file_put_contents($logFile, '[' . date('Y-m-d H:i:s.' . $microseconds[1]) . '][' . getmypid() . '][ ' . self::getLevelText($level) . ' ][ ' . self::getCategoryText($category) . ' ][ ' . $className . ' ] ' . str_replace("\n", ' ', $message) . "\n", FILE_APPEND)) {
                    throw new Exception\FileNotWritable($logFile);
                }
            }
        }
    }

    /**
     * Alternative to Log which does not save to file right away.
     * This is a work around for circular dependencies within the framework,
     * where the object Logger needs to fetch its configs and the object Config
     * needs to fetch the ENV->Paths() which uses to Logger.
     *
     * The buffer is put in the file at the next Log() function call
     *
     * @static
     * @param  string $className
     * @param  string $message
     * @param  bit $level
     * @param  bit $category
     * @return void
     */
    public static function Buffer($className, $message, $level, $category)
    {
        self::$buffer[] = array(
            'level' => $level,
            'category' => $category,
            'message' => '[      BUFFERED LOG      ][' . posix_getpid() . '][ ' . self::getLevelText($level) . ' ][ ' . self::getCategoryText($category) . ' ][ ' . $className . ' ] ' . str_replace("\n", ' ', $message) . "\n",
        );
    }

    /**
     * Converts the bit into text for the log
     *
     * @static
     * @param  bit $level
     * @return string
     */
    private static function getLevelText($level)
    {
        $levelText = array();
        if (self::LEVEL_DEBUG == (self::LEVEL_DEBUG & $level)) {
            $levelText[] = str_pad('DEBUG', 7);
        }

        if (self::LEVEL_ERROR == (self::LEVEL_ERROR & $level)) {
            $levelText[] = str_pad('ERROR', 7);
        }

        if (self::LEVEL_FATAL == (self::LEVEL_FATAL & $level)) {
            $levelText[] = str_pad('FATAL', 7);
        }

        if (self::LEVEL_WARNING == (self::LEVEL_WARNING & $level)) {
            $levelText[] = str_pad('WARNING', 7);
        }

        if (self::LEVEL_INFO == (self::LEVEL_INFO & $level)) {
            $levelText[] = str_pad('INFO', 7);
        }

        return implode('.', $levelText);
    }

    /**
     * Converts the bit into text for the log
     *
     * @static
     * @param  bit $category
     * @return string
     */
    private static function getCategoryText($category)
    {
        $categoryText = array();
        if (self::CATEGORY_FRAMEWORK == (self::CATEGORY_FRAMEWORK & $category)) {
            $categoryText[] = 'FRAMEWORK';
        }

        if (self::CATEGORY_DATABASE == (self::CATEGORY_DATABASE & $category)) {
            $categoryText[] = 'DATABASE';
        }

        if (self::CATEGORY_TEMPLATE == (self::CATEGORY_TEMPLATE & $category)) {
            $categoryText[] = 'TEMPLATE';
        }

        if (self::CATEGORY_ENVIRONMENT == (self::CATEGORY_ENVIRONMENT & $category)) {
            $categoryText[] = 'ENVIRONMENT';
        }

        if (self::CATEGORY_SERVICE == (self::CATEGORY_SERVICE & $category)) {
            $categoryText[] = 'SERVICE';
        }
        return implode('.', $categoryText);
    }

    private function __construct()
    {
        
    }

}
