<?php

namespace MPF\Log;

use MPF\Log\Category;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

class Logger extends AbstractLogger
{
    /**
     * A lookup array to convert normal LogLevel to a bit.
     * This is used by MPF to only log specific LogLevels
     *
     * @var array
     */
    protected $levelBit = array(
        LogLevel::ERROR => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::EMERGENCY => 4,
        LogLevel::ALERT => 8,
        LogLevel::WARNING => 16,
        LogLevel::NOTICE => 32,
        LogLevel::INFO => 64,
        LogLevel::DEBUG => 128
    );

    /**
     * Allows to only log specific level and ignore the rest
     * 
     * @var bit
     */
    protected static $currentLogLevel = null;
    
    /**
     * Allows to only log specific categories and ignore the rest
     * 
     * @var bit
     */
    protected static $currentCategoryLevel = null;
    
    /**
     * Specifies which media to log to
     * 
     * @var string
     */
    protected static $storageType = null;
    
    /**
     * Storage media
     * 
     * @var \MPF\Logger\Storage\Intheface
     */
    protected static $storage = null;
    
    /**
     * Buffered logs
     * 
     * @var array
     */
    protected static $buffer = array();
    
    public function __construct($currentLogLevel = 0, $currentCategoryLevel = Category::ALL, $storageType = 'file')
    {
        if (null === static::$currentLogLevel && 0 !== $currentLogLevel) {
            static::$currentLogLevel = $currentLogLevel;
        }
        
        if (null === static::$currentCategoryLevel) {
            static::$currentCategoryLevel = $currentCategoryLevel;
        }
        
        if (null === static::$storageType) {
            static::$storageType = $storageType;
        }
    }
    
    /**
     * Used in certain cases where it creates a circular dependancy with the logger.
     * Like the \MPF\Config class
     */
    public function buffer($level, $logMessage, array $context = array())
    {
        $bitLevel = $this->levelBit[$level];
        if (null !== static::$currentLogLevel && $bitLevel != ($bitLevel & static::$currentLogLevel)) {
            return;
        }

        if (null !== static::$currentCategoryLevel && array_key_exists('category', $context) 
            && $context['category'] != ($context['category'] & static::$currentCategoryLevel) 
            && static::$currentCategoryLevel != Category::ALL) {
            return;
        }

        $microseconds = explode('.', sprintf('%0.4f', microtime(true)));
        $timestamp = date('Y-m-d H:i:s.' . $microseconds[1]);

        $message = $this->interpolate($logMessage, $context);
        static::$buffer[$timestamp] = array(
            'level' => $level,
            'message' => $message,
            'context' => $context
        );
    }
    
    public function log($level, $logMessage, array $context = array())
    {
        $storage = $this->getStorage();
        if (!$storage) {
            throw new \Exception('No Media Storage found for the logger');
            return;
        }
        
        // if we have anything in the buffer we flush it now
        if (!empty(static::$buffer)) {
            foreach (static::$buffer as $timestamp => $info) {
                $category = "";
                if (array_key_exists('category', $info['context'])) {
                    $category = $info['context']['category'];
                }

                $className = "";
                if (array_key_exists('className', $info['context'])) {
                    $className = $info['context']['className'];
                }
                $storage->save($info['message'], strtoupper($info['level']), $category, $className);
            }
            
            static::$buffer = array();
        }
        
        if (!in_array($level, array(LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG))) {
            throw new InvalidArgumentException();
        }

        $bitLevel = $this->levelBit[$level];
        if (null !== static::$currentLogLevel && $bitLevel != ($bitLevel & static::$currentLogLevel)) {
            return;
        }

        if (null !== static::$currentCategoryLevel && array_key_exists('category', $context) 
            && $context['category'] != ($context['category'] & static::$currentCategoryLevel) 
            && static::$currentCategoryLevel != Category::ALL) {
            return;
        }

        $message = $this->interpolate($logMessage, $context);
        
        $category = "";
        if (array_key_exists('category', $context)) {
            $category = $context['category'];
        }
        
        $className = "";
        if (array_key_exists('className', $context)) {
            $className = $context['className'];
        }

        $storage->save($message, strtoupper($level), $category, $className);
    }
    
    /**
     * 
     * @return \MPF\Log\Storage\Intheface
     */
    protected function getStorage()
    {
        if (static::$storage === null) {
            $className = '\MPF\Log\Storage\\'.ucfirst(static::$storageType);
            if (class_exists($className)) {
                static::$storage = new $className();
            }
        }
        
        return static::$storage;
    }
    
    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message
     * @param array  $context
     * @return type
     */
    private function interpolate($message, array $context = array())
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
