<?php

// Rectify the current path
$SITE_PATH = realpath('../../../');
$url = pathinfo($_SERVER['argv'][1]);
if (!array_key_exists('extension', $url)) {
  $url['dirname'] = '/'.$url['basename'];
}
$_SERVER['PWD'] = $SITE_PATH.'/http'.preg_replace('@\/$@', '', $url['dirname']);

require($SITE_PATH.'/bootstrap.php');
use MPF\ENV;
use MPF\Config;

if (property_exists(Config::get($_SERVER['argv'][2]), 'js')) {
  echo json_encode(Config::get($_SERVER['argv'][2])->js);
}
else {
  echo '{}';
}
