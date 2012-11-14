<?php
use MPF\Logger;
use MPF\ENV;

header('x-powered-by: MPF/0.1.0');

define('PATH_FRAMEWORK', __DIR__ .'/');

include (PATH_FRAMEWORK .'includes/utils.php');
include (PATH_FRAMEWORK .'classes/MPF/Logger.php');
include (PATH_FRAMEWORK .'classes/MPF/Config.php');
include (PATH_FRAMEWORK .'classes/MPF/ENV.php');
include (PATH_FRAMEWORK .'includes/autoload.php');

spl_autoload_register('__autoload');

ENV::init();

register_shutdown_function(array('\MPF\ENV', 'shutdown'));

Logger::Log('Framework.init', 'framework initialized', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK|Logger::CATEGORY_ENVIRONMENT);
