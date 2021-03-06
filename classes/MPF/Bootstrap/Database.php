<?php

namespace MPF\Bootstrap;

use MPF\Db;
use MPF\ENV;
use MPF\Log\Category;

require(__DIR__ . '/../Db.php');

class Database extends \MPF\Bootstrap implements Intheface
{

    /**
     * Initialize the Db object with the proper configs
     *
     * @throws Exception\InvalidXml
     * @throws Db\Exception\InvalidConfig
     * @param string $filename
     * @return
     */
    public function init($args=array())
    {
        $filename = (array_key_exists('filename', $args) ? $args['filename'] : "");

        // we fetch all the potential database configs
        $paths = array_merge(array(CONFIG_PRIORITY_FOLDER), ENV::paths()->configs());
        foreach ($paths as $path) {
            if (file_exists($path . 'dbs')) {
                if ($filename) {
                    $file = $path . 'dbs/' . $filename . '.xml';
                    $xml = @simplexml_load_file($file);
                    if (!$xml) {
                        throw new \MPF\Exception\InvalidXml($file);
                    }

                    Db::addDatabaseXml($xml, $file);
                } else if ($handle = opendir($path . 'dbs')) {
                    $files = array();
                    while (false !== ($file = readdir($handle))) {
                        if ($file != "." && $file != "..") {
                            $files[] = $file;
                        }
                    }
                    closedir($handle);

                    foreach ($files as $file) {
                        $xml = @simplexml_load_file($path . 'dbs/' . $file);
                        if (!$xml) {
                            throw new \MPF\Exception\InvalidXml($path . 'dbs/' . $file);
                        }

                        Db::addDatabaseXml($xml, $file);
                    }
                }
            }
        }
    }

    public function shutdown()
    {
        $this->getLogger()->info('Shutting down databases', array(
            'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
            'className' => 'ENV/Boostrap/Database'
        ));
    }

}
