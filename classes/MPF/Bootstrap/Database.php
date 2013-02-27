<?php

namespace MPF\Bootstrap;

use MPF\Env;
use MPF\Db;
use MPF\Logger;

require(__DIR__ . '/../Db.php');

class Database extends \MPF\Bootstrap implements Intheface {

    /**
     * Initialize the Db object with the proper configs
     *
     * @throws Exception\InvalidXml
     * @throws Db\Exception\InvalidConfig
     * @throws Db\Exception\UnsupportedType
     * @param array $args
     * @return
     */
    public function init($args=array()) {
        // we fetch all the potential database configs
        $paths = array_merge(array(CONFIG_PRIORITY_FOLDER), ENV::paths()->configs());
        foreach ($paths as $path) {
            if (stream_resolve_include_path($path . 'dbs')) {
                if ($handle = opendir($path . 'dbs')) {
                    $files = array();
                    while (false !== ($file = readdir($handle))) {
                        if ($file != "." && $file != "..") {
                            $files[] = $file;
                        }
                    }
                    closedir($handle);

                    foreach ($files as $file) {
                        $xml = @simplexml_load_file($path . 'dbs/'.$file);
                        if (!$xml) {
                            throw new \MPF\Exception\InvalidXml($filename);
                        }

                        Db::addDatabaseXml($xml, $file);
                    }
                }
            }
        }

        // if we found and loaded an xml we prepare the dbs
        #if (isset($xml) && $xml) {
        #    Db::addDatabase($xml, $file);
        #    $this->initialized = true;
        #    return;
        #}
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

    public function shutdown() {
        Logger::Log('ENV/Boostrap/Database', 'shutting down databases', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
    }

}
