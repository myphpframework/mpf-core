<?php

// TODO: There is no reason NOT to cache paths once in production.

namespace MPF {

    use MPF\Log\Category;
    use Psr\Log\LogLevel;

    use MPF\Config;
    use MPF\Text;
    use MPF\ENV\Paths;
    use MPF\Locale;

    require(__DIR__ . '/Bootstrap/Intheface.php');

    class ENV extends \MPF\Base
    {

        const TEMPLATE = 'Template';
        const DATABASE = 'Database';
        const SESSION = 'Session';
        const TYPE_DEVELOPMENT = 'development';
        const TYPE_TESTING = 'testing';
        const TYPE_STAGING = 'staging';
        const TYPE_PRODUCTION = 'production';

        private static $type = null;
        private static $tld = null;

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
            self::$tld = get_cfg_var('mpf.tld');
            
            $_ENV['type'] = self::$type;
            $_ENV['tld'] = self::$tld;
            
            setcookie('mpf_locale', ENV::getLocale()->getCode(), time() + 15552000, SESSION_COOKIE_PATH, SESSION_COOKIE_DOMAIN);
            
            // This is just to initiate the paths
            $paths = self::Paths();

            return $paths;
        }

        /**
         * Returns the locale of the session
         *
         * @return Locale
         */
        public static function getLocale()
        {
            if (!array_key_exists('mpf_locale', $_COOKIE)) {
                return new Locale(Config::get('settings')->default->locale);
            }

            return new Locale($_COOKIE['mpf_locale']);
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
         * This function should not be used unless for testing purposes...
         * @param string $tld;
         */
        public static function setTld($tld)
        {
            self::$tld = $tld;
        }

        public static function getTld()
        {
            return self::$tld;
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
         * @return Bootstrap_Interface
         */
        public static function bootstrap($type, $args=array())
        {
            if (!in_array($type, array(ENV::TEMPLATE, ENV::DATABASE, ENV::SESSION))) {
                throw new Bootstrap\Exception\UnsupportedType($type);
            }

            if (!array_key_exists($type, self::$bootstraps)) {
                $class = 'MPF\\Bootstrap\\' . $type;
                $bootstrap = new $class();
                if (!$bootstrap->isInitialized()) {
                    $logger = new \MPF\Log\Logger();
                    $logger->info('Initializing bootstrap "{type}"', array(
                        'category' => Category::FRAMEWORK | Category::ENVIRONMENT,
                        'className' => 'ENV',
                        'type' => $type
                    ));
                    
                    $bootstrap->init($args);
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

    use MPF\Log\Category;
    use Psr\Log\LogLevel;
    
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
            $logger = new \MPF\Log\Logger();

            if (empty(self::$paths)) {
                $logger->buffer(LogLevel::INFO, 'initiating environment paths...', array(
                    'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                    'className' => 'ENV\Paths'
                ));
                $this->currentDir = dirname(filter_var($_SERVER['SCRIPT_FILENAME'], \FILTER_SANITIZE_URL)) . '/';

                // If we are in a console or purposely overwriting we use PWD
                if (array_key_exists('PWD', $_SERVER) && false !== strpos($_SERVER['PWD'], PATH_SITE)) {
                    $this->currentDir = filter_var($_SERVER['PWD'], \FILTER_SANITIZE_URL);
                }

                $logger->buffer(LogLevel::INFO, 'current dir:' . $this->currentDir . ' == ' . PATH_SITE, array(
                    'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                    'className' => 'ENV\Paths'
                ));

                $folderNames = array(
                    self::FOLDER_BUCKET,
                    self::FOLDER_CONFIG,
                    self::FOLDER_CLASS,
                    self::FOLDER_TEMPLATE,
                    self::FOLDER_I18N,
                    self::FOLDER_INCLUDE,
                );
                foreach ($folderNames as $type) {
                    $logger->buffer(LogLevel::DEBUG, 'initiating type "' . $type . '"\'s array', array(
                        'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                        'className' => 'ENV\Paths'
                    ));

                    if (!array_key_exists($type, self::$paths)) {
                        self::$paths[$type] = array();
                    }
                    $failSafeCounter = 0;
                    $dir = $this->currentDir;

                    while (PATH_SITE != $dir) {
                        if (is_dir($dir . $type)) {
                            $logger->buffer(LogLevel::DEBUG, 'Found dir "' . $dir . $type . '/' . '"', array(
                                'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                                'className' => 'ENV\Paths'
                            ));

                            self::$paths[$type][] = $dir . $type . '/';
                        }

                        $failSafeCounter++;
                        if ($failSafeCounter >= self::DEPTH_LIMIT) {
                            $logger->buffer(LogLevel::WARNING, 'Depth limit as been reached while walking through dirs', array(
                                'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                                'className' => 'ENV\Paths'
                            ));
                            break;
                        }

                        preg_match('~/([a-zA-Z0-9_ \-]+/)$~', $dir, $matches);
                        if (empty($matches)) {
                            $logger->buffer(LogLevel::WARNING, 'no more dir matches, breaking out of dir walk', array(
                                'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                                'className' => 'ENV\Paths'
                            ));
                            break;
                        }
                        $dir = preg_replace('~' . $matches[1] . '$~', '', $dir);
                    }

                    if (is_dir($dir . $type) && !in_array($dir . $type . '/', self::$paths[$type])) {
                        $logger->buffer(LogLevel::DEBUG, 'Found dir "' . $dir . $type . '/' . '"', array(
                            'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                            'className' => 'ENV\Paths'
                        ));
                        self::$paths[$type][] = $dir . $type . '/';
                    }

                    if ($type != self::FOLDER_BUCKET) {
                        self::$paths[$type][] = PATH_MPF_CORE . $type . '/';
                    }
                }

                // TODO: Do we really need to set include paths since we are looping thru our own ENV:paths in autoload? (Messes up PHPUnit to reset include_path), should sanitize for performance? .
                //$logger->buffer(LogLevel::INFO, 'setting include paths to:'.implode(PATH_SEPARATOR, array(
                //    'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                //    'className' => 'ENV\Paths'
                //));
                //set_include_path(implode(PATH_SEPARATOR, self::$paths[self::FOLDER_INCLUDE]));

                $logger->buffer(LogLevel::DEBUG, 'Final paths ' . var_export(self::$paths, true), array(
                    'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                    'className' => 'ENV\Paths'
                ));
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

            $logger = new \MPF\Log\Logger();

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

                    $logger->debug('Found dir "{newPath}"', array(
                        'category' => Category::FRAMEWORK | Category::ENVIRONMENT,
                        'className' => 'ENV',
                        'newPath' => $newPath
                    ));
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

            $logger = new \MPF\Log\Logger();

            if (!in_array($path, self::$paths[$type])) {
                $logger->buffer(LogLevel::INFO, 'adding path "' . $path . '" of type "' . $type . '"', array(
                    'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                    'className' => 'ENV\Paths'
                ));

                array_unshift(self::$paths[$type], $path);
                if (self::FOLDER_INCLUDE == $type) {
                    $logger->buffer(LogLevel::INFO, 'adding new include path set_include_path:' . implode(PATH_SEPARATOR, self::$paths[self::FOLDER_INCLUDE]), array(
                        'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
                        'className' => 'ENV\Paths'
                    ));
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
