<?php
use MPF\Logger;
use MPF\ENV;
use MPF\Autoloader;

date_default_timezone_set('America/Montreal');

header('x-powered-by: MPF/0.1.0');

include (PATH_MPF_CORE .'includes/utils.php');
include (PATH_MPF_CORE .'classes/MPF/Base.php');
require_once (PATH_MPF_CORE .'classes/MPF/Config.php');
include (PATH_MPF_CORE .'classes/MPF/ENV.php');
include (PATH_MPF_CORE .'classes/MPF/Autoloader.php');

include (PATH_MPF_CORE .'classes/Psr/Log/LogLevel.php');
include (PATH_MPF_CORE .'classes/Psr/Log/LoggerInterface.php');
include (PATH_MPF_CORE .'classes/Psr/Log/AbstractLogger.php');
include (PATH_MPF_CORE .'classes/MPF/Log/Category.php');
include (PATH_MPF_CORE .'classes/MPF/Log/Logger.php');

ENV::init(get_cfg_var('mpf.env'));
\MPF\Log\Logger::$currentLogLevel = \MPF\Config::get('settings')->logger->level;
\MPF\Log\Logger::$currentCategoryLevel = \MPF\Config::get('settings')->logger->category;
\MPF\Log\Logger::$storageType = \MPF\Config::get('settings')->logger->storage;

$autoloader = new Autoloader();
foreach (ENV::paths()->classes() as $path) {
    $autoloader->addPath($path);
}
$autoloader->register();

register_shutdown_function(array('\MPF\ENV', 'shutdown'));


// if we have a post-inits scripts we include them all from the closest to initial path
foreach (ENV::paths()->includes() as $path) {
    if (file_exists($path.'post-init.php')) {
        include($path.'post-init.php');
    }
}
