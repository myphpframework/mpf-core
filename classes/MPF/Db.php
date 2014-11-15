<?php

namespace MPF;

class Db
{

    const TYPE_MYSQLI = 'MySQLi';
    const TYPE_SQLITE = 'SQLite';
    const TYPE_ORACLE = 'Oracle';
    const TYPE_MSSQL = 'MSSQL';
    const TYPE_POSTGRESQL = 'PostgreSQL';
    const ACCESS_TYPE_READ = 'r';
    const ACCESS_TYPE_WRITE = 'w';
    const ACCESS_TYPE_READWRITE = 'rw';

    /**
     * @var \SimpleXMLElement
     */
    protected static $database_xmls = array();

    /**
     * @var \MPF\Db\Layer
     */
    protected static $database_layers = array();

    /**
     * Returns the Default db layer, the one in mpf.xml
     *
     * @return Db\Layer
     */
    public static function getDefault()
    {
        foreach (self::$database_xmls as $xml) {
            if ($xml->isDefault) {
                return self::byName((string) $xml->name);
            }
        }

        return null;
    }

    /**
     * Returns the Default db layer, the one in mpf.xml
     *
     * @return string
     */
    public static function getDefaultName()
    {
        foreach (self::$database_xmls as $xml) {
            if ($xml->isDefault) {
                return (string) $xml->name;
            }
        }

        return null;
    }

    /**
     * Returns the proper instance of the Db\Layer
     *
     * @throws Db\Exception\ConnectInfoNotFound
     * @throws Db\Exception\UnsupportedType
     * @throws Db\Exception\InvalidAccessType
     * @param string $name
     * @return Db\Layer
     */
    public static function byName($name)
    {
        if (!$name) {
            $exception = new \MPF\Db\Exception\InvalidDatabaseName($name);
            Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        if (array_key_exists($name, self::$database_layers)) {
            return self::$database_layers[$name];
        }

        if (!array_key_exists($name, self::$database_xmls)) {
            $exception = new \MPF\Db\Exception\InvalidDatabaseName($name);
            Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $xml = self::$database_xmls[$name];

        $connectionId = 0;
        $dbConnections = array();
        $className = self::getClassNameByType((string) $xml->engine, 'Connection');
        foreach ($xml->server as $server) {
            foreach ($server->access as $access) {
                $dbConnection = new $className();
                $dbConnection->setId($connectionId++);
                $dbConnection->setInfo((string) $xml->engine, (string) $server->host, (int) $server->port, $name, (string) $access->login, (string) $access->password, (string) $access['type']);
                $dbConnections[] = $dbConnection;
            }
        }

        $className = self::getClassNameByType((string) $xml->engine);
        self::$database_layers[$name] = new $className($dbConnections);

        return self::$database_layers[$name];
    }

    /**
     * Returns the class name of the Database layer if we could find that type
     *
     * @throws Db\Exception\UnsupportedType
     * @param string $dbType
     * @param string $prefix
     * @return string
     */
    public static function getClassNameByType($dbType, $prefix = 'Layer')
    {
        $className = 'MPF\Db\\' . $prefix . '\\';
        // Find the right string case for the class name
        foreach (array(
    self::TYPE_MYSQLI,
    self::TYPE_POSTGRESQL,
    self::TYPE_SQLITE,
    self::TYPE_MSSQL,
    self::TYPE_ORACLE,) as $type) {
            if (strtolower($type) == strtolower($dbType)) {
                $className .= $type;
                break;
            }
        }

        // Verify if the database is supported (If we have a Db\Layer class for it)
        if (!class_exists($className) || !in_array('MPF\Db\\' . $prefix . '\Intheface', class_implements($className)) || !in_array('MPF\Db\\' . $prefix, class_parents($className))) {
            $exception = new Db\Exception\UnsupportedType($dbType);
            Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        return $className;
    }

    /**
     * Sets the xml for servers and their accesses
     *
     * @throws Db\Exception\InvalidConfig
     * @throws Db\Exception\UnsupportedType
     * @param \SimpleXMLElement $xml
     * @param $filePath
     *
     */
    public static function addDatabaseXml(\SimpleXMLElement $xml, $filePath)
    {
        try {
            self::validateConfig($xml, $filePath);
        } catch (\MPF\Db\Exception\InvalidConfig $e) {
            return;
        }

        if (!array_key_exists((string) $xml->name, self::$database_xmls)) {
            $fileInfo = pathinfo($filePath);
            if ($fileInfo['filename'] == 'default') {
                $xml->isDefault = true;
            }
            self::$database_xmls[(string) $xml->name] = $xml;
        }
    }

    /**
     * Validates the database config to make sure we have everything we need
     *
     * @throws Db\Exception\InvalidConfig
     * @throws Db\Exception\UnsupportedType
     * @param \SimpleXMLElement $conf
     * @param string $filename
     */
    private static function validateConfig(\SimpleXMLElement $conf, $filename)
    {
        if (!$conf->server || !$conf->name || !$conf->engine) {
            $exception = new Db\Exception\InvalidConfig($filename);
            Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        // Verify the nodes of the database config
        foreach ($conf->server as $server) {
            if (!$server->host || !$server->port || !$server->access) {
                $exception = new Db\Exception\InvalidConfig($filename);
                Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                throw $exception;
            }

            foreach ($server->access as $access) {
                if (!$access['type'] || !$access->login || !$access->password) {
                    $exception = new Db\Exception\InvalidConfig($filename);
                    Logger::Log('DB', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                    throw $exception;
                }
            }
        }
    }

    private function __construct()
    {
        
    }

}
