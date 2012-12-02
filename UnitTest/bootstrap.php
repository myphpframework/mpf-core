<?php

$_SERVER['MPF_ENV'] = 'development';

define('TIMER_START', microtime(true));
//date_default_timezone_set('America/Montreal');

error_reporting(E_ALL);
ini_set('display_errors',   'on');
ini_set('short_open_tag',   '1');
ini_set('register_globals', '0');
//ini_set('memory_limit',     '128M');

define('PATH_MPF_CORE', '/var/www/MPF-Core/');
define('PATH_SITE',     __DIR__.'/');

define('CONFIG_PRIORITY_FOLDER', '/tmp/mpf/');
define('CONFIG_CACHE', false);
define('CONFIG_CACHE_PATH', PATH_SITE.'cache/configs/');

require(PATH_MPF_CORE.'/init.php');

use \MPF\ENV;
use \MPF\ENV\Paths;
ENV::paths()->add(Paths::FOLDER_CONFIG, __DIR__.'/config/');
ENV::paths()->add(Paths::FOLDER_TEMPLATE, __DIR__.'/templates/');
ENV::paths()->add(Paths::FOLDER_I18N, __DIR__.'/i18n/');

// Reload settings which is loaded before we could add the paths
\MPF\Config::get('settings', false);


