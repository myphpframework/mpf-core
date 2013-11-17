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

define('PATH_SITE',     '{PATH_SITE}');
define('PATH_MPF_CORE', '{PATH_MPF_CORE}');

if (PATH_MPF_CORE != '' && file_exists(PATH_MPF_CORE .'init.php')) {
    require_once(PATH_MPF_CORE.'classes/MPF/Config.php');
    \MPF\Config::$priority_folder = '/etc/mpf/';
    \MPF\Config::$cache_enabled = false;
    \MPF\Config::$cache_path = PATH_SITE.'cache/configs/';

    require(PATH_MPF_CORE .'init.php');
}

$cssFiles = array();
$jsFiles = array();
