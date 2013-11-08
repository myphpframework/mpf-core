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
     * @param string $filename
     * @return
     */
    public function init($filename='') {
        // we fetch all the potential database configs
        $paths = array_merge(array(\MPF\Config::$priority_folder), ENV::paths()->configs());
        foreach ($paths as $path) {
            if (file_exists($path . 'dbs')) {
                if ($filename) {
                    $file = $path . 'dbs/'.$filename.'.xml';
                    $xml = @simplexml_load_file($file);
                    if (!$xml) {
                        throw new \MPF\Exception\InvalidXml($file);
                    }

                    Db::addDatabaseXml($xml, $file);
                } else  if ($handle = opendir($path . 'dbs')) {
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
                            throw new \MPF\Exception\InvalidXml($path . 'dbs/'.$file);
                        }

                        Db::addDatabaseXml($xml, $file);
                    }
                }
            }
        }
    }

    public function shutdown() {
        Logger::Log('ENV/Boostrap/Database', 'shutting down databases', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
    }

}
