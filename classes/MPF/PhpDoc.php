<?php

namespace MPF;

abstract class PhpDoc {
    const CLASS_NAME = 'object';
    const CLASS_DATABASE = 'database';
    const CLASS_TABLE = 'table';

    const PROPERTY_PRIMARY_KEY = 'primaryKey';
    const PROPERTY_READONLY = 'readonly';
    const PROPERTY_TYPE = 'type';
    const PROPERTY_DEFAULT_VALUE = 'default';
    const PROPERTY_ENCRYPTION = 'encryption';
    const PROPERTY_SALT = 'salt';
    const PROPERTY_PASSWORD = 'password';
    const PROPERTY_TABLE = 'table';
    const PROPERTY_MODEL = 'model';
    const PROPERTY_RELATION = 'relation';
    const PROPERTY_PRIVATE = 'private';
    const PROPERTY_DATABASE = 'database';

    protected static $phpdoc = array();

    /**
     * @var string
     */
    protected $className = '';

    protected static function generatePhpDoc($className) {
        if (!array_key_exists($className, self::$phpdoc)) {
            self::$phpdoc[$className] = array();
            $class = new \ReflectionClass($className);
            self::$phpdoc[$className]['class'] = self::getPhpDoc($class->getDocComment());
            foreach ($class->getMethods() as $method) {
                self::$phpdoc[$className]['methods'][$method->getName()] = self::getPhpDoc($method->getDocComment());
            }

            foreach ($class->getProperties() as $property) {
                self::$phpdoc[$className]['properties'][$property->getName()] = self::getPhpDoc($property->getDocComment());

                $property->setAccessible(true);
                self::$phpdoc[$className]['properties'][$property->getName()]['declaringClass'] = $property->getDeclaringClass()->name;
            }
        }
    }

    /**
     * Chops off the phpdoc in a useable array
     *
     * @param  string $rawPhpdoc
     * @return array
     */
    private static function getPhpDoc($rawPhpdoc) {
        preg_match_all("/@([a-z0-9]+)([a-z0-9 _\-:,\\\]{0,})\n/i", $rawPhpdoc, $matches);
        $phpdoc = array();
        foreach ($matches[1] as $index => $match) {
            $phpdoc[$match] = true;
            if ($value = trim($matches[2][$index])) {
                $phpdoc[$match] = $value;
                if (false !== strpos($value, ' ')) {
                    $phpdoc[$match] = preg_split("/\s/", $value);
                }

                if (false !== strpos($value, ',')) {
                    $phpdoc[$match] = preg_split("/,/", $value);
                }
            }
        }
        return $phpdoc;
    }

    protected function __construct() {
        $this->className = get_class($this);
        if (!array_key_exists($this->className, self::$phpdoc)) {
            self::generatePhpDoc($this->className);
        }

        //TODO: need to cache the phpdoc of models to avoid reparsing... reparsing faster than file IO?
        //var_dump(self::$phpdoc[ $this->className ]);
    }

}

