<?php

use MPF\ENV;
use MPF\Config;
use MPF\Bootstrap\Template;

require_once(__DIR__ . '/../../bootstrap.php');

class Bootstrap_TemplateTest extends PHPUnit_Framework_TestCase {

    public function testInit_Template() {
        ENV::setType(ENV::TYPE_DEVELOPMENT);
        $bootstrap = new Template();
        $bootstrap->init();
        $this->assertTrue(file_exists(Config::get('settings')->template->cache->dir));
    }
    
    public function testInit_FolderNotWritable() {
        ENV::setType(ENV::TYPE_STAGING);
        $this->setExpectedException('MPF\Exception\FolderNotWritable');
        $bootstrap = new Template();
        $bootstrap->init();
        $this->fail('The exception "Exception_FolderNotFound" should be thrown if we cant find the file.');
    }

    public function testshutdown() {
        ENV::setType(ENV::TYPE_DEVELOPMENT);
        $bootstrap = new Template();
        $bootstrap->init();
        $bootstrap->shutdown();
    }

}