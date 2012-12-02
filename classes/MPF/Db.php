<?php

namespace MPF;

require(__DIR__ . '/Db/Result.php');
require(__DIR__ . '/Db/Layer/Intheface.php');
require(__DIR__ . '/Db/Layer.php');
require(__DIR__ . '/Db/Connection/Intheface.php');
require(__DIR__ . '/Db/Connection.php');
require(__DIR__ . '/Db/Entry.php');

class DB {
    const QUERY_MODE_RESULTSET = 'resultset';
    const QUERY_MODE_STREAM = 'stream';

    const TYPE_MYSQL = 'MySQL';
    const TYPE_MYSQLI = 'MySQLi';
    const TYPE_SQLITE = 'SQLite';
    const TYPE_ORACLE = 'Oracle';
    const TYPE_MSSQL = 'MSSQL';
    const TYPE_POSTGRESQL = 'PostgreSQL';

    const ACCESS_TYPE_READ = 'r';
    const ACCESS_TYPE_WRITE = 'w';
    const ACCESS_TYPE_READWRITE = 'rw';

    const FOR_MAIN = 1;
    const FOR_SESSION = 2;

    /**
     *
     * @var \SimpleXmlElement
     */
    private static $servers = null;

    /**
     *
     * @var Db\Layer
     */
    private static $databases = array();

    /**
     * Returns the proper instance of the Db\Layer
     *
     * @throws Db\Exception\ConnectInfoNotFound
     * @throws Db\Exception\UnsupportedType
     * @throws Db\Exception\InvalidAccessType
     * @param string $name
     * @param string $dbType
     * @param string $accessType
     * @return Db\Layer
     */
    public static function byName($name, $dbType='MySQLi', $accessType='rw') {
        // if we request an invalid access type we throw an exception
        if (!in_array($accessType, array(DB::ACCESS_TYPE_READ, DB::ACCESS_TYPE_WRITE, DB::ACCESS_TYPE_READWRITE))) {
            $exception = new Db\Exception\InvalidAccessType($accessType);
            Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $index = $name . '_' . $dbType . '_' . $accessType;
        if (array_key_exists($index, self::$databases)) {
            return self::$databases[$index];
        }

        $className = self::getClassNameByType($dbType);
        // TODO: should return an array of connections not just one...
        $dbLayer = new $className(self::findConnectInfoByDbName($name, $dbType, $accessType));
        self::$databases[$index] = $dbLayer;

        return self::$databases[$index];
    }

    /**
     * Finds connection info by the database name
     *
     * @param string $name
     * @param string $dbType
     * @param string $accessType
     * @return Db\Connection
     */
    private static function findConnectInfoByDbName($name, $dbType, $accessType) {
        $className = self::getClassNameByType($dbType, 'Connection');
        $dbConnection = new $className();

        if (self::$servers === null) {
            $exception = new Db\Exception\DatabaseNotBootstrapped();
            Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        foreach (self::$servers->xpath('//server[@engine="' . $dbType . '"]') as $server) {
            foreach ($server->access as $access) {
                if ((string)$access['type'] == $accessType && (string)$access->database == $name) {
                    $host = (string) $server->host;

                    // if we have some variables or constants to switch in the host we do so
                    preg_match('/\{([$a-zA-Z0-9_]+)\}/i', $host, $matchs);
                    if (!empty($matchs)) {
                        if (preg_match('/^\$/', $matchs[1])) {
                            $host = str_replace($matchs[0], $GLOBALS[substr($matchs[1], 1)], $host);
                        } elseif (defined($matchs[1])) {
                            $host = str_replace($matchs[0], constant($matchs[1]), $host);
                        }
                    }
                    $dbConnection->setInfo($server['engine'], $host, (int) $server->port, (string) $access->database, (string) $access->login, (string) $access->password, (string)$access->type);
                }
            }
        }

        return $dbConnection;
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
    public static function setServers(\SimpleXMLElement $xml, $filePath) {
        self::validateConfig($xml, $filePath);
        self::$servers = $xml->server;
    }

    /**
     * Validates the database config to make sure we have everything we need
     *
     * @throws Db\Exception\InvalidConfig
     * @throws Db\Exception\UnsupportedType
     * @param \SimpleXMLElement $conf
     * @param string $filename
     */
    private static function validateConfig(\SimpleXMLElement $conf, $filename) {
        if (!$conf->server) {
            $exception = new Db\Exception\InvalidConfig($filename);
            Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        // Verify the nodes of the database config
        foreach ($conf->server as $server) {
            // TODO: DB + config.xml for SQLITE which has no port no login and no password... gotta update checked IF SQLite engine
            if (!$server['engine'] || !$server->host || !$server->port || !$server->access) {
                $exception = new Db\Exception\InvalidConfig($filename);
                Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                throw $exception;
            }

            self::getClassNameByType($server['engine']);
            foreach ($server->access as $access) {
                if (!$access['type'] || !$access->database || !$access->login || !$access->password) {
                    $exception = new Db\Exception\InvalidConfig($filename);
                    Logger::Log('DB', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                    throw $exception;
                }
            }
        }
    }

    /**
     * Returns the class name of the Database layer if we could find that type
     *
     * @throws Db\Exception\UnsupportedType
     * @param string $dbType
     * @param string $prefix
     * @return string
     */
    public static function getClassNameByType($dbType, $prefix='Layer') {
        $className = 'MPF\Db\\' . $prefix . '\\';
        // Find the right string case for the class name
        foreach (array(
            Db::TYPE_MYSQL,
            Db::TYPE_MYSQLI,
            Db::TYPE_POSTGRESQL,
            Db::TYPE_SQLITE,
            Db::TYPE_MSSQL,
            Db::TYPE_ORACLE,) as $type) {
            if (strtolower($type) == strtolower($dbType)) {
                $className .= $type;
                break;
            }
        }

        // Verify if the database is supported (If we have a Db\Layer class for it)
        if (!class_exists($className)
          || !in_array('MPF\Db\\' . $prefix . '\Intheface', class_implements($className))
          || !in_array('MPF\Db\\' . $prefix, class_parents($className))) {
            $exception = new Db\Exception\UnsupportedType($dbType);
            Logger::Log('Db', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        return $className;
    }

    private function __construct() {

    }

}