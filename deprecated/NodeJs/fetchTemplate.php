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
use MPF\Template;
ENV::bootstrap(ENV::TEMPLATE);

$template = Template::getFile($_SERVER['argv'][2].'.html');
echo $template->parse();

