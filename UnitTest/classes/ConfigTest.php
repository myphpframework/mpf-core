<?php
use MPF\Config;

require_once(__DIR__.'/../bootstrap.php');

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testGet_FileNotFound() {
        $this->setExpectedException('MPF\Config\Exception\FileNotFound');
        Config::get('randomFileName');
        $this->fail('The exception "Exception_Config_FileNotFound" should be thrown if we cant find the file.');
    }

    public function testGet_UnknownEnvironment() {
        \MPF\ENV::setType('unknownEnvironment');
        $this->setExpectedException('MPF\Config\Exception\EnvironmentNotFound');
        Config::get('configTest');
        $this->fail('If the mpf.env does not match any section of the EnvironmentNotFound exception should be thrown');
    }

    public function testGet_Overlapping() {
        \MPF\ENV::setType('testing');
        $this->setExpectedException('MPF\Config\Exception\Overlapping');
        Config::get('configOverlapTest');
        $this->fail('If a config overlaps another an exception should be thrown');
    }

    public function testGet_ExtensibilityAndMultipleLevels() {
        \MPF\ENV::setType('production');
        $test1 = Config::get('configTest')->test1;
        $this->assertTrue(('prod' == $test1), 'The value should of been "prod" and not "'.print_r($test1, true).'"');

        \MPF\ENV::setType('development');
        $test1 = Config::get('configTest')->test1;
        $this->assertTrue(('dev' == $test1), 'The value should of been "dev" and not "'.print_r($test1, true).'"');

        \MPF\ENV::setType('development');
        $test2 = Config::get('configTest')->test2;
        $this->assertTrue(('prod' == $test2), 'The value should of been "prod" and not "'.print_r($test2, true).'"');

        $bool = Config::get('configTest')->bool;
        $this->assertTrue(is_bool($bool), 'The value should of been a boolean');

        $integer = Config::get('configTest')->integer;
        $this->assertTrue(is_int($integer), 'The value should of been a integer');

        $float = Config::get('configTest')->float;
        $this->assertTrue(is_float($float), 'The value should of been a float');

        $string = Config::get('configTest')->string;
        $this->assertTrue(is_string($string), 'The value should of been a string');

        $level2 = Config::get('configTest')->level1->level2;
        $this->assertTrue(('level2' == $level2), 'The value should of been "level2" and not "'.print_r($level2, true).'"');

        $level3 = Config::get('configTest')->level1->level3->level33;
        $this->assertTrue(('level3' == $level3), 'The value should of been "level3" and not "'.print_r($level3, true).'"');
    }
}
