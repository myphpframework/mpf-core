<?php

define('TIMER_START', microtime(true));

// php.ini: date.timezone = America/Montreal
//date_default_timezone_set('America/Montreal');

error_reporting(E_ALL);
ini_set('display_errors',   'on');
ini_set('short_open_tag',   '1');
ini_set('register_globals', '0');
//ini_set('memory_limit',     '128M');

define('SESSION_COOKIE_DOMAIN', '.'.filter_var($_SERVER['SERVER_NAME'], FILTER_SANITIZE_STRING));
define('SESSION_COOKIE_PATH', '/');

define('URL_SITE',      '{URL_SITE}');
define('MPF_ENV',       '{MPF_ENV}');

define('PATH_SITE',     '{PATH_SITE}');
define('PATH_MPF_CORE', '{PATH_MPF_CORE}');

define('CONFIG_PRIORITY_FOLDER', '/etc/mpf/');
define('CONFIG_CACHE', false);
define('CONFIG_CACHE_PATH', PATH_SITE.'cache/configs/');

if (PATH_MPF_CORE != '' && file_exists(PATH_MPF_CORE .'init.php')) {
    require(PATH_MPF_CORE .'init.php');
}

$cssFiles = array();
$jsFiles = array();