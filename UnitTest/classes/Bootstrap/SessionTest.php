<?php

use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Bootstrap\Session;

require_once(__DIR__ . '/../../bootstrap.php');

ENV::bootstrap(ENV::DATABASE, 'dbTestMySQL');

class Bootstrap_SessionTest extends PHPUnit_Framework_TestCase {

    public function testInit_InvalidConfig() {
        $bootstrap = new Session();
        $bootstrap->init();
        $this->assertNotEquals('', session_id());
        $bootstrap->shutdown();
    }
}