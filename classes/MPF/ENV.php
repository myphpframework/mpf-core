<?php

// TODO: There is no reason NOT to cache paths once in production.

namespace MPF {

    use MPF\Logger;
    use MPF\Config;
    use MPF\Text;
    use MPF\ENV\Paths;

//use MPF\Bootstrap;

    require(__DIR__ . '/Bootstrap/Intheface.php');

    class ENV
    {

        const TEMPLATE = 'Template';
        const DATABASE = 'Database';
        const SESSION = 'Session';
        const TYPE_DEVELOPMENT = 'development';
        const TYPE_TESTING = 'testing';
        const TYPE_STAGING = 'staging';
        const TYPE_PRODUCTION = 'production';

        private static $type = null;

        /**
         * Bootstrap that were initiated
         *
         * @var Bootstrap_Interface
         */
        private static $bootstraps = array();

        /**
         * @param string $type
         * @return \MPF\ENV\Paths
         */
        public static function init($type)
        {
            if (!$type) {
                // TODO: Multi-lang exception required
                throw new \Exception('The value mpf.env must be defined/set in the php.ini for MyPhpFramework to work properly');
            }

            self::$type = $type;

            // This is just to initiate the paths and main settings/configs
            $paths = self::Paths();
            Config::get('settings');

            return $paths;
        }

        /**
         * This function should not be used unless for testing purposes...
         * @param string $type;
         */
        public static function setType($type)
        {
            self::$type = $type;
        }

        public static function getType()
        {
            return self::$type;
        }

        /**
         * Clears all cache, must be called in a while.
         * It returns information array about each type of cache
         * it tries to clear till it returns null
         *
         * @return array
         */
        public static function clearAllCache()
        {
            static $cacheTypes = array('Template', 'Config');

            if (empty($cacheTypes)) {
                $cacheTypes = array('Template', 'Config');
                return null;
            }

            foreach ($cacheTypes as $key => $type) {
                switch ($type) {
                    case 'Template':
                        unset($cacheTypes[$key]);
                        if (\MPF\Template::clearCache()) {
                            return true;
                        }
                        break;
                    case 'Config':
                        unset($cacheTypes[$key]);
                        if (\MPF\Config::clearCache()) {
                            return true;
                        }
                        break;
                }

                unset($cacheTypes[$key]);
                return $type;
            }

            return null;
        }

        /**
         * Bootstraps a certain environment and returns it
         *
         * ENV::TEMPLATE
         * ENV::DATABASE
         * ENV::SESSION
         *
         * @throws Bootstrap\Exception\UnsupportedType
         * @param string $type
         * @param string $filename
         * @return Bootstrap_Interface
         */
        public static function bootstrap($type, $filename = '')
        {
            if (!in_array($type, array(ENV::TEMPLATE, ENV::DATABASE, ENV::SESSION))) {
                throw new Bootstrap\Exception\UnsupportedType($type);
            }

            if (!array_key_exists($type, self::$bootstraps)) {
                Logger::Log('ENV', 'Instantiating bootstrap "' . $type . '"', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);

                $class = 'MPF\\Bootstrap\\' . $type;
                $bootstrap = new $class();
                if (!$bootstrap->isInitialized()) {
                    Logger::Log('ENV', 'Initiating bootstrap "' . $type . '"', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                    $bootstrap->init($filename);
                }

                self::$bootstraps[$type] = $bootstrap;
            }

            return self::$bootstraps[$type];
        }

        /**
         * Returns the paths from the framework
         *
         * @return \MPF\ENV\Paths
         */
        public static function paths()
        {
            static $path = null;

            if (!$path) {
                $path = new Paths();
            }

            return $path;
        }

        /**
         * register_shutdown_function
         *
         * Shutdown bootstraps in an orderly fashion
         */
        public static function shutdown()
        {
            // shutdown sessions first
            if (array_key_exists(self::SESSION, self::$bootstraps)) {
                self::$bootstraps[self::SESSION]->shutdown();
            }

            if (array_key_exists(self::TEMPLATE, self::$bootstraps)) {
                self::$bootstraps[self::TEMPLATE]->shutdown();
            }

            if (array_key_exists(self::DATABASE, self::$bootstraps)) {
                self::$bootstraps[self::DATABASE]->shutdown();
            }
        }

    }

}

namespace MPF\ENV {

