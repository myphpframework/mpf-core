<?php

use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Bootstrap\Template;

require_once(__DIR__ . '/../../bootstrap.php');
require_once(PATH_MPF_CORE . 'classes/MPF/Bootstrap.php');
require_once(PATH_MPF_CORE . 'classes/MPF/Bootstrap/Intheface.php');
require_once(PATH_MPF_CORE . 'classes/MPF/Bootstrap/Template.php');


class Bootstrap_TemplateTest extends PHPUnit_Framework_TestCase {

    public function testInit_Template() {
        $bootstrap = ENV::bootstrap(ENV::TEMPLATE);
        $this->assertTrue(file_exists(Config::get('settings')->template->cache->dir));
    }
}