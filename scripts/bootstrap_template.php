<?php

define('TIMER_START', microtime(true));

// php.ini: date.timezone = America/Montreal
//date_default_timezone_set('America/Montreal');

error_reporting(E_ALL);
ini_set('display_errors',   'on');
ini_set('short_open_tag',   '1');
ini_set('register_globals', '0');
//ini_set('memory_limit',     '128M');

define('PATH_MPF_CORE', '{corePath}');
define('PATH_SITE',     '{sitePath}');
define('URL_SITE',      '{siteUrl}');

define('CONFIG_PRIORITY_FOLDER', '/etc/mpf/');
define('CONFIG_CACHE', false);
define('CONFIG_CACHE_PATH', PATH_SITE.'cache/configs/');

require(PATH_MPF_CORE .'init.php');

$cssFiles = array();
$jsFiles = array();