    use MPF\Logger;

    /**
     * Class that is capable for crawling thru dirs to find
     * all the paths for a certain type.
     */
    class Paths
    {

        const DEPTH_LIMIT = 20;
        const FOLDER_CLASS = 'classes';
        const FOLDER_TEMPLATE = 'templates';
        const FOLDER_I18N = 'i18n';
        const FOLDER_CONFIG = 'config';
        const FOLDER_INCLUDE = 'includes';
        const FOLDER_BUCKET = 'buckets';

        //const FOLDER_CSS = 'css';

        static $paths = array();
        public $currentDir = null;

        public function __construct()
        {
            if (empty(self::$paths)) {
                Logger::Buffer('ENV\Paths', 'initiating environment paths...', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                $this->currentDir = dirname(filter_var($_SERVER['SCRIPT_FILENAME'], \FILTER_SANITIZE_URL)) . '/';

                // If we are in a console or purposely overwriting we use PWD
                if (array_key_exists('PWD', $_SERVER) && false !== strpos($_SERVER['PWD'], PATH_SITE)) {
                    $this->currentDir = filter_var($_SERVER['PWD'], \FILTER_SANITIZE_URL);
                }

                Logger::Buffer('ENV\Paths', 'current dir:' . $this->currentDir . ' == ' . PATH_SITE, Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);

                $folderNames = array(
                    self::FOLDER_BUCKET,
                    self::FOLDER_CONFIG,
                    self::FOLDER_CLASS,
                    self::FOLDER_TEMPLATE,
                    self::FOLDER_I18N,
                    self::FOLDER_INCLUDE,
                );
                foreach ($folderNames as $type) {
                    Logger::Buffer('ENV\Paths', 'initiating type "' . $type . '"\'s array', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                    if (!array_key_exists($type, self::$paths)) {
                        self::$paths[$type] = array();
                    }
                    $failSafeCounter = 0;
                    $dir = $this->currentDir;

                    while (PATH_SITE != $dir) {
                        if (is_dir($dir . $type)) {
                            Logger::Buffer('ENV\Paths', 'Found dir "' . $dir . $type . '/' . '"', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                            self::$paths[$type][] = $dir . $type . '/';
                        }

                        $failSafeCounter++;
                        if ($failSafeCounter >= self::DEPTH_LIMIT) {
                            Logger::Buffer('ENV\Paths', 'Depth limit as been reached while walking through dirs', Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                            break;
                        }

                        preg_match('~/([a-zA-Z0-9_ \-]+/)$~', $dir, $matches);
                        if (empty($matches)) {
                            Logger::Buffer('ENV\Paths', 'no more dir matches, breaking out of dir walk', Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                            break;
                        }
                        $dir = preg_replace('~' . $matches[1] . '$~', '', $dir);
                    }

                    if (is_dir($dir . $type) && !in_array($dir . $type . '/', self::$paths[$type])) {
                        Logger::Buffer('ENV\Paths', 'Found dir "' . $dir . $type . '/' . '"', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                        self::$paths[$type][] = $dir . $type . '/';
                    }

                    if ($type != self::FOLDER_BUCKET) {
                        self::$paths[$type][] = PATH_MPF_CORE . $type . '/';
                    }
                }

                // TODO: Do we really need to set include paths since we are looping thru our own ENV:paths in autoload? (Messes up PHPUnit to reset include_path), should sanitize for performance? .
                //Logger::Buffer('ENV\Paths', 'setting include paths to:'.implode(PATH_SEPARATOR, self::$paths[self::FOLDER_INCLUDE]), Logger::LEVEL_INFO|Logger::CATEGORY_FRAMEWORK);
                //set_include_path(implode(PATH_SEPARATOR, self::$paths[self::FOLDER_INCLUDE]));

                Logger::Buffer('ENV\Paths', 'Final paths ' . var_export(self::$paths, true), Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
            }
        }

        /**
         * Returns the current dir
         *
         * @return string
         */
        public function getCurrentDir()
        {
            return $this->currentDir;
        }

        /**
         * Looks up the path for all types and add them
         *
         * @param string $path
         */
        public function addAll($path)
        {
            $folderNames = array(
                self::FOLDER_BUCKET,
                self::FOLDER_CONFIG,
                self::FOLDER_CLASS,
                self::FOLDER_TEMPLATE,
                self::FOLDER_I18N,
                self::FOLDER_INCLUDE,
            );

            foreach ($folderNames as $type) {
                if (is_dir($path . $type)) {
                    $newPath = $path . $type . '/';

                    // if we found the key already in the array we remove it to avoid duplicates
                    // we still add it to give a chance to re-organise priorities
                    $foundKey = array_search($newPath, self::$paths[$type]);
                    if ($foundKey) {
                        self::$paths[$foundKey] = null;
                        unset(self::$paths[$type][$foundKey]);
                    }

                    Logger::log('ENV\Paths', 'Found dir "' . $newPath . '"', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                    array_unshift(self::$paths[$type], $newPath);
                }
            }
        }

        /**
         * Adds a path to the array of paths
         *
         * @param string $type
         * @param string $path
         */
        public function add($type, $path)
        {
            $validTypes = array(
                self::FOLDER_CONFIG,
                self::FOLDER_CLASS,
                self::FOLDER_TEMPLATE,
                self::FOLDER_I18N,
                self::FOLDER_BUCKET,
                self::FOLDER_INCLUDE);

            if (!in_array($type, $validTypes)) {
                // TODO: Change for custom Exception (phpdoc @throws) and Multi-Language Text
                throw new \Exception('Must provide a valid path type');
            }

            if (!is_dir($path)) {
                // TODO: Change for custom Exception (phpdoc @throws) and Multi-Language Text
                throw new \Exception('Must provide an existing directory');
            }

            if (!in_array($path, self::$paths[$type])) {
                Logger::Buffer('ENV\Paths', 'adding path "' . $path . '" of type "' . $type . '"', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                array_unshift(self::$paths[$type], $path);
                if (self::FOLDER_INCLUDE == $type) {
                    Logger::Buffer('ENV\Paths', 'adding new include path set_include_path:' . implode(PATH_SEPARATOR, self::$paths[self::FOLDER_INCLUDE]), Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
                    set_include_path(implode(PATH_SEPARATOR, self::$paths[self::FOLDER_INCLUDE]));
                }
            }
        }

        /**
         * Returns all the possible config path folders for current script location
         *
         * @return Array
         */
        public function configs()
        {
            return (array_key_exists(self::FOLDER_CONFIG, self::$paths) ? self::$paths[self::FOLDER_CONFIG] : array());
        }

        /**
         * Returns all the possible include path folders for current script location
         *
         * @return Array
         */
        public function includes()
        {
            return (array_key_exists(self::FOLDER_INCLUDE, self::$paths) ? self::$paths[self::FOLDER_INCLUDE] : array());
        }

        /**
         * Returns all the possible class path folders for current script location
         *
         * @return Array
         */
        public function classes()
        {
            return (array_key_exists(self::FOLDER_CLASS, self::$paths) ? self::$paths[self::FOLDER_CLASS] : array());
        }

        /**
         * Returns all the possible template path folders for current script location
         *
         * @return Array
         */
        public function buckets()
        {
            return (array_key_exists(self::FOLDER_BUCKET, self::$paths) ? self::$paths[self::FOLDER_BUCKET] : array());
        }

        /**
         * Returns all the possible template path folders for current script location
         *
         * @return Array
         */
        public function templates()
        {
            return (array_key_exists(self::FOLDER_TEMPLATE, self::$paths) ? self::$paths[self::FOLDER_TEMPLATE] : array());
        }

        /**
         * Returns all the possible language path folders for current script location
         *
         * @return Array
         */
        public function i18n()
        {
            return (array_key_exists(self::FOLDER_I18N, self::$paths) ? self::$paths[self::FOLDER_I18N] : array());
        }

    }

}
