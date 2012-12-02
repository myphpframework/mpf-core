<?php

use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Bootstrap\Session;

require_once(__DIR__ . '/../../bootstrap.php');
require_once(PATH_FRAMEWORK . 'classes/MPF/Bootstrap.php');
require_once(PATH_FRAMEWORK . 'classes/MPF/Bootstrap/Intheface.php');
require_once(PATH_FRAMEWORK . 'classes/MPF/Bootstrap/Session.php');

//ENV::bootstrap(ENV::DATABASE, array('filename' => 'dbTest'));
//ENV::bootstrap(ENV::SESSION);

class Bootstrap_SessionTest extends PHPUnit_Framework_TestCase {

    public function testInit_InvalidConfig() {
        $bootstrap = new Session();
        $bootstrap->init();
        $this->assertNotEquals('', session_id());
        $bootstrap->shutdown();
    }
}