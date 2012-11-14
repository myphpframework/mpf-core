<?php

namespace MPF;

use MPF\ENV;

class Config {

    /**
     *
     * @throws Exception\Config\FileNotFound
     * @staticvar array $configs
     * @param string $filename
     * @return StdObj
     */
    public static function get($filename) {
        static $configs = array();

        if (!array_key_exists('MPF_ENV', $_SERVER)) {
            die('<xmp>
        #################################################################
        # The enviroment variable "MPF_ENV" must be set according       #
        # to your /config/*.ini sections in order for myphpframework    #
        # to work correctly. See documentation.                         #
        #################################################################
      </xmp>');
        }

        if (array_key_exists($filename, $configs)) {
            return self::getEnv($configs[$filename]);
        }

        $cacheFile = CONFIG_CACHE_PATH . self::getCacheId($filename);
        if (CONFIG_CACHE && stream_resolve_include_path($cacheFile)) {
            $configs[$filename] = unserialize(file_get_contents($cacheFile));
            return self::getEnv($configs[$filename]);
        }

        Logger::Buffer('Config', 'Searching for file "' . $filename . '"', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
        $config = null;

        // We fetch the first file that we find in the paths
        $paths = array_merge(array(CONFIG_PRIORITY_FOLDER), ENV::paths()->configs());
        foreach ($paths as $path) {
            $file = $path . $filename . '.ini';
            if (stream_resolve_include_path($file)) {

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

            if (CONFIG_CACHE && !stream_resolve_include_path($cacheFile)) {
                @file_put_contents($cacheFile, serialize($config));
            }

            return self::getEnv($configs[$filename]);
        }
        throw new Exception\Config\FileNotFound($filename, $paths);
    }

    /**
     *
     * @param Config $config
     * @return stdClass
     */
    private static function getEnv(Config $config) {
        if (!array_key_exists($_SERVER['MPF_ENV'], $config->configs)) {
            throw new Exception\Config\EnvironmentNotFound('Enviroment "' . $_SERVER['MPF_ENV'] . '" does not exist in the config file!');
        }

        $return = $config->configs[$_SERVER['MPF_ENV']];
        if (array_key_exists($_SERVER['MPF_ENV'], $config->extends)) {
            $extendName = $config->extends[$_SERVER['MPF_ENV']];
            $return = array_merge_recursive_simple($config->configs[$extendName], $return);
        }

        return arrayToObject($return);
    }

    /**
     * Returns the Cache id for the config file
     *
     * @return string
     */
    private static function getCacheId($filename) {
        $pathInfo = pathinfo(ENV::paths()->getCurrentDir());
        return md5($pathInfo['dirname'] . $filename);
    }

    protected $configs = array();
    protected $extends = array();

    /**
     *
     * @param string $filename
     */
    protected function __construct($filename) {
    }

    /**
     *
     * @throws Exception\Config\Overlapping
     * @param type $iniSections
     */
    protected function addIni($iniSections) {
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
                        throw new Exception\Config\Overlapping(implode('.', $parts));
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