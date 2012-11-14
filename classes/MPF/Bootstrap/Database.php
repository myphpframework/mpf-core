<?php
namespace MPF\Bootstrap;
use MPF\Env;
use MPF\Db;
use MPF\Logger;

require(__DIR__.'/../Db.php');

class Database extends \MPF\Bootstrap implements Intheface
{
    /**
     * Initialize the Db object with the proper configs
     *
     * @throws Exception\InvalidXml
     * @throws Db\Exception\InvalidConfig
     * @throws Db\Exception\UnsupportedType
     * @param array $args
     * @return
     */
    public function init($args=array())
    {
        $filename = 'database';
        if (array_key_exists('filename', $args))
        {
            $filename = $args['filename'];
        }

        // we fetch all the potential database configs
        $paths = array_merge(array(CONFIG_PRIORITY_FOLDER), ENV::paths()->configs());
        foreach ($paths as $path)
        {
            $file = $path.$filename.'.xml';
            if (stream_resolve_include_path($file))
            {
                $xml = @simplexml_load_file($file);
                if (!$xml)
                {
                    throw new \MPF\Exception\InvalidXml($filename);
                }

                // TODO: we only fetch the first (nearest?) one we find, valid or not
                break;
            }
        }

        // if we found and loaded an xml we prepare the dbs
        if (isset($xml) && $xml)
        {
            Db::setServers($xml, $file);
            $this->initialized = true;
            return;
        }

        throw new \Exception('Could not initialize database with filename: '.$filename);
    }

    public function shutdown() {
        Logger::Log('ENV/Boostrap/Database', 'shutting down databases', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
    }

}
