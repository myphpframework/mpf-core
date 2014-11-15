<?php

namespace MPF;

use MPF\ENV;

class Config
{

    public static $cache_enabled = false;
    public static $cache_path = '/tmp/';

    public static function checkCacheDir()
    {
        if ($dir && (!is_dir($dir) || !is_writable($dir))) {
            if (!@mkdir($dir, 0775, true)) {
                return false;
            }
        }
        return true;
    }

    public static function clearCache()
    {
        if (null === shell_exec('rm -rf ' . escapeshellarg(self::$cache_path) . '  && echo "success"')) {
            return false;
        }
        return true;
    }

    /**
     *
     * @throws Config\Exception\FileNotFound
     * @staticvar array $configs
     * @param string $filename
     * @param bool $useCache
     * @return StdObj
     */
    public static function get($filename, $useCache = true)
    {
        static $configs = array();

        if (ENV::getType() === '') {
            die('<xmp>
        #################################################################
        # The php.ini value "mpf.env" must be set according to your     #
        # /config/*.ini sections in order for myphpframework            #
        # to work correctly. See documentation.                         #
        #################################################################
      </xmp>');
        }

        if ($useCache && array_key_exists($filename, $configs)) {
            return self::getEnv($configs[$filename]);
        }

        if (self::$cache_enabled && !self::checkCacheDir(self::$cache_path)) {
            $exception = new \MPF\Exception\FolderNotWritable(self::$cache_path);
            Logger::Log('Bootstrap/Template', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_TEMPLATE);
            throw $exception;
        }

        $cacheFile = self::$cache_path . self::getCacheId($filename);
        if (self::$cache_enabled && file_exists($cacheFile)) {
            $configs[$filename] = unserialize(file_get_contents($cacheFile));
            return self::getEnv($configs[$filename]);
        }

        Logger::Buffer('Config', 'Searching for file "' . $filename . '"', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
        $config = null;

        // We fetch the first file that we find in the paths
        $paths = array_merge(array(CONFIG_PRIORITY_FOLDER), ENV::paths()->configs());
        foreach ($paths as $path) {
            $file = $path . $filename . '.ini';
            if (file_exists($file)) {
                // If its the first file we instantiate the config
                if ($config === null) {
                    $config = new Config($filename);
                }
                $config->addIni(mpf_parse_ini_file($file));
            }
        }

        // if we found a file we add it to the static config array for that file name
        if ($config) {
            $configs[$filename] = $config;

            if (self::$cache_enabled && !file_exists($cacheFile)) {
                @file_put_contents($cacheFile, serialize($config));
            }

            return self::getEnv($configs[$filename]);
        }
        throw new Config\Exception\FileNotFound($filename, $paths);
    }

    /**
     *
     * @param Config $config
     * @return stdClass
     */
    private static function getEnv(Config $config)
    {
        if (!array_key_exists(ENV::getType(), $config->configs)) {
            throw new Config\Exception\EnvironmentNotFound('Enviroment "' . ENV::getType() . '" does not exist in the config file!');
        }

        $return = $config->configs[ENV::getType()];
        if (array_key_exists(ENV::getType(), $config->extends)) {
            $extendName = $config->extends[ENV::getType()];
            $return = array_merge_recursive_simple($config->configs[$extendName], $return);
        }

        return arrayToObject($return);
    }

    /**
     * Returns the Cache id for the config file
     *
     * @return string
     */
    private static function getCacheId($filename)
    {
        $pathInfo = pathinfo(ENV::paths()->getCurrentDir());
        return md5(@$pathInfo['dirname'] . $filename);
    }

    protected $configs = array();
    protected $extends = array();

    /**
     *
     * @param string $filename
     */
    protected function __construct($filename)
    {
        
    }

    /**
     *
     * @throws Config\Exception\Overlapping
     * @param type $iniSections
     */
    protected function addIni($iniSections)
    {
        foreach ($iniSections as $sectionName => $values) {
            if (strpos($sectionName, ':') !== false) {
                list ($sectionName, $extendName) = preg_split("/\s{0,}:\s{0,}/", $sectionName);
                $this->extends[$sectionName] = $extendName;
            }

            if (!array_key_exists($sectionName, $this->configs)) {
                $this->configs[$sectionName] = array();
            }

            foreach ($values as $key => $value) {
                // if the line is commented out
                if (preg_match('/^;/i', $key)) {
                    continue;
                }

                // FIXME: if value ends with the character ";" everything crashs...
                if (strpos($key, '.') === false) {
                    $this->configs[$sectionName][$key] = $value;
                    continue;
                }

                $parts = explode('.', $key);
                for ($i = 0; $i < count($parts); $i++) {
                    if ($i == 0) {
                        $config = & $this->configs[$sectionName];
                    }

                    // Since we are looping thru the file from the highest authority first
                    // we only set the value if its not already set
                    if ($i == (count($parts) - 1) && !isset($config[$parts[$i]])) {
                        $config[$parts[$i]] = $value;
                        continue;
                    }

                    if (!is_array($config)) {
                        throw new Config\Exception\Overlapping(implode('.', $parts));
                    }

                    if (!array_key_exists($parts[$i], $config)) {
                        $config[$parts[$i]] = array();
                    }

                    $config = & $config[$parts[$i]];
                }
            }
        }
    }

}
