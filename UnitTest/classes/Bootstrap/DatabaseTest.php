<?php

use MPF\ENV;
use MPF\Config;
use MPF\Bootstrap\Database;

require_once(__DIR__ . '/../../bootstrap.php');

class Bootstrap_DatabaseTest extends PHPUnit_Framework_TestCase {

    public function testInit_Database() {
        ENV::setType(ENV::TYPE_DEVELOPMENT);
        $bootstrap = new Database();
        $bootstrap->init('dbTestMySQL');
        //$this->assertTrue(file_exists(Config::get('settings')->template->cache->dir));
    }

    public function testInit_withFilename() {
        ENV::setType(ENV::TYPE_DEVELOPMENT);
        $bootstrap = new Database();
        $bootstrap->init('dbTestMySQL');
    }

    public function testInit_InvalidXml() {
        $this->setExpectedException('MPF\Exception\InvalidXml');
        ENV::setType(ENV::TYPE_DEVELOPMENT);
        $bootstrap = new Database();
        $bootstrap->init('dbTestInvalidXml');
    }

    public function testInit_InvalidXml2() {
        $this->setExpectedException('MPF\Exception\InvalidXml');
        ENV::setType(ENV::TYPE_DEVELOPMENT);
        $bootstrap = new Database();
        $bootstrap->init();
    }

    public function testshutdown() {
        ENV::setType(ENV::TYPE_DEVELOPMENT);
        $bootstrap = new Database();
        $bootstrap->init('dbTestMySQL');
        $bootstrap->shutdown();
    }

